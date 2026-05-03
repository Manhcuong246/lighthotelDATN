<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\BookingRoom;
use App\Models\User;
use App\Models\BookingLog;
use App\Support\BookingInvoiceViewData;
use App\Services\VnPayService;
use App\Services\BookingService;
use App\Http\Requests\StoreBookingRequest;
use App\Exceptions\BookingException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Carbon\CarbonPeriod;

class BookingController extends Controller
{
    private const SIGNATURE_IGNORE_QUERY_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'mc_cid',
        'mc_eid',
        'igshid',
        'si',
        'source',
    ];

    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Chi tiết đơn: chủ tài khoản đã đăng nhập, hoặc khách mở link có chữ ký (sau thanh toán / email).
     */
    public function show(Request $request, Booking $booking)
    {
        $booking->load([
            'user',
            'room.roomType',
            'room.images',
            'rooms.roomType',
            'rooms.images',
            'bookingRooms.roomType',
            'bookingRooms.room.roomType',
            'bookingRooms.room.images',
            'payment',
            'bookingServices.service',
            'surcharges.service',
            'refundRequest',
            'refundLogs',
        ]);

        $isOwner = Auth::check() && (int) Auth::id() === (int) $booking->user_id;
        $signedOk = $this->hasValidSignedAccess($request);
        if (! $isOwner && ! $signedOk) {
            abort(403, 'Bạn không có quyền xem đơn đặt phòng này. Đăng nhập đúng tài khoản đặt phòng hoặc dùng link được gửi trong email xác nhận.');
        }

        $guestPortalUser = null;
        $guestPortalIndexUrl = null;
        $guestPortalInvoiceUrl = null;

        // Nếu mở bằng link ký không đăng nhập, vẫn cho mở hóa đơn khách (khi đủ điều kiện).
        if (! $isOwner && $signedOk) {
            $expires = now()->addDays(max(1, (int) config('booking.signed_booking_show_ttl_days', 90)));
            if (BookingInvoiceViewData::guestCanViewInvoiceSheet($booking)) {
                $guestPortalInvoiceUrl = URL::temporarySignedRoute(
                    'bookings.invoice',
                    $expires,
                    ['booking' => $booking->id, 'portal_user' => $booking->user_id],
                    false
                );
            }

            // Nếu mở từ portal guest (link ký có portal_user), giữ điều hướng quay lại đúng ngữ cảnh.
            $portalUserId = (int) $request->query('portal_user', 0);
            if ($portalUserId > 0 && $portalUserId === (int) $booking->user_id) {
                $portalUser = User::query()->find($portalUserId);
                if ($portalUser && ! $portalUser->canAccessAdmin()) {
                    $guestPortalUser = $portalUser;
                    $guestPortalIndexUrl = URL::temporarySignedRoute(
                        'guest.bookings.index',
                        $expires,
                        ['user' => $portalUserId],
                        false
                    );
                }
            }
        }

        return view('account.booking-show', compact('booking', 'guestPortalUser', 'guestPortalIndexUrl', 'guestPortalInvoiceUrl'));
    }

    /**
     * Hóa đơn khách: chỉ chủ đơn hoặc link ký hợp lệ.
     */
    public function invoice(Request $request, Booking $booking)
    {
        $isOwner = Auth::check() && (int) Auth::id() === (int) $booking->user_id;
        $signedOk = $this->hasValidSignedAccess($request);
        if (! $isOwner && ! $signedOk) {
            abort(403, 'Bạn không có quyền xem hóa đơn này.');
        }

        if (! BookingInvoiceViewData::guestCanViewInvoiceSheet($booking)) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', 'Biên lai chỉ xem được khi đơn đã thanh toán và chưa hủy.');
        }

        $guestPortalBookingShowUrl = null;
        if (! $isOwner && $signedOk) {
            $portalUserId = (int) $request->query('portal_user', (int) $booking->user_id);
            $guestPortalBookingShowUrl = URL::temporarySignedRoute(
                'bookings.show',
                now()->addDays(max(1, (int) config('booking.signed_booking_show_ttl_days', 90))),
                ['booking' => $booking->id, 'portal_user' => $portalUserId],
                false
            );
        }

        return view(
            'account.booking-invoice',
            array_merge(BookingInvoiceViewData::make($booking), [
                'guestPortalBookingShowUrl' => $guestPortalBookingShowUrl,
            ])
        );
    }

    private function hasValidSignedAccess(Request $request): bool
    {
        return $request->hasValidSignatureWhileIgnoring(self::SIGNATURE_IGNORE_QUERY_PARAMS, true)
            || $request->hasValidSignatureWhileIgnoring(self::SIGNATURE_IGNORE_QUERY_PARAMS, false);
    }

    public function store(StoreBookingRequest $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->canAccessAdmin()) {
            return back()->withErrors('Tài khoản nhân viên/quản trị không thể đặt phòng trên giao diện khách.')->withInput();
        }

        try {
            $validated = $request->validated();
            $booking = $this->bookingService->createBooking($validated);

            // Tạo 1 Guest duy nhất - người đại diện (CCCD có thể bổ sung khi check-in)
            if ($request->filled('name')) {
                $repCccd = trim((string) $request->input('cccd', ''));
                Guest::create([
                    'booking_id'       => $booking->id,
                    'room_type'        => null,
                    'room_index'       => 0,
                    'name'             => trim($request->input('name')),
                    'cccd'             => $repCccd !== '' ? $repCccd : null,
                    'type'             => 'adult',
                    'is_representative'=> 1,
                    'checkin_status'   => 'pending',
                ]);
            }

            // 11. Redirect VNPay
            $vnPayService = app(VnPayService::class);
            $returnUrl    = route('payment.vnpay.return');
            $orderInfo    = 'Dat phong Light Hotel #' . $booking->id;
            $txnRef       = 'LIGHT' . $booking->id;
            $amountVND    = (int) round($booking->total_price);
            $bankCode     = $request->input('bank_code') ?: null;

            $paymentUrl = $vnPayService->createPaymentUrl(
                $txnRef,
                $amountVND,
                $orderInfo,
                $returnUrl,
                $request->ip(),
                'vn',
                $bankCode
            );

            // Email hướng dẫn/link thanh toán chỉ gửi khi admin đặt hộ (BookingAdminController).
            // Khách đặt web: chuyển thẳng VNPay; sau khi trả về thành công sẽ gửi mail xác nhận (VnPayController).

            return $vnPayService->redirectAwayNoCache($paymentUrl);

        } catch (BookingException $e) {
            return back()->withErrors($e->getMessage())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    private function normalizeGuestPayload(array $guests): array
    {
        $flattened = [];

        foreach ($guests as $topKey => $value) {
            if (! is_array($value)) {
                continue;
            }

            if (isset($value['name']) || isset($value['cccd']) || isset($value['type']) || isset($value['room_index'])) {
                $flattened[] = [
                    'room_type' => null,
                    'room_index' => isset($value['room_index']) ? (int) $value['room_index'] : 0,
                    'name' => trim((string) ($value['name'] ?? '')),
                    'cccd' => trim((string) ($value['cccd'] ?? '')),
                    'type' => $value['type'] ?? 'adult',
                ];
                continue;
            }

            foreach ($value as $guestData) {
                if (! is_array($guestData)) {
                    continue;
                }

                $flattened[] = [
                    'room_type' => trim((string) $topKey),
                    'room_index' => 0,
                    'name' => trim((string) ($guestData['name'] ?? '')),
                    'cccd' => trim((string) ($guestData['cccd'] ?? '')),
                    'type' => $guestData['type'] ?? 'adult',
                ];
            }
        }

        return $flattened;
    }

    public function update(Request $request, Booking $booking)
    {
        // Giữ nguyên update nhưng xóa calculateTotalPrice ở dưới nếu trùng lặp
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);
        $booking->update(['status' => $data['status']]);
        return back()->with('success', 'Cập nhật trạng thái thành công');
    }

    public function checkIn(Booking $booking)
    {
        abort_unless($booking->isCheckinAllowed(), 403);
        $booking->update([
            'status' => 'checked_in',
            'actual_check_in' => now(),
        ]);
        return back()->with('success', 'Check-in thành công');
    }

    public function checkOut(Booking $booking)
    {
        abort_unless($booking->isCheckoutAllowed(), 403);

        $oldStatus = $booking->status;
        $staffName = auth()->user()?->full_name ?? 'Hệ thống';

        $booking->update([
            'actual_check_out' => now(),
            'status' => 'completed',
        ]);

        if ($oldStatus !== 'completed') {
            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'completed',
                'notes' => "{$staffName} check-out.",
                'changed_at' => now(),
            ]);
        }

        return back()->with('success', 'Check-out thành công');
    }

    /**
     * Hiển thị form đặt phòng đơn giản
     */
    public function createSimple(Request $request)
    {
        $validated = $request->validate([
            'room_type_id' => 'nullable|integer|exists:room_types,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $roomType = null;
        if (! empty($validated['room_type_id'])) {
            $roomType = RoomType::query()->findOrFail((int) $validated['room_type_id']);
        } elseif (! empty($validated['room_id'])) {
            $room = Room::with('roomType')->findOrFail((int) $validated['room_id']);
            if ($room->isInMaintenance()) {
                abort(404);
            }
            $roomType = $room->roomType;
        }

        if (! $roomType) {
            return back()->withErrors('Loại phòng không hợp lệ.');
        }

        return view('bookings.create-simple', [
            'roomType' => $roomType,
            'checkIn' => $validated['check_in'],
            'checkOut' => $validated['check_out'],
        ]);
    }

    /**
     * Xử lý lưu đặt phòng đơn giản
     */
    public function storeSimple(\App\Http\Requests\StoreSimpleBookingRequest $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        try {
            $validated = $request->validated();

            DB::beginTransaction();

            // 1. Tính giá theo loại phòng (không gán số phòng trước check-in)
            $checkIn = \Carbon\Carbon::parse($validated['check_in']);
            $checkOut = \Carbon\Carbon::parse($validated['check_out']);
            $nights = max(1, $checkIn->diffInDays($checkOut));
            $roomType = RoomType::query()->findOrFail((int) $validated['room_type_id']);
            $pricingRoom = Room::query()
                ->where('room_type_id', $roomType->id)
                ->where('status', 'available')
                ->excludeMaintenance()
                ->orderBy('id')
                ->first()
                ?? Room::firstCatalogueRoomForRoomType((int) $roomType->id);
            if (! $pricingRoom) {
                throw new \RuntimeException('Loại phòng đã chọn không có phòng hợp lệ (tránh phòng bảo trì) để báo giá.');
            }

            $dates = [];
            foreach (CarbonPeriod::create($checkIn, $checkOut->copy()->subDay()) as $date) {
                $dates[] = $date->toDateString();
            }

            Room::query()
                ->where('room_type_id', (int) $roomType->id)
                ->where('status', 'available')
                ->excludeMaintenance()
                ->lockForUpdate()
                ->pluck('id');

            $bookedRoomIds = \App\Models\RoomBookedDate::query()
                ->whereIn('booked_date', $dates)
                ->pluck('room_id')
                ->unique()
                ->toArray();
            $physicalAvailable = Room::query()
                ->where('room_type_id', $roomType->id)
                ->where('status', 'available')
                ->excludeMaintenance()
                ->whereNotIn('id', $bookedRoomIds)
                ->count();

            $roomsCount = $validated['rooms'];
            $unassigned = BookingRoom::unassignedCountForRoomTypeBetween(
                (int) $roomType->id,
                $checkIn->toDateString(),
                $checkOut->toDateString()
            );
            if (($physicalAvailable - $unassigned) < $roomsCount) {
                DB::rollBack();
                return back()->withErrors('Không đủ phòng trống theo loại đã chọn trong khoảng thời gian này.')->withInput();
            }
            $pricePerNight = (float) $pricingRoom->catalogueBasePrice();
            $roomSubtotal = $pricePerNight * $nights;

            $booking = Booking::create([
                'user_id'     => $user?->id,
                'check_in'    => $validated['check_in'],
                'check_out'   => $validated['check_out'],
                'room_id'     => null,
                'adults'      => $roomsCount,
                'total_price' => $roomSubtotal * $roomsCount,
                'status'      => 'pending',
                'payment_status' => 'pending',
                'payment_method' => 'vnpay',
                'placed_via' => Booking::PLACED_VIA_CUSTOMER_WEB,
            ]);

            for ($i = 0; $i < $roomsCount; $i++) {
                $booking->bookingRooms()->create([
                    'room_type_id' => $roomType->id,
                    'room_id' => null,
                    'adults' => 1,
                    'children_0_5' => 0,
                    'children_6_11' => 0,
                    'price_per_night' => $pricePerNight,
                    'nights' => $nights,
                    'subtotal' => $roomSubtotal,
                ]);
            }

            // 3. Tạo 1 guest duy nhất - người đại diện
            Guest::create([
                'booking_id'       => $booking->id,
                'name'             => $validated['name'],
                'cccd'             => $validated['cccd'],
                'type'             => 'adult',
                'is_representative'=> 1,
                'checkin_status'   => 'pending',
            ]);

            DB::commit();

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Đặt phòng thành công! Vui lòng thanh toán để hoàn tất.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Simple booking error: ' . $e->getMessage());
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }
}



