<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPaymentIntent;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\BookingLog;
use App\Support\BookingInvoiceViewData;
use App\Services\VnPayService;
use App\Services\BookingService;
use App\Services\RefundService;
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

        $canCustomerRefundOnDetail = $isOwner || $signedOk;
        $refundFormUrl = null;
        if ($canCustomerRefundOnDetail) {
            $expires = now()->addDays(max(1, (int) config('booking.signed_booking_show_ttl_days', 90)));
            $refundFormUrl = $signedOk && ! $isOwner
                ? URL::temporarySignedRoute('bookings.refund', $expires, ['booking' => $booking->id], true)
                : route('bookings.refund', $booking, true);
        }

        return view(
            'account.booking-show',
            compact('booking', 'guestPortalUser', 'guestPortalIndexUrl', 'guestPortalInvoiceUrl', 'canCustomerRefundOnDetail', 'refundFormUrl')
        );
    }

    /**
     * Form yêu cầu hoàn tiền: chủ đơn đăng nhập hoặc khách mở link có chữ ký (cùng quy tắc với chi tiết đơn).
     */
    public function refundForm(Request $request, Booking $booking, RefundService $refundService)
    {
        $booking->load(['payment', 'refundRequest']);

        $isOwner = Auth::check() && (int) Auth::id() === (int) $booking->user_id;
        $signedOk = $this->hasValidSignedAccess($request);
        if (! $isOwner && ! $signedOk) {
            abort(403, 'Bạn không có quyền truy cập. Đăng nhập tài khoản đặt phòng hoặc dùng link được gửi trong email.');
        }

        $eligibility = $refundService->canCustomerRequestRefund($booking, (int) $booking->user_id);
        if (! ($eligibility['allowed'] ?? false)) {
            return redirect()->to($this->customerBookingShowUrl($booking, $isOwner, $request))
                ->with('error', $eligibility['message'] ?? 'Đơn chưa đủ điều kiện yêu cầu hoàn tiền.');
        }

        $calc = $eligibility['calc'];
        $latestRefundRequest = $eligibility['existing'] ?? null;

        $expires = now()->addDays(max(1, (int) config('booking.signed_booking_show_ttl_days', 90)));
        $refundPostUrl = $signedOk && ! $isOwner
            ? URL::temporarySignedRoute('bookings.refund.submit', $expires, ['booking' => $booking->id], true)
            : route('bookings.refund.submit', $booking, true);
        $refundBookingShowUrl = $this->customerBookingShowUrl($booking, $isOwner, $request);
        $refundNavIsGuest = $signedOk && ! $isOwner;
        $guestPortalIndexUrl = null;
        if ($refundNavIsGuest) {
            $portal = User::query()->find((int) $booking->user_id);
            if ($portal && ! $portal->canAccessAdmin()) {
                $guestPortalIndexUrl = URL::temporarySignedRoute('guest.bookings.index', $expires, ['user' => $portal->id], true);
            }
        }

        return view('account.refund', compact(
            'booking',
            'calc',
            'latestRefundRequest',
            'refundPostUrl',
            'refundBookingShowUrl',
            'refundNavIsGuest',
            'guestPortalIndexUrl'
        ));
    }

    public function submitRefund(Request $request, Booking $booking, RefundService $refundService)
    {
        $isOwner = Auth::check() && (int) Auth::id() === (int) $booking->user_id;
        $signedOk = $this->hasValidSignedAccess($request);
        if (! $isOwner && ! $signedOk) {
            abort(403);
        }

        $eligibility = $refundService->canCustomerRequestRefund($booking, (int) $booking->user_id);
        if (! ($eligibility['allowed'] ?? false)) {
            return back()->with('error', $eligibility['message'] ?? 'Không đủ điều kiện hoàn tiền.');
        }

        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'bank_name' => 'required|string|max:255',
            'qr_image' => 'nullable|image|max:2048',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('qr_image')) {
            $validated['qr_image'] = $request->file('qr_image')->store('refunds', 'public');
        }

        $result = $refundService->submitCustomerRefundRequest($booking, (int) $booking->user_id, $validated);
        if (! $result['success']) {
            return back()->with('error', $result['message'] ?? 'Không thể gửi yêu cầu.');
        }

        return redirect()->to($this->customerBookingShowUrl($booking, $isOwner, $request))
            ->with('success', 'Yêu cầu hoàn tiền của bạn đã được gửi và đang chờ xử lý.');
    }

    private function customerBookingShowUrl(Booking $booking, bool $isOwner, ?Request $request = null): string
    {
        if ($isOwner) {
            return route('bookings.show', $booking, true);
        }

        $portalUserId = (int) $booking->user_id;
        if ($request) {
            $q = (int) $request->query('portal_user', 0);
            if ($q > 0 && $q === (int) $booking->user_id) {
                $portalUserId = $q;
            }
        }

        return URL::temporarySignedRoute(
            'bookings.show',
            now()->addDays(max(1, (int) config('booking.signed_booking_show_ttl_days', 90))),
            ['booking' => $booking->id, 'portal_user' => $portalUserId],
            true
        );
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
            $ctx = $this->bookingService->composeCheckoutContext($validated);
            $amountVnd = (int) round((float) $ctx['totalPrice']);

            $expireMinutes = max(5, min(1440, (int) config('vnpay.transaction_expire_minutes', 30)));
            $intent = BookingPaymentIntent::query()->create([
                'payload' => $validated,
                'amount_vnd' => $amountVnd,
                'expires_at' => now()->addMinutes($expireMinutes + 2),
            ]);

            $vnPayService = app(VnPayService::class);
            $returnUrl = route('payment.vnpay.return');
            $orderInfo = 'Dat phong Light Hotel phien #' . $intent->id;
            $txnRef = $intent->vnPayTxnRef();
            $bankCode = $request->input('bank_code') ?: null;

            $paymentUrl = $vnPayService->createPaymentUrl(
                $txnRef,
                $amountVnd,
                $orderInfo,
                $returnUrl,
                $request->ip(),
                'vn',
                $bankCode
            );

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
        if ($user && $user->canAccessAdmin()) {
            return back()->withErrors('Tài khoản nhân viên/quản trị không thể đặt phòng trên giao diện khách.')->withInput();
        }

        try {
            $validated = $request->validated();

            $roomTypeId = (int) $validated['room_type_id'];
            $qty = (int) $validated['rooms'];
            $roomTypeIds = array_fill(0, $qty, $roomTypeId);

            $asStoreBookingPayload = [
                'room_type_ids' => $roomTypeIds,
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'payment_method' => 'vnpay',
                'coupon_code' => null,
                'adults' => array_fill(0, $qty, 1),
                'children_0_5' => array_fill(0, $qty, 0),
                'children_6_11' => array_fill(0, $qty, 0),
                'name' => $validated['name'],
                'cccd' => $validated['cccd'],
                'bank_code' => null,
            ];

            $ctx = $this->bookingService->composeCheckoutContext($asStoreBookingPayload);
            $amountVnd = (int) round((float) $ctx['totalPrice']);

            $expireMinutes = max(5, min(1440, (int) config('vnpay.transaction_expire_minutes', 30)));
            $intent = BookingPaymentIntent::query()->create([
                'payload' => $asStoreBookingPayload,
                'amount_vnd' => $amountVnd,
                'expires_at' => now()->addMinutes($expireMinutes + 2),
            ]);

            $vnPayService = app(VnPayService::class);
            $paymentUrl = $vnPayService->createPaymentUrl(
                $intent->vnPayTxnRef(),
                $amountVnd,
                'Dat phong Light Hotel phien #' . $intent->id,
                route('payment.vnpay.return'),
                $request->ip(),
                'vn',
                null
            );

            return $vnPayService->redirectAwayNoCache($paymentUrl);

        } catch (BookingException $e) {
            return back()->withErrors($e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Simple booking error: ' . $e->getMessage());
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }
}



