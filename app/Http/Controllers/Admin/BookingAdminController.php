<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentInstructionDelivery;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\BookingGuest;
use App\Models\BookingLog;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\RoomPrice;
use App\Models\User;
use App\Models\BookingService as BookingServiceRow;
use App\Http\Requests\RoomChangeRequest;
use App\Services\RoomChangeService;
use App\Services\UnpaidBookingObliterateService;
use App\Support\BookingActivityTimeline;
use App\Support\BookingCatalogServices;
use App\Support\BookingFinancialAudit;
use App\Support\BookingInvoiceViewData;
use App\Support\InvoiceBookingSynchronizer;
use App\Support\RoomOccupancyPricing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Carbon\CarbonPeriod;

class BookingAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request): \Illuminate\View\View
    {
        if ($request->filled('status') && in_array((string) $request->status, ['cancelled', 'cancel_requested', 'pending'], true)) {
            return redirect()->route('admin.bookings.index', $request->except('status'));
        }

        $countsRow = Booking::query()
            ->toBase()
            ->selectRaw("SUM(CASE WHEN status NOT IN ('cancelled','cancel_requested') THEN 1 ELSE 0 END) as total_count")
            ->selectRaw("SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count")
            ->selectRaw("SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in_count")
            ->first();

        $counts = [
            'total' => (int) ($countsRow->total_count ?? 0),
            'confirmed' => (int) ($countsRow->confirmed_count ?? 0),
            'checked_in' => (int) ($countsRow->checked_in_count ?? 0),
        ];

        $query = Booking::with([
            'user',
            'room',
            'rooms',
            'bookingRooms.room.roomType',
            'bookingRooms.roomType',
            'latestPayment',
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereNotIn('status', ['cancelled', 'cancel_requested']);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('name', 'like', "%{$q}%"))
                    ->orWhere('id', 'like', "%{$q}%")
                    ->orWhereHas('payments', fn ($p) => $p->where('transaction_id', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('check_in_from')) {
            $query->whereDate('check_in', '>=', $request->check_in_from);
        }

        if ($request->filled('check_in_to')) {
            $query->whereDate('check_in', '<=', $request->check_in_to);
        }

        if ($request->filled('check_out_from')) {
            $query->whereDate('check_out', '>=', $request->check_out_from);
        }

        if ($request->filled('check_out_to')) {
            $query->whereDate('check_out', '<=', $request->check_out_to);
        }

        $bookings = $query->paginate(15)->withQueryString();
        return view('admin.bookings.index', compact('bookings', 'counts'));
    }

    public function show(Booking $booking): \Illuminate\View\View
    {
        $booking->load([
            'user',
            'room',
            'payment',
            'logs.user',
            'bookingServices.service',
            'surcharges.service',
            'bookingRooms.room.roomType',
            'bookingGuests.bookingRoom.room',
            'guests',
            'payments',
        ]);
        $latestPayment = $booking->payments->first();
        $booking->reconcilePaymentStatusWithPayments();
        $services = BookingCatalogServices::forBooking($booking);
        $bookingSvcCatalogNotice = BookingCatalogServices::resolveNoticeForBooking($booking);
        $bookingActivity = BookingActivityTimeline::forBooking($booking);

        return view('admin.bookings.show', compact('booking', 'latestPayment', 'services', 'bookingSvcCatalogNotice', 'bookingActivity'));
    }

    /**
     * Báo giá gia hạn lưu trú (JSON): mỗi dòng booking_room × số đêm thêm, giá theo bảng RoomPrice + phụ phí occupancy (đồng bộ đặt phòng).
     */
    public function extendStayQuote(Request $request, Booking $booking): JsonResponse
    {
        $booking->loadMissing([
            'bookingGuests',
            'guests',
            'bookingRooms.room.roomType',
            'bookingRooms.roomType',
            'bookingServices',
            'surcharges',
        ]);

        try {
            $this->assertBookingAllowsExtension($booking);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $validated = $request->validate([
            'new_check_out' => 'required|date|after:'.$booking->check_out->format('Y-m-d'),
        ]);

        try {
            $payload = $this->buildStayExtensionPayload($booking, Carbon::parse($validated['new_check_out'])->startOfDay());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true] + $payload);
    }

    /**
     * Gia hạn ngày trả phòng: cập nhật giá từng phòng, room_booked_dates, tổng đơn (trừ coupon), đồng bộ thanh toán / HĐ nếu có.
     */
    public function extendStay(Request $request, Booking $booking): RedirectResponse
    {
        $booking->loadMissing([
            'bookingGuests',
            'guests',
            'bookingRooms.room.roomType',
            'bookingRooms.roomType',
            'bookingServices',
            'surcharges',
        ]);

        try {
            $this->assertBookingAllowsExtension($booking);
        } catch (\Throwable $e) {
            return back()->withErrors(['extend' => $e->getMessage()]);
        }

        $validated = $request->validate([
            'new_check_out' => 'required|date|after:'.$booking->check_out->format('Y-m-d'),
        ]);

        $newCheckOut = Carbon::parse($validated['new_check_out'])->startOfDay();

        try {
            $payload = $this->buildStayExtensionPayload($booking, $newCheckOut);
        } catch (\Throwable $e) {
            return back()->withErrors(['extend' => $e->getMessage()]);
        }

        if (! empty($payload['has_availability_conflict'])) {
            return back()->withErrors([
                'extend' => 'Không thể gia hạn: một hoặc nhiều phòng đã có đặt chỗ trùng các đêm gia hạn.',
            ]);
        }

        $invoiceNote = '';

        $booking->refresh();
        $extendSnapshotBefore = BookingFinancialAudit::financialSnapshot($booking);

        DB::beginTransaction();
        try {
            $oldCheckOut = Carbon::parse($booking->check_out)->startOfDay();
            $effectiveStayStart = $this->effectiveStayStartDate($booking)->startOfDay();

            foreach ($booking->bookingRooms as $br) {
                $line = $payload['lines_by_booking_room_id'][$br->id] ?? null;
                if (! $line) {
                    throw new \RuntimeException('Thiếu dòng giá cho booking_room #'.$br->id);
                }

                $addAmount = (float) $line['extension_amount'];
                $extNights = (int) $payload['extension_nights'];

                $priorNightsOnLine = (int) ($br->nights ?? 0);
                if ($priorNightsOnLine < 1) {
                    $priorNightsOnLine = max(1, $this->stayNightsBetween($effectiveStayStart, $oldCheckOut));
                }

                $newTotalNights = $priorNightsOnLine + $extNights;
                $roomSubtotal = (float) $br->subtotal;
                $pricePerNightBase = $priorNightsOnLine > 0 ? round($roomSubtotal / $priorNightsOnLine, 2) : (float) $br->price_per_night;

                $br->update([
                    'nights' => $newTotalNights,
                    'price_per_night' => $pricePerNightBase,
                ]);

                $dates = $line['extension_dates'] ?? [];
                $firstExt = $dates !== [] ? $dates[0] : $oldCheckOut->toDateString();
                $lastExt = $dates !== [] ? $dates[count($dates) - 1] : $newCheckOut->copy()->subDay()->toDateString();

                $booking->surcharges()->create([
                    'service_id' => null,
                    'reason' => sprintf(
                        'Gia hạn lưu trú (%d đêm, %s → %s): %s',
                        $extNights,
                        Carbon::parse($firstExt)->format('d/m/Y'),
                        Carbon::parse($lastExt)->format('d/m/Y'),
                        $line['label']
                    ),
                    'quantity' => 1,
                    'amount' => round($addAmount, 2),
                ]);

                if ($br->room_id) {
                    foreach ($line['extension_dates'] as $dateStr) {
                        RoomBookedDate::firstOrCreate([
                            'room_id' => (int) $br->room_id,
                            'booked_date' => $dateStr,
                            'booking_id' => $booking->id,
                        ]);
                    }
                }
            }

            $booking->update([
                'check_out' => $newCheckOut->toDateString(),
            ]);

            $booking->refresh()->load(['bookingRooms', 'bookingServices', 'surcharges']);

            $this->applyBookingGrandTotalFromRoomParts($booking);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'old_status' => $booking->status,
                'new_status' => $booking->status,
                'notes' => 'Gia hạn lưu trú: trả phòng '.$oldCheckOut->format('d/m/Y').' → '.$newCheckOut->format('d/m/Y').' (+'.($payload['extension_nights'] ?? 0).' đêm). Đã ghi '.count($payload['lines'] ?? []).' dòng phụ phí gia hạn (tổng '.number_format((float) ($payload['extension_room_total'] ?? 0), 0, ',', '.').' ₫). Giá theo nhận phòng & bảng giá từng đêm.',
                'changed_at' => now(),
            ]);

            $this->updatePaymentAmount($booking);

            DB::commit();

            $bookingFresh = $booking->fresh();
            if ($bookingFresh) {
                BookingFinancialAudit::record($bookingFresh, 'extend_stay', [
                    'snapshot_before' => $extendSnapshotBefore,
                    'new_check_out' => $newCheckOut->toDateString(),
                    'extension_nights' => $payload['extension_nights'] ?? null,
                    'extension_room_total' => $payload['extension_room_total'] ?? null,
                ], Auth::id());
            }

            $invoiceNote = $this->syncInvoiceExtrasIfExists($booking->fresh(['invoice']));
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors(['extend' => 'Gia hạn thất bại: '.$e->getMessage()]);
        }

        return redirect()
            ->route('admin.bookings.show', $booking->fresh())
            ->with('success', 'Đã gia hạn lưu trú: ghi phụ phí gia hạn (giá theo nhận phòng & từng đêm), cập nhật ngày trả và lịch phòng.'.$invoiceNote);
    }

    private function assertBookingAllowsExtension(Booking $booking): void
    {
        if (in_array($booking->status, ['cancelled', 'completed', 'cancel_requested'], true)) {
            throw new \InvalidArgumentException('Không gia hạn được đơn đã hủy / hoàn tất.');
        }
        if ($booking->actual_check_out !== null) {
            throw new \InvalidArgumentException('Không gia hạn được đơn đã check-out.');
        }
        if ($booking->bookingRooms->isEmpty()) {
            throw new \InvalidArgumentException('Đơn không có dòng phòng (booking_rooms) để tính giá gia hạn.');
        }
    }

    /**
     * @return array{
     *   extension_nights:int,
     *   extension_room_total:float,
     *   new_grand_total:float,
     *   has_availability_conflict:bool,
     *   lines: list<array<string,mixed>>,
     *   lines_by_booking_room_id: array<int,array<string,mixed>>
     * }
     */
    private function buildStayExtensionPayload(Booking $booking, Carbon $newCheckOut): array
    {
        $booking->loadMissing(['bookingGuests', 'guests', 'bookingRooms.room.roomType', 'bookingRooms.roomType']);

        $oldCheckOut = Carbon::parse($booking->check_out)->startOfDay();
        if ($newCheckOut->lte($oldCheckOut)) {
            throw new \InvalidArgumentException('Ngày trả phòng mới phải sau ngày trả hiện tại.');
        }

        $extensionNights = (int) $oldCheckOut->diffInDays($newCheckOut);
        if ($extensionNights < 1) {
            throw new \InvalidArgumentException('Số đêm gia hạn không hợp lệ.');
        }

        $lines = [];
        $linesById = [];
        $extensionRoomTotal = 0.0;
        $hasConflict = false;

        foreach ($booking->bookingRooms as $br) {
            $room = $this->resolveRoomForBookingRoomPricing($br);
            $occ = $booking->occupancyDisplayCountsForBookingRoom($br);

            RoomOccupancyPricing::validate(
                (int) $occ['adults'],
                (int) $occ['children_6_11'],
                (int) $occ['children_0_5'],
                $room->roomType
            );

            $amount = $this->calculateTotalPriceWithSurcharge(
                $room,
                $oldCheckOut,
                $newCheckOut,
                (int) $occ['adults'],
                (int) $occ['children_6_11'],
                (int) $occ['children_0_5']
            );

            $extensionDates = [];
            $period = CarbonPeriod::create($oldCheckOut, $newCheckOut->copy()->subDay());
            foreach ($period as $date) {
                $extensionDates[] = $date->toDateString();
            }

            $blocked = false;
            if ($br->room_id) {
                $blocked = $this->isRoomOccupied((int) $br->room_id, $booking->id, $oldCheckOut->toDateString(), $newCheckOut->toDateString());
                if ($blocked) {
                    $hasConflict = true;
                }
            }

            $extensionRoomTotal += $amount;

            $line = [
                'booking_room_id' => $br->id,
                'label' => $br->guestFacingLine(),
                'room_id' => $br->room_id,
                'adults' => (int) $occ['adults'],
                'children_6_11' => (int) $occ['children_6_11'],
                'children_0_5' => (int) $occ['children_0_5'],
                'extension_nights' => $extensionNights,
                'extension_amount' => round($amount, 2),
                'extension_dates' => $extensionDates,
                'availability_blocked' => $blocked,
                'pricing_room_used' => $room->displayLabel(),
            ];
            $lines[] = $line;
            $linesById[$br->id] = $line;
        }

        $currentRoomSum = (float) $booking->bookingRooms->sum('subtotal');
        $servicesTotal = $this->calculateBookingServicesTotal($booking);
        $surchargesTotal = (float) $booking->surcharges()->sum('amount');
        $discount = (float) ($booking->discount_amount ?? 0);
        $newGrand = max(0.0, $currentRoomSum + $extensionRoomTotal + $servicesTotal + $surchargesTotal - $discount);

        return [
            'extension_nights' => $extensionNights,
            'extension_room_total' => round($extensionRoomTotal, 2),
            'new_grand_total' => round($newGrand, 2),
            'has_availability_conflict' => $hasConflict,
            'lines' => $lines,
            'lines_by_booking_room_id' => $linesById,
            'old_check_out' => $oldCheckOut->toDateString(),
            'new_check_out' => $newCheckOut->toDateString(),
        ];
    }

    /**
     * Ngày bắt đầu lưu trú thực tế cho nghiệp vụ (gia hạn, đêm đã ở): actual_check_in nếu có, không thì ngày nhận phòng trên đơn.
     */
    private function effectiveStayStartDate(Booking $booking): Carbon
    {
        if ($booking->actual_check_in) {
            return Carbon::parse($booking->actual_check_in)->startOfDay();
        }

        return Carbon::parse($booking->check_in)->startOfDay();
    }

    private function resolveRoomForBookingRoomPricing(BookingRoom $br): Room
    {
        if ($br->room_id) {
            $r = Room::with('roomType')->find($br->room_id);
            if ($r) {
                return $r;
            }
        }
        if ($br->room_type_id) {
            $fallback = Room::firstCatalogueRoomForRoomType((int) $br->room_type_id);
            if ($fallback) {
                return $fallback->loadMissing('roomType');
            }
        }

        throw new \RuntimeException('Không xác định được phòng để tính giá gia hạn (thiếu room_id / room_type_id).');
    }

    private function stayNightsBetween($checkIn, Carbon $checkOutExclusive): int
    {
        $ci = Carbon::parse($checkIn)->startOfDay();

        return max(1, (int) $ci->diffInDays($checkOutExclusive));
    }

    /**
     * Đồng bộ bookings.total_price với tổng booking_rooms + dịch vụ + phụ phí − coupon (đúng với biểu thức trên trang chi tiết đơn).
     */
    private function applyBookingGrandTotalFromRoomParts(Booking $booking): void
    {
        $booking->loadMissing(['bookingServices', 'surcharges']);
        $roomSum = (float) $booking->bookingRooms()->sum('subtotal');
        $servicesTotal = $this->calculateBookingServicesTotal($booking);
        $surchargesTotal = (float) $booking->surcharges()->sum('amount');
        $discount = (float) ($booking->discount_amount ?? 0);

        $booking->total_price = max(0.0, $roomSum + $servicesTotal + $surchargesTotal - $discount);
        $booking->save();
    }

    /** Biên lai / hóa đơn tóm tắt — chỉ khi đã thanh toán và đã checkout. */
    public function bookingInvoice(Booking $booking): \Illuminate\Http\RedirectResponse|\Illuminate\View\View
    {
        if (! BookingInvoiceViewData::customerCanView($booking)) {
            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('error', 'Chỉ xem biên lai khi đơn đã checkout và đã thanh toán.');
        }

        return view('admin.bookings.invoice', BookingInvoiceViewData::make($booking));
    }


    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'room_type_id' => 'nullable|exists:room_types,id',
            'room_id' => 'nullable|exists:rooms,id',
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1|max:6',
            'children_6_11' => 'nullable|integer|min:0|max:5',
            'children_0_5' => 'nullable|integer|min:0|max:2',
            'status' => 'required|in:pending,confirmed',
            'payment_method' => 'required|in:cash,vnpay',
            'payment_status' => 'required|in:pending,paid,partial',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_note' => 'nullable|string|max:500',
            'guests_json' => 'nullable|json',
            'representative_name' => 'required|string|max:150',
            'representative_cccd' => 'required|string|regex:/^[0-9]{12}$/',
        ]);

        $guestsArray = json_decode($validated['guests_json'], true);

        if (!is_array($guestsArray) || empty($guestsArray)) {
            return back()->withErrors(['guests_json' => 'Vui lòng nhập thông tin khách hàng.'])->withInput();
        }

        foreach ($guestsArray as $index => $guest) {
            if (empty($guest['name'])) {
                return back()->withErrors(["guests.{$index}.name" => "Tên khách hàng không được để trống."])->withInput();
            }
        }

        if (empty($validated['room_type_id']) && empty($validated['room_id'])) {
            return back()->withErrors(['room_type_id' => 'Vui lòng chọn loại phòng.'])->withInput();
        }

        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);
        $winStart = $checkIn->toDateString();
        $winEnd = $checkOut->toDateString();

        $pricingRoom = null;
        $roomTypeId = null;
        if (! empty($validated['room_type_id'])) {
            $roomTypeId = (int) $validated['room_type_id'];
            $pricingRoom = Room::query()
                ->where('room_type_id', $roomTypeId)
                ->vacantForGuestBookingWindow($winStart, $winEnd)
                ->orderBy('id')
                ->first()
                ?? Room::firstCatalogueRoomForRoomType($roomTypeId);
        } else {
            $selectedRoom = Room::findOrFail((int) $validated['room_id']);
            if ($selectedRoom->isInMaintenance()) {
                return back()->withErrors(['room_id' => 'Không thể đặt phòng đang bảo trì.'])->withInput();
            }
            if (! Room::query()->whereKey($selectedRoom->id)->vacantForGuestBookingWindow($winStart, $winEnd)->exists()) {
                return back()->withErrors(['room_id' => 'Phòng đã chọn đang có khách hoặc không còn trống trong khoảng ngày này.'])->withInput();
            }
            $roomTypeId = (int) $selectedRoom->room_type_id;
            $pricingRoom = $selectedRoom;
        }

        if (! $pricingRoom || ! $roomTypeId) {
            return back()->withErrors(['room_type_id' => 'Loại phòng không hợp lệ hoặc chưa có phòng mẫu để báo giá.'])->withInput();
        }

        $adults = $validated['adults'];
        $children611 = $validated['children_6_11'] ?? 0;
        $children05 = $validated['children_0_5'] ?? 0;
        $totalGuests = $adults + $children611 + $children05;

        $paymentMethod = $validated['payment_method'];
        $paymentPaid = in_array($validated['payment_status'], ['paid', 'partial'], true);
        $deferRooms = ($paymentMethod === 'vnpay' && ! $paymentPaid);

        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = collect();
        foreach ($period as $date) {
            $dates->push($date->toDateString());
        }

        if (! $deferRooms) {
            $physicalAvailable = Room::query()
                ->where('room_type_id', $roomTypeId)
                ->vacantForGuestBookingWindow($winStart, $winEnd)
                ->count();
            $unassigned = BookingRoom::unassignedCountForRoomTypeBetween(
                $roomTypeId,
                $checkIn->toDateString(),
                $checkOut->toDateString()
            );
            if (($physicalAvailable - $unassigned) < 1) {
                return back()->withErrors(['check_in' => 'Loại phòng đã chọn không còn đủ chỗ trống trong khoảng thời gian này.'])->withInput();
            }
        }

        $totalPrice = $this->calculateTotalPriceWithSurcharge($pricingRoom, $checkIn, $checkOut, $adults, $children611, $children05);

        DB::beginTransaction();
        try {
            // Find or create user (đồng bộ họ tên / SĐT khi admin đặt hộ hoặc khách đã có tài khoản shadow)
            $guestEmail = Str::lower(trim((string) $validated['email']));
            $user = User::firstOrCreate(
                ['email' => $guestEmail],
                [
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => bcrypt(Str::random(12)),
                ]
            );
            $user->forceFill([
                'email' => $guestEmail,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'] ?? $user->phone,
            ])->save();

            $nights = max(1, $checkIn->diffInDays($checkOut));

            // Create booking (không gán số phòng vật lý trước check-in)
            $booking = Booking::create([
                'user_id' => $user->id,
                'room_id' => null,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $totalGuests,
                'adults' => $adults,
                'children' => $children611 + $children05,
                'total_price' => $totalPrice,
                'status' => $validated['status'],
                'payment_status' => $paymentPaid ? 'paid' : 'pending',
                'payment_method' => $paymentMethod,
                'placed_via' => Booking::PLACED_VIA_ADMIN,
            ]);

            if ($deferRooms) {
                $booking->pending_checkout_payload = [
                    'mode' => 'single',
                    'dates' => $dates->all(),
                    'room_type_id' => $roomTypeId,
                    'nights' => $nights,
                    'price_per_night' => round($totalPrice / $nights, 2),
                    'subtotal' => $totalPrice,
                    'adults' => $adults,
                    'children_0_5' => $children05,
                    'children_6_11' => $children611,
                ];
                $booking->save();
            } else {
                // Chỉ giữ loại phòng ở bước đặt; số phòng do lễ tân chốt khi check-in.
                $booking->bookingRooms()->create([
                    'room_type_id' => $roomTypeId,
                    'room_id' => null,
                    'price_per_night' => round($totalPrice / $nights, 2),
                    'nights' => $nights,
                    'subtotal' => $totalPrice,
                    'adults' => $adults,
                    'children_0_5' => $children05,
                    'children_6_11' => $children611,
                ]);
            }

            // Log
            BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'new',
                'new_status' => $booking->status,
                'changed_at' => now(),
            ]);

            // Create payment record
            try {
                $ps = $validated['payment_status'];
                $payAmount = $paymentPaid
                    ? (float) ($validated['amount_paid'] ?? $totalPrice)
                    : ($paymentMethod === 'vnpay' ? (float) $totalPrice : (float) ($validated['amount_paid'] ?? 0));

                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $payAmount,
                    'method' => $paymentMethod,
                    'status' => $paymentPaid ? 'paid' : 'pending',
                    'transaction_id' => 'ADM' . time() . rand(1000, 9999),
                    'paid_at' => $paymentPaid ? now() : null,
                ]);

                // Nếu thanh toán qua VNPay, gửi email cho khách
                if ($paymentMethod === 'vnpay' && $ps === 'pending') {
                    $this->sendVnPayPaymentEmail($booking, $adults, $children611, $children05);
                }
            } catch (\Exception $e) {
                // Continue even if payment creation fails
            }

            // Save guest information
            Log::info('Processing guest data for booking ' . $booking->id, [
                'guests_data' => $guestsArray,
                'is_array' => is_array($guestsArray),
                'count' => count($guestsArray)
            ]);

            if (is_array($guestsArray) && count($guestsArray) > 0) {
                foreach ($guestsArray as $index => $guestData) {
                    Log::info("Creating guest {$index}", [
                        'name' => $guestData['name'] ?? 'MISSING',
                        'cccd' => $guestData['cccd'] ?? 'MISSING',
                    ]);

                    // Determine guest type based on index and counts
                    $guestType = 'adult';
                    if ($index >= $adults) {
                        $guestType = ($index < $adults + $children611) ? 'child_6_11' : 'child_0_5';
                    }

                    // Guest 0 is the representative
                    $isRepresentative = $index === 0;
                    $guestName = $isRepresentative ? $validated['representative_name'] : ($guestData['name'] ?? '');
                    $guestCccd = $isRepresentative ? $validated['representative_cccd'] : ($guestData['cccd'] ?? null);

                    BookingGuest::create(BookingGuest::filterAttributesForStorage([
                        'booking_id' => $booking->id,
                        'name' => $guestName,
                        'cccd' => $guestCccd,
                        'type' => BookingGuest::normalizeTypeForStorage($guestType),
                        'status' => 'pending',
                        'is_representative' => $isRepresentative ? 1 : 0,
                    ]));
                }
            } else {
                Log::warning('No guest data found in request for booking ' . $booking->id);
            }

            DB::commit();

            return redirect()->route('admin.bookings.show', $booking)->with('success', 'Tạo đơn đặt phòng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    protected function calculateTotalPrice(Room $room, \Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut): float
    {
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $prices = RoomPrice::where('room_id', $room->id)->get();

        $total = 0;
        foreach ($period as $date) {
            $priceForDate = $room->catalogueBasePrice();

            foreach ($prices as $price) {
                if ($date->betweenIncluded($price->start_date, $price->end_date)) {
                    $priceForDate = $price->price;
                    break;
                }
            }

            $total += (float) $priceForDate;
        }

        return $total;
    }

    /**
     * Tính tổng giá với phụ phí số khách vượt sức chứa tiêu chuẩn
     */
    protected function calculateTotalPriceWithSurcharge(
        Room $room,
        \Carbon\Carbon $checkIn,
        \Carbon\Carbon $checkOut,
        int $adults,
        int $children611 = 0,
        int $children05 = 0
    ): float {
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $prices = RoomPrice::where('room_id', $room->id)->get();
        $room->loadMissing('roomType');
        $roomType = $room->roomType;

        RoomOccupancyPricing::validate($adults, $children611, $children05, $roomType);

        $total = 0.0;
        foreach ($period as $date) {
            $basePrice = (float) $room->catalogueBasePrice();

            foreach ($prices as $price) {
                if ($date->betweenIncluded($price->start_date, $price->end_date)) {
                    $basePrice = (float) $price->price;
                    break;
                }
            }

            $breakdown = RoomOccupancyPricing::breakdown($basePrice, $adults, $children611, $children05, $roomType);
            $total += (float) $breakdown['price_per_night'];
        }

        return round($total, 2);
    }

    public function edit(Booking $booking): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.bookings.show', $booking);
    }

    public function update(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'check_in'    => 'required|date',
            'check_out'   => 'required|date|after:check_in',
            'status'      => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $old_status = $booking->status;

        DB::beginTransaction();
        try {
            if ($validated['status'] === 'cancelled') {
                $obliterator = app(UnpaidBookingObliterateService::class);
                if ($obliterator->obliterateIfUnpaid($booking)) {
                    DB::commit();

                    return redirect()->route('admin.bookings.index')
                        ->with('success', 'Đơn chưa thanh toán đã được xóa khỏi hệ thống (không lưu trạng thái hủy).');
                }
            }

            $newCheckIn  = new \Carbon\Carbon($validated['check_in']);
            $newCheckOut = new \Carbon\Carbon($validated['check_out']);

            if ($booking->check_in != $newCheckIn->format('Y-m-d') || $booking->check_out != $newCheckOut->format('Y-m-d')) {
                RoomBookedDate::where('booking_id', $booking->id)->delete();

                $period  = CarbonPeriod::create($newCheckIn, $newCheckOut->copy()->subDay());
                $roomIds = $booking->bookingRooms()
                    ->whereNotNull('room_id')
                    ->pluck('room_id')
                    ->unique()
                    ->values();

                foreach ($roomIds as $roomId) {
                    foreach ($period as $date) {
                        RoomBookedDate::firstOrCreate([
                            'room_id'     => $roomId,
                            'booked_date' => $date->toDateString(),
                            'booking_id'  => $booking->id,
                        ]);
                    }
                }
            }

            $booking->update([
                'check_in'    => $validated['check_in'],
                'check_out'   => $validated['check_out'],
                'status'      => $validated['status'],
            ]);

            if ($booking->status === 'confirmed' && $old_status === 'pending') {
                $booking->update(['payment_status' => 'paid']);
                $payment = Payment::where('booking_id', $booking->id)->orderByDesc('id')->first();
                if ($payment && $payment->status !== 'paid') {
                    $payment->update([
                        'status'         => 'paid',
                        'paid_at'        => now(),
                        'transaction_id' => $payment->transaction_id ?: strtoupper($payment->method ?? 'MANUAL') . '_' . now()->format('YmdHis') . '_' . $booking->id,
                    ]);
                }
            }

            if ($booking->status === 'cancelled') {
                RoomBookedDate::where('booking_id', $booking->id)->delete();
                Payment::where('booking_id', $booking->id)->where('status', 'pending')->update(['status' => 'failed']);
            }

            if ($old_status !== $booking->status) {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $old_status,
                    'new_status' => $booking->status,
                    'changed_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('admin.bookings.show', $booking)->with('success', 'Cập nhật đơn đặt phòng thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Booking $booking): \Illuminate\Http\RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isStaff())) {
            abort(403, 'Chỉ quản trị viên và nhân viên mới được xóa đơn đặt phòng.');
        }
        DB::beginTransaction();
        try {
            $booking->delete();

            DB::commit();
            return redirect()->route('admin.bookings.index')->with('success', 'Xóa đơn đặt phòng thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi khi xóa đơn đặt phòng. Vui lòng thử lại sau.');
        }
    }

    public function updateStatus(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        if ($request->status === 'cancelled') {
            $obliterator = app(UnpaidBookingObliterateService::class);
            if ($obliterator->obliterateIfUnpaid($booking)) {
                return redirect()->route('admin.bookings.index')
                    ->with('success', 'Đơn chưa thanh toán đã được xóa khỏi hệ thống.');
            }
        }

        $old = $booking->status;
        $booking->status = $request->status;

        if ($request->status === 'confirmed') {
            $booking->payment_status = 'paid';

            $payment = Payment::where('booking_id', $booking->id)
                ->orderByDesc('id')
                ->first();

            if ($payment && $payment->status !== 'paid') {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'transaction_id' => $payment->transaction_id
                        ?: strtoupper($payment->method ?? 'MANUAL') . '_' . now()->format('YmdHis') . '_' . $booking->id,
                ]);
            } elseif (! $payment) {
                Payment::create([
                    'booking_id'     => $booking->id,
                    'amount'         => $booking->total_price,
                    'method'         => $booking->payment_method ?? 'cash',
                    'status'         => 'paid',
                    'transaction_id' => strtoupper($booking->payment_method ?? 'CASH') . '_' . now()->format('YmdHis') . '_' . $booking->id,
                    'paid_at'        => now(),
                ]);
            }
        }

        if ($request->status === 'cancelled') {
            RoomBookedDate::where('booking_id', $booking->id)->delete();
            Payment::where('booking_id', $booking->id)->where('status', 'pending')->update(['status' => 'failed']);
        }

        $booking->save();

        BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => $booking->status,
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Cập nhật trạng thái thành công.');
    }

    /** Chỉnh trạng thái đơn + thanh toán + đồng bộ bảng payments. */
    public function updatePaymentSettings(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'booking_status' => 'required|in:pending,confirmed,cancelled,checked_in,completed',
            'payment_method' => 'required|in:cash,vnpay',
        ], [
            'booking_status.required' => 'Chọn trạng thái đơn.',
            'payment_method.required' => 'Chọn phương thức thanh toán.',
        ]);

        if (in_array((string) $booking->payment_status, ['refunded', 'partial_refunded'], true)) {
            return back()->withErrors('Đơn có hoàn tiền — không chỉnh thanh toán ở đây.');
        }
        if ($booking->status === 'cancel_requested') {
            return back()->withErrors('Đơn đang chờ xử lý hoàn tiền — vui lòng xử lý tại mục Hoàn tiền.');
        }

        if ($booking->status === 'cancelled' && $request->input('booking_status') !== 'cancelled') {
            return back()->withErrors(
                'Đơn đã hủy và ngày phòng đã mở. Không khôi phục trạng thái đặt phòng tại đây; hãy tạo đơn mới nếu khách đặt lại.'
            );
        }

        $bookingStatus = $request->input('booking_status');
        if ($booking->status === 'checked_in' && $bookingStatus !== 'checked_in') {
            return back()->withErrors(
                'Đơn đang ở trạng thái đã check-in. Không đổi tiến trình tại đây; hãy dùng thao tác check-out.'
            );
        }
        if ($booking->status === 'completed' && $bookingStatus !== 'completed') {
            return back()->withErrors(
                'Đơn đã hoàn thành. Không đổi tiến trình tại đây.'
            );
        }
        if ($bookingStatus === 'cancelled' && ! is_null($booking->actual_check_in)) {
            return back()->withErrors(
                'Đơn đã check-in thì không thể chuyển sang hủy. Vui lòng xử lý bằng check-out/hoàn tiền phù hợp.'
            );
        }
        if ($bookingStatus === 'pending' && ! is_null($booking->actual_check_in)) {
            return back()->withErrors(
                'Đơn đã check-in thì không thể trả về chờ xác nhận.'
            );
        }

        // Thanh toán được suy ra từ tiến trình đơn để tránh lệch logic.
        $paymentStatus = in_array($bookingStatus, ['confirmed', 'checked_in', 'completed'], true)
            ? 'paid'
            : 'pending';

        $booking->refresh();
        $snapshotBefore = BookingFinancialAudit::financialSnapshot($booking);

        DB::beginTransaction();
        try {
            $oldBookingStatus = $booking->status;

            if ($request->input('booking_status') === 'cancelled' && $booking->status !== 'cancelled') {
                RoomBookedDate::where('booking_id', $booking->id)->delete();
                Payment::where('booking_id', $booking->id)->where('status', 'pending')->update(['status' => 'failed']);
            }

            $booking->status = $request->input('booking_status');
            $booking->payment_status = $paymentStatus;
            $booking->payment_method = $request->input('payment_method');
            $booking->save();

            $payment = Payment::where('booking_id', $booking->id)->orderByDesc('id')->first();
            $amount = (float) $booking->total_price;

            if ($paymentStatus === 'paid') {
                $transactionId = null;
                if ($request->input('payment_method') === 'cash') {
                    $transactionId = 'CASH_' . now()->format('YmdHis') . '_' . $booking->id;
                } elseif ($payment && $payment->transaction_id && ! str_starts_with((string) $payment->transaction_id, 'CASH_')) {
                    $transactionId = $payment->transaction_id;
                } else {
                    $transactionId = 'ADMIN_PAID_' . $booking->id . '_' . time();
                }

                if ($payment) {
                    // Giao dịch đã paid = tiền thực thu — không ghi đè amount bằng total_price khi đơn phát sinh thêm phụ phí/đổi phòng.
                    $payload = [
                        'method' => $request->input('payment_method'),
                        'status' => 'paid',
                        'transaction_id' => $transactionId,
                        'paid_at' => $payment->paid_at ?? now(),
                    ];
                    if ($payment->status !== 'paid') {
                        $payload['amount'] = $amount;
                    }
                    $payment->update($payload);
                } else {
                    Payment::create([
                        'booking_id' => $booking->id,
                        'amount' => $amount,
                        'method' => $request->input('payment_method'),
                        'status' => 'paid',
                        'transaction_id' => $transactionId,
                        'paid_at' => now(),
                    ]);
                }
            } else {
                $pendingMethod = $request->input('payment_method');
                $pendingTx = 'PENDING_' . $booking->id . '_' . time();
                if ($payment) {
                    $payment->update([
                        'amount' => $amount,
                        'method' => $pendingMethod,
                        'status' => 'pending',
                        'paid_at' => null,
                        'transaction_id' => $pendingTx,
                    ]);
                } else {
                    Payment::create([
                        'booking_id' => $booking->id,
                        'amount' => $amount,
                        'method' => $pendingMethod,
                        'status' => 'pending',
                        'transaction_id' => $pendingTx,
                    ]);
                }
            }

            if ($oldBookingStatus !== $booking->status) {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $oldBookingStatus,
                    'new_status' => $booking->status,
                    'changed_at' => now(),
                ]);
            }

            DB::commit();

            $booking->refresh();
            BookingFinancialAudit::record($booking, 'payment_settings_updated', [
                'snapshot_before' => $snapshotBefore,
                'requested_booking_status' => $request->input('booking_status'),
                'derived_payment_status' => $paymentStatus,
                'payment_method' => $request->input('payment_method'),
            ], Auth::id());

            $hint = '';
            if ($request->input('payment_method') === 'cash' && $paymentStatus === 'paid') {
                $hint = ' Link VNPay trong email (nếu có) không còn dùng được — đơn đã ghi nhận tiền mặt.';
            }

            return back()->with('success', 'Đã cập nhật trạng thái đơn và thanh toán.' . $hint);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors('Không lưu được: ' . $e->getMessage());
        }
    }

    public function checkIn(Booking $booking): \Illuminate\Http\RedirectResponse
    {
        if (!$booking->isAdminCheckinAllowed()) {
            return back()->with('error', 'Không thể thực hiện check-in cho đơn này.');
        }

        // Khóa luồng check-in tắt để tránh bỏ qua đối soát số người/phụ thu.
        if ($booking->bookingRooms()->exists()) {
            return back()->with(
                'error',
                'Vui lòng check-in bằng form "gán phòng + danh sách khách" để hệ thống đối soát số người thực tế và phụ thu chênh lệch.'
            );
        }

        $old = $booking->status;
        $booking->status = 'checked_in';
        $booking->actual_check_in = Carbon::now();
        $booking->save();

        // Cập nhật trạng thái tất cả khách hàng thành checked_in
        $booking->guests()->update(['checkin_status' => 'checked_in']);

        // Xóa cache để cập nhật giao diện ngay lập tức
        Cache::forget("guest_info_{$booking->id}");

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $staffName = $user?->full_name ?? 'Lễ tân';
        $rooms = $booking->bookingRooms()->with('room')->get()->map(fn($br) => $br->room?->name)->filter()->implode(', ');
        $roomText = $rooms ? " phòng {$rooms}" : '';

        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'old_status' => $old,
            'new_status' => 'checked_in',
            'notes' => "{$staffName} check-in{$roomText}.",
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Khách đã được check-in.');
    }

    public function checkOut(Booking $booking): \Illuminate\Http\RedirectResponse
    {
        if (!$booking->isAdminCheckoutAllowed()) {
            return back()->with('error', 'Không thể thực hiện check-out cho đơn này.');
        }

        $old = $booking->status;
        $booking->actual_check_out = Carbon::now();
        // mark completed on checkout
        $booking->status = 'completed';
        $booking->save();

        RoomBookedDate::where('booking_id', $booking->id)->delete();

        $releasedRoomIds = $booking->bookingRooms()
            ->whereNotNull('room_id')
            ->pluck('room_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        if ($releasedRoomIds !== []) {
            Room::whereIn('id', $releasedRoomIds)->update(['status' => 'available']);
        }

        // Cập nhật trạng thái tất cả khách thành checked_out
        $booking->guests()->update(['checkin_status' => 'checked_out']);
        $booking->bookingGuests()->update(['status' => 'checked_out', 'checkin_status' => 'checked_out']);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $staffName = $user?->full_name ?? 'Lễ tân';
        $rooms = $booking->bookingRooms()->with('room')->get()->map(fn($br) => $br->room?->name)->filter()->implode(', ');
        $roomText = $rooms ? " phòng {$rooms}" : '';

        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'old_status' => $old,
            'new_status' => 'completed',
            'notes' => "{$staffName} check-out{$roomText}.",
            'changed_at' => Carbon::now(),
        ]);

        return back()->with('success', 'Khách hàng check-out thành công.');
    }

    /** Lấy thông tin khách hàng cho modal check-in, group by booking_rooms. */
    public function getGuestInfo(Booking $booking): \Illuminate\Http\JsonResponse
    {
        try {
            if (!$booking->isAdminCheckinAllowed()) {
                return response()->json(['error' => 'Không thể thực hiện check-in cho đơn này.'], 403);
            }

            $bookingData = $this->loadBookingGuestData($booking);
            $guestsByRoom = $this->buildGuestsByRoomResponse($bookingData);

            if (empty($guestsByRoom)) {
                return $this->createEmptyGuestResponse($booking);
            }

            return $this->createGuestInfoResponse($booking, $guestsByRoom);

        } catch (\Exception $e) {
            return $this->handleGuestInfoError($e, $booking);
        }
    }

    private function loadBookingGuestData(Booking $booking): array
    {
        $booking->load([
            'bookingRooms.room.roomType',
            'guests:id,booking_id,name,type,cccd,checkin_status,room_index,room_type,room_id',
            'user:id,full_name',
            'rooms:id,name'
        ]);

        return [
            'booking' => $booking,
            'bookingRooms' => $booking->bookingRooms()->with('room.roomType')->orderBy('id')->get(),
            'allGuests' => $booking->guests()->orderBy('id')->get(),
            'rooms' => $booking->rooms
        ];
    }

    private function buildGuestsByRoomResponse(array $data): array
    {
        if ($data['bookingRooms']->isEmpty()) {
            return $this->buildLegacyGuestResponse($data['rooms'], $data['allGuests']);
        }

        return $this->buildAssignmentGuestResponse($data['bookingRooms'], $data['allGuests']);
    }

    private function buildLegacyGuestResponse($rooms, $allGuests): array
    {
        $guestsByRoom = [];
        $guestIndex = 0;

        foreach ($rooms as $roomIndex => $room) {
            $roomTypeName = $room->roomType?->name ?? 'Phòng';
            $roomName = $room->name ?? ($roomIndex + 1);
            $roomDisplayName = $roomTypeName . ' ' . $roomName;

            $roomGuests = [];
            $roomGuestCount = ($roomIndex === 0) ? $allGuests->count() : 0;

            for ($i = 0; $i < $roomGuestCount && $guestIndex < $allGuests->count(); $i++) {
                $guest = $allGuests[$guestIndex++];
                $roomGuests[] = $this->formatGuestData($guest);
            }

            if (!empty($roomGuests)) {
                $guestsByRoom[] = $this->formatRoomGroupData($room, $roomDisplayName, $roomTypeName, $roomGuests);
            }
        }

        return $guestsByRoom;
    }

    private function buildAssignmentGuestResponse($bookingRooms, $allGuests): array
    {
        $guestsByRoom = [];
        $guestsByAssignedRoom = $allGuests->groupBy(fn($g) => $g->room_id ?? 'unassigned');
        $roomMap = $this->buildRoomMap($bookingRooms);

        foreach ($guestsByAssignedRoom as $roomId => $guests) {
            if ($roomId === 'unassigned') {
                $guestsByRoom[] = $this->buildUnassignedRoomGroup($guests);
            } else {
                $guestsByRoom[] = $this->buildAssignedRoomGroup($roomId, $guests, $roomMap);
            }
        }

        return $guestsByRoom;
    }

    private function buildRoomMap($bookingRooms): array
    {
        $roomMap = [];
        foreach ($bookingRooms as $bookingRoom) {
            $room = $bookingRoom->room;
            if ($room) {
                $roomMap[$room->id] = ['room' => $room, 'booking_room' => $bookingRoom];
            }
        }
        return $roomMap;
    }

    private function buildUnassignedRoomGroup($guests): array
    {
        return [
            'room_id' => null,
            'room_name' => 'Chưa gán phòng',
            'room_number' => null,
            'room_type' => null,
            'guests' => $guests->map(fn($g) => $this->formatGuestData($g, false))->toArray()
        ];
    }

    private function buildAssignedRoomGroup(int $roomId, $guests, array $roomMap): array
    {
        $roomInfo = $roomMap[$roomId] ?? null;
        $room = $roomInfo['room'] ?? null;
        $roomTypeName = $room?->roomType?->name ?? 'Phòng';
        $roomNumber = $room?->room_number ?? $room?->name ?? '#' . $roomId;

        return [
            'room_id' => $roomId,
            'room_name' => $roomNumber . ' (' . $roomTypeName . ')',
            'room_number' => $roomNumber,
            'room_type' => $roomTypeName,
            'guests' => $guests->map(fn($g) => $this->formatGuestData($g, true))->toArray()
        ];
    }

    private function formatGuestData($guest, $assigned = null): array
    {
        $isAssigned = $assigned ?? !is_null($guest->room_id);

        return [
            'id' => $guest->id,
            'name' => $guest->name,
            'type' => $guest->type ?? 'adult',
            'cccd' => $guest->cccd ?: '',
            'status' => $guest->checkin_status,
            'room_id' => $guest->room_id,
            'room_assigned' => $isAssigned,
        ];
    }

    private function formatRoomGroupData($room, string $roomDisplayName, string $roomTypeName, array $guests): array
    {
        return [
            'room_id' => $room?->id,
            'room_name' => $roomDisplayName,
            'room_number' => $room?->room_number ?? $room?->name ?? null,
            'room_type' => $room?->roomType?->name ?? $roomTypeName ?? null,
            'guests' => $guests
        ];
    }

    private function createEmptyGuestResponse(Booking $booking): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
                'user' => $booking->user?->full_name ?? '—',
            ],
            'guests_by_room' => [],
            'message' => 'Không có thông tin khách hàng. Vui lòng thêm thông tin khách hàng.'
        ]);
    }

    private function createGuestInfoResponse(Booking $booking, array $guestsByRoom): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'user_name' => $booking->user?->full_name ?? '—',
                'rooms' => $booking->rooms->pluck('name')->implode(', '),
                'check_in' => $booking->check_in ? \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') : '—',
                'check_out' => $booking->check_out ? \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') : '—',
            ],
            'guests_by_room' => $guestsByRoom,
        ]);
    }

    private function handleGuestInfoError(\Exception $e, Booking $booking): \Illuminate\Http\JsonResponse
    {
        Log::error('Error in getGuestInfo for booking ' . $booking->id, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Lỗi server: ' . $e->getMessage(),
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
                'user' => $booking->user?->full_name,
            ],
            'guests' => []
        ], 500);
    }

    public function updateGuestInfo(Request $request, Booking $booking): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'guests' => 'required|array|min:1',
            'guests.*.id' => 'required|exists:guests,id',
            'guests.*.name' => 'required|string|max:150',
            'guests.*.cccd' => 'nullable|string|max:20',
            'guests.*.type' => 'required|in:adult,child,child_6_11,child_0_5',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['guests'] as $guestData) {
                /** @var \App\Models\Guest $guest */
                $guest = Guest::find($guestData['id']);
                if ($guest && $guest->booking_id === $booking->id) {
                    $guest->update([
                        'name' => $guestData['name'],
                        'cccd' => $guestData['cccd'] ?? null,
                        'type' => $guestData['type'],
                    ]);
                }
            }

            DB::commit();

            // Clear cache for this booking to ensure fresh data
            Cache::forget("guest_info_{$booking->id}");

            // Return updated guest list
            return $this->getGuestInfo($booking);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /** Gán phòng cụ thể cho khách khi check-in. */
    public function assignGuestToRoom(Request $request, Booking $booking): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'room_id'  => 'required|exists:rooms,id',
        ]);

        DB::beginTransaction();
        try {
            $guest = Guest::find($validated['guest_id']);

            // Verify guest belongs to this booking
            if ($guest->booking_id !== $booking->id) {
                return response()->json(['error' => 'Khách không thuộc đơn đặt này'], 403);
            }

            // Verify room is part of this booking
            $roomExists = $booking->bookingRooms()->where('room_id', $validated['room_id'])->exists();
            if (!$roomExists) {
                return response()->json(['error' => 'Phòng không thuộc đơn đặt này'], 403);
            }

            // Assign room to guest
            $guest->update(['room_id' => $validated['room_id']]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã gán phòng thành công',
                'guest' => [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'room_display' => $guest->room_display
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning room to guest', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /** Lấy danh sách phòng trống có thể gán cho khách. */
    public function getAvailableRoomsForAssignment(Booking $booking): \Illuminate\Http\JsonResponse
    {
        try {
            // Get rooms from this booking
            $bookingRooms = $booking->bookingRooms()
                ->with('room.roomType')
                ->get()
                ->reject(fn ($br) => $br->room && $br->room->isInMaintenance());

            $rooms = $bookingRooms->map(function ($br) {
                $room = $br->room;
                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number ?? $room->name ?? '#' . $room->id,
                    'room_type' => $room->roomType?->name ?? 'Phòng',
                    'status' => $room->status,
                    'max_guests' => $room->catalogueMaxGuests(),
                    'current_guests' => Guest::where('room_id', $room->id)
                        ->whereHas('booking', function ($q) {
                            $q->whereIn('status', ['confirmed', 'checked_in']);
                        })
                        ->count()
                ];
            });

            return response()->json([
                'rooms' => $rooms,
                'booking_id' => $booking->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting available rooms', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Có lỗi xảy ra'], 500);
        }
    }

    // ===================== MULTI-ROOM BOOKING =====================

    public function createMulti(): \Illuminate\View\View
    {
        $hotelInfo = HotelInfo::first();
        return view('admin.bookings.create-multi', compact('hotelInfo'));
    }

    public function checkAvailability(Request $request): \Illuminate\Http\JsonResponse
    {
        $checkIn = $request->query('check_in');
        $checkOut = $request->query('check_out');

        if (!$checkIn || !$checkOut) {
            return response()->json(['error' => 'Thiếu ngày check-in hoặc check-out'], 400);
        }

        $rooms = Room::with('roomType')
            ->vacantForGuestBookingWindow($checkIn, $checkOut)
            ->get();

        $period = CarbonPeriod::create($checkIn, Carbon::parse($checkOut)->subDay());
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->toDateString();
        }

        // Group by room type
        $roomTypes = [];
        foreach ($rooms as $room) {
            /** @var Room $room */
            $typeId = $room->room_type_id;
            if (!isset($roomTypes[$typeId])) {
                $roomTypes[$typeId] = [
                    'room_type_id' => $typeId,
                    'name' => $room->roomType->name ?? 'Không xác định',
                    'description' => strip_tags(html_entity_decode($room->roomType->description ?? '')),
                    'base_price' => $room->catalogueBasePrice(),
                    'max_occupancy' => (int) ($room->roomType->capacity ?? $room->catalogueMaxGuests() ?? 6),
                    'standard_capacity' => (int) ($room->roomType->standard_capacity ?? config('booking.pricing.standard_capacity', 3)),
                    'adult_capacity' => $room->roomType->adult_capacity ?? $room->catalogueMaxGuests() ?? 2,
                    'child_capacity' => $room->roomType->child_capacity ?? 0,
                    'adult_surcharge_rate' => RoomOccupancyPricing::adultSurchargeRate($room->roomType),
                    'child_surcharge_rate' => RoomOccupancyPricing::childSurchargeRate($room->roomType),
                    'area' => $room->roomType->area ?? 30,
                    'image' => $room->roomType ? RoomType::resolveImageUrl($room->roomType->image) : null,
                    'total_count' => 0,
                    'available_count' => 0,
                ];
            }
            $roomTypes[$typeId]['total_count']++;
            $roomTypes[$typeId]['available_count']++;
        }

        // Trừ các đơn chưa gán số phòng (room_id null) để tránh overbooking theo loại phòng.
        foreach ($roomTypes as $typeId => &$meta) {
            $unassigned = BookingRoom::unassignedCountForRoomTypeBetween((int) $typeId, $checkIn, $checkOut);
            $meta['available_count'] = max(0, (int) $meta['available_count'] - $unassigned);
            $meta['unassigned_count'] = $unassigned;
        }
        unset($meta);

        // Filter only available room types
        $availableRoomTypes = array_values(array_filter($roomTypes, fn($rt) => $rt['available_count'] > 0));

        return response()->json([
            'rooms' => $availableRoomTypes,
            'nights' => count($dates),
        ]);
    }

    public function validateCoupon(Request $request): \Illuminate\Http\JsonResponse
    {
        $code = $request->query('code');
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
            })
            ->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn']);
        }

        return response()->json([
            'valid' => true,
            'discount_percent' => $coupon->discount_percent,
            'message' => "Giảm {$coupon->discount_percent}%"
        ]);
    }

    public function storeMulti(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|array|min:1',
            'rooms.*.room_type_id' => 'required|exists:room_types,id',
            'rooms.*.quantity' => 'required|integer|min:1',
            'rooms.*.adults' => 'required|integer|min:1',
            'rooms.*.children_0_5' => 'nullable|integer|min:0|max:2',
            'rooms.*.children_6_11' => 'nullable|integer|min:0',
            'rooms.*.price_per_night' => 'required|numeric|min:0',
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'coupon_code' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,vnpay',
            'payment_status' => 'nullable|in:pending,paid',
            'amount_paid' => 'nullable|numeric|min:0',
            'guests' => 'nullable|array',
            'guests.*' => 'array',
            'representative_name' => 'required|string|max:150',
            'representative_cccd' => 'required|string|regex:/^[0-9]{12}$/',
        ]);

        $repName = trim((string) $validated['representative_name']);
        $repCccd = trim((string) $validated['representative_cccd']);
        if ($repName === '') {
            return back()->withErrors(['representative_name' => 'Vui lòng nhập họ tên người đại diện.'])->withInput();
        }
        if (! preg_match('/^[0-9]{12}$/', $repCccd)) {
            return back()->withErrors(['representative_cccd' => 'CCCD người đại diện phải gồm đúng 12 chữ số.'])->withInput();
        }

        // Một đơn chỉ một người đại diện (không phụ thuộc số phòng).
        $representativeGuestRows = [[
            'room_type' => null,
            'room_index' => 0,
            'name' => $repName,
            'cccd' => $repCccd,
            'type' => 'adult',
            'is_representative' => 1,
        ]];

        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->toDateString();
        }

        DB::beginTransaction();
        try {
            // 1. Tìm hoặc tạo User
            $user = $this->findOrCreateGuestUser($validated);

            // 2. Tính toán dữ liệu giá và phòng
            $pricingData = $this->calculateMultiRoomData($validated['rooms'], $dates);
            $subtotal = $pricingData['subtotal'];
            $totalGuests = $pricingData['total_guests'];
            $calculatedRoomData = $pricingData['calculatedRoomData'];

            $discount = $validated['discount_amount'] ?? 0;
            $totalPrice = max(0, $subtotal - $discount);

            // 3. Tạo đơn Booking
            $paymentMethodInitial = $validated['payment_method'];

            $booking = Booking::create([
                'user_id' => $user->id,
                'room_id' => null,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $totalGuests,
                'adults' => $totalGuests,
                'children' => 0,
                'total_price' => $totalPrice,
                'coupon_code' => $validated['coupon_code'] ?? null,
                'discount_amount' => $discount,
                'status' => $paymentMethodInitial === 'vnpay' ? 'pending' : 'confirmed',
                'payment_status' => $paymentMethodInitial === 'vnpay' ? 'pending' : 'paid',
                'payment_method' => $validated['payment_method'],
                'placed_via' => Booking::PLACED_VIA_ADMIN,
                'cccd' => $repCccd,
            ]);

            // 4. VNPay: không tạo booking_rooms / không chiếm slot cho đến khi thanh toán thành công (materialize tại VnPay return).
            if ($paymentMethodInitial === 'vnpay') {
                $booking->pending_checkout_payload = [
                    'mode' => 'multi',
                    'dates' => $dates,
                    'calculated_room_data' => array_values($calculatedRoomData),
                ];
                $booking->save();
            } else {
                $this->assignRoomsToBooking($booking, $calculatedRoomData, $dates);
            }

            // 5. Lưu thông tin khách hàng (legacy)
            $this->createBookingLegacyGuests($booking, $representativeGuestRows);

            // 5b. Người đại diện cho check-in modal (booking_guests) — đồng bộ CCCD & phòng khi chỉ 1 phòng
            if (! BookingGuest::where('booking_id', $booking->id)->exists()) {
                $brRows = $booking->bookingRooms()->get();
                $singleBookingRoomId = $brRows->count() === 1 ? $brRows->first()->id : null;
                BookingGuest::create(BookingGuest::filterAttributesForStorage([
                    'booking_id' => $booking->id,
                    'booking_room_id' => $singleBookingRoomId,
                    'name' => $repName,
                    'cccd' => $repCccd,
                    'type' => 'adult',
                    'status' => 'pending',
                    'is_representative' => 1,
                    'checkin_status' => 'pending',
                ]));
            }

            // 6. Ghi log
            BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'new',
                'new_status' => $booking->status,
                'changed_at' => Carbon::now(),
            ]);

            // 7. Xử lý thanh toán
            if ($paymentMethodInitial === 'cash') {
                Payment::create([
                    'booking_id'     => $booking->id,
                    'amount'         => $booking->total_price,
                    'method'         => 'cash',
                    'status'         => 'paid',
                    'transaction_id' => 'CASH_' . Carbon::now()->format('YmdHis') . '_' . $booking->id,
                    'paid_at'        => Carbon::now(),
                ]);

                DB::commit();

                $booking->load('user');
                $hotelInfo = HotelInfo::first();
                PaymentInstructionDelivery::send(
                    $booking,
                    $hotelInfo,
                    count($dates),
                    null,
                    null,
                    $user->email,
                    true
                );

                return redirect()->route('admin.bookings.show', $booking)
                    ->with('success', 'Tạo đơn đặt phòng thành công! Đã ghi nhận thanh toán tiền mặt.')
                    ->with('info', 'Đã gửi thông tin đặt phòng qua email tới khách.');
            }

            if ($paymentMethodInitial === 'vnpay') {
                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->total_price,
                    'method' => 'vnpay',
                    'status' => 'pending',
                ]);

                DB::commit();

                $booking->load('user');
                $hotelInfo = HotelInfo::first();
                $payUrl = $this->signedVnPayEntryUrl($booking);
                PaymentInstructionDelivery::send(
                    $booking,
                    $hotelInfo,
                    count($dates),
                    null,
                    $payUrl,
                    $user->email,
                    false
                );

                return redirect()->route('admin.bookings.payment-instruction', $booking)
                    ->with('success', 'Đã tạo đơn.')
                    ->with('info', 'Đã gửi email chứa link thanh toán VNPay tới khách. Link vẫn hiển thị trên trang này để sao chép.');
            }

            DB::commit();
            return redirect()->route('admin.bookings.show', $booking)->with('success', 'Tạo đơn đặt phòng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    protected function signedVnPayEntryUrl(Booking $booking): string
    {
        $days = max(1, (int) config('vnpay.pay_entry_signed_ttl_days', 14));

        return URL::to(URL::signedRoute(
            'payment.vnpay.pay',
            ['booking' => $booking->id],
            now()->addDays($days),
            false
        ));
    }

    /** Gửi email thanh toán VNPay cho khách khi admin tạo đơn. */
    protected function sendVnPayPaymentEmail(
        Booking $booking,
        int $adults = 1,
        int $children611 = 0,
        int $children05 = 0
    ): void {
        try {
            // Tạo link VNPay có chữ ký
            $vnpayPayUrl = $this->signedVnPayEntryUrl($booking);

            // Tính số đêm
            $checkIn = new \Carbon\Carbon($booking->check_in);
            $checkOut = new \Carbon\Carbon($booking->check_out);
            $nights = $checkIn->diffInDays($checkOut);

            $hotelInfo = HotelInfo::first();
            PaymentInstructionDelivery::send(
                $booking,
                $hotelInfo,
                $nights,
                null,
                $vnpayPayUrl,
                $booking->user->email,
                false
            );
        } catch (\Throwable $e) {
            Log::error('Error sending VNPay payment email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function paymentInstruction(Booking $booking)
    {
        $hotelInfo = HotelInfo::first();
        $existingPayment = Payment::where('booking_id', $booking->id)
            ->where('status', 'paid')
            ->first();

        if ($existingPayment) {
            return redirect()->route('admin.bookings.show', $booking)
                ->with('info', 'Đơn đặt phòng này đã được thanh toán.');
        }

        $vnpayPayUrl = null;
        $pendingVnpay = Payment::where('booking_id', $booking->id)
            ->where('method', 'vnpay')
            ->where('status', 'pending')
            ->first();

        if ($pendingVnpay && ($booking->payment_method === null || $booking->payment_method === 'vnpay')) {
            $vnpayPayUrl = $this->signedVnPayEntryUrl($booking);
        }

        return view('admin.bookings.payment-instruction', compact('booking', 'hotelInfo', 'vnpayPayUrl'));
    }

    public function cancel(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
    {
        // Validate
        if ($booking->status !== 'pending') {
            return back()->withErrors('Chỉ có thể hủy các đơn đang chờ xác nhận.');
        }

        $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ], [
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy đơn.',
            'cancel_reason.max' => 'Lý do hủy không được vượt quá 500 ký tự.',
        ]);

        DB::beginTransaction();
        try {
            $removedId = (int) $booking->id;
            $obliterator = app(UnpaidBookingObliterateService::class);
            if ($obliterator->obliterateIfUnpaid($booking)) {
                DB::commit();

                return redirect()->route('admin.bookings.index')
                    ->with('success', 'Đã gỡ đơn #'.$removedId.' (chưa thanh toán) — không lưu bản ghi hủy, phòng được mở.');
            }

            $booking->update([
                'status' => 'cancelled',
                'cancel_reason' => $request->cancel_reason,
                'cancelled_at' => now(),
            ]);

            RoomBookedDate::where('booking_id', $booking->id)->delete();

            Payment::where('booking_id', $booking->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);

            // Log
            BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'pending',
                'new_status' => 'cancelled',
                'notes' => 'Hủy đơn: ' . $request->cancel_reason,
                'changed_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.bookings.index')
                ->with('success', 'Đã hủy đơn #' . $booking->id . ' thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Thêm dịch vụ từ danh mục (booking_services) — giá snapshot theo bảng dịch vụ, cộng vào tổng đơn.
     */
    public function storeBookingServices(Request $request, Booking $booking)
    {
        if ($response = $this->guardBookingOpenForExtras($booking)) {
            return $response;
        }

        $booking->loadMissing(['bookingRooms.room', 'room']);

        $filtered = $this->normalizeSvcCatalogRows($request);

        if (count($filtered) === 0) {
            return back()->with('error', 'Chọn ít nhất một dịch vụ trong danh mục (không để trống ô dịch vụ) và số lượng ≥ 1.');
        }

        if (count($filtered) > 50) {
            return back()->with('error', 'Tối đa 50 dòng dịch vụ mỗi lần gửi.');
        }

        $eligibleIds = BookingCatalogServices::eligibleIds($booking);

        $validator = Validator::make(
            ['svc_items' => $filtered],
            [
                'svc_items' => ['required', 'array', 'min:1'],
                'svc_items.*.service_id' => ['required', 'integer', Service::existsActiveIdRule(), Rule::in($eligibleIds)],
                'svc_items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            ],
            [
                'svc_items.*.service_id.in' => 'Dịch vụ không khớp loại phòng trên đơn hoặc không còn trong danh mục.',
                'svc_items.*.quantity.min' => 'Số lượng mỗi dịch vụ phải từ 1 trở lên.',
            ]
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $lines = $validator->validated()['svc_items'];

        if (! Schema::hasTable('booking_services')) {
            return back()->with('error', 'Chưa có bảng booking_services trong database. Chạy: php artisan migrate.')->withInput();
        }

        DB::beginTransaction();
        try {
            $totalAdded = 0.0;
            $logParts = [];

            foreach ($lines as $line) {
                $service = Service::query()->whereKey($line['service_id'])->firstOrFail();
                $qty = (int) $line['quantity'];
                $unit = (float) $service->price;
                $lineTotal = round($unit * $qty, 2);

                BookingServiceRow::create([
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'quantity' => $qty,
                    'price' => $unit,
                ]);

                $totalAdded += $lineTotal;
                $logParts[] = $service->name . ' × ' . $qty . ' — ' . number_format($lineTotal, 0, ',', '.') . ' ₫';
            }

            $booking->total_price = (float) $booking->total_price + $totalAdded;
            $booking->save();

            $payment = $booking->payments()->orderByDesc('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->amount = (float) $payment->amount + $totalAdded;
                $payment->save();
            }

            DB::commit();

            $booking->refresh();
            $booking->reconcilePaymentStatusWithPayments();
            $invoiceNote = $this->syncInvoiceExtrasIfExists($booking);

            $note = count($logParts) === 1
                ? 'Thêm dịch vụ danh mục: ' . ($logParts[0] ?? '')
                : 'Thêm ' . count($logParts) . ' dịch vụ danh mục: ' . implode(' | ', $logParts);

            try {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'notes' => $note,
                    'changed_at' => now(),
                ]);
            } catch (\Throwable $logErr) {
                // Không làm mất dữ liệu đã commit
            }

            return redirect()
                ->route('admin.bookings.show', $booking->fresh())
                ->with('success', (count($lines) === 1
                    ? 'Đã thêm dịch vụ từ danh mục.'
                    : 'Đã thêm ' . count($lines) . ' dịch vụ từ danh mục.') . $invoiceNote);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('storeBookingServices failed', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Không thêm được dịch vụ: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Một form: lưu đồng thời dịch vụ danh mục và/hoặc phụ phí (tránh mất dữ liệu khi chỉ bấm một nút).
     */
    public function storeBookingExtras(Request $request, Booking $booking)
    {
        if ($response = $this->guardBookingOpenForExtras($booking)) {
            return $response;
        }

        $booking->loadMissing(['bookingRooms.room', 'room']);

        $svcFiltered = $this->normalizeSvcCatalogRows($request);

        $rawItems = $request->input('items', []);
        if (! is_array($rawItems)) {
            $rawItems = [];
        }
        $surgeFiltered = [];
        foreach ($rawItems as $row) {
            if (! is_array($row)) {
                continue;
            }
            $reason = trim((string) ($row['reason'] ?? ''));
            $amount = (float) ($row['amount'] ?? 0);
            if ($reason === '' || $amount <= 0) {
                continue;
            }
            $surgeFiltered[] = $row;
        }

        if (count($svcFiltered) === 0 && count($surgeFiltered) === 0) {
            return back()->with('error', 'Chọn ít nhất một dịch vụ danh mục hoặc điền ít nhất một dòng phụ phí (mô tả + số tiền > 0), rồi bấm Lưu.');
        }

        if (count($svcFiltered) > 50) {
            return back()->with('error', 'Tối đa 50 dòng dịch vụ mỗi lần gửi.');
        }
        if (count($surgeFiltered) > 50) {
            return back()->with('error', 'Tối đa 50 dòng phụ phí mỗi lần gửi.');
        }

        $svcLines = [];
        if (count($svcFiltered) > 0) {
            if (! Schema::hasTable('booking_services')) {
                return back()->with('error', 'Chưa có bảng booking_services trong database. Chạy: php artisan migrate (hoặc bật lại migration tạo bảng dịch vụ kèm).')->withInput();
            }

            $eligibleIds = BookingCatalogServices::eligibleIds($booking);

            $vSvc = Validator::make(
                ['svc_items' => $svcFiltered],
                [
                    'svc_items' => ['required', 'array', 'min:1'],
                    'svc_items.*.service_id' => ['required', 'integer', Service::existsActiveIdRule(), Rule::in($eligibleIds)],
                    'svc_items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
                ],
                [
                    'svc_items.*.service_id.in' => 'Dịch vụ không khớp loại phòng trên đơn hoặc không còn trong danh mục.',
                    'svc_items.*.quantity.min' => 'Số lượng mỗi dịch vụ phải từ 1 trở lên.',
                ]
            );
            if ($vSvc->fails()) {
                return back()->withErrors($vSvc)->withInput();
            }
            $svcLines = $vSvc->validated()['svc_items'];
        }

        $surgeRows = [];
        if (count($surgeFiltered) > 0) {
            $vSur = Validator::make(
                ['items' => $surgeFiltered],
                [
                    'items' => ['required', 'array', 'min:1'],
                    'items.*.reason' => ['required', 'string', 'max:500'],
                    'items.*.amount' => ['required', 'numeric', 'min:0.01'],
                ],
                [
                    'items.*.reason.required' => 'Mỗi dòng cần có mô tả / lý do (phụ phí, bồi thường…).',
                    'items.*.amount.min' => 'Số tiền mỗi dòng phải lớn hơn 0.',
                ]
            );
            if ($vSur->fails()) {
                return back()->withErrors($vSur)->withInput();
            }
            foreach ($vSur->validated()['items'] as $row) {
                $surgeRows[] = [
                    'reason' => trim((string) $row['reason']),
                    'amount' => (float) $row['amount'],
                ];
            }
        }

        DB::beginTransaction();
        try {
            $booking->refresh();

            $totalAdded = 0.0;
            $svcLogParts = [];
            $surgeLogParts = [];

            foreach ($svcLines as $line) {
                $service = Service::query()->whereKey($line['service_id'])->firstOrFail();
                $qty = (int) $line['quantity'];
                $unit = (float) $service->price;
                $lineTotal = round($unit * $qty, 2);

                BookingServiceRow::create([
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'quantity' => $qty,
                    'price' => $unit,
                ]);

                $totalAdded += $lineTotal;
                $svcLogParts[] = $service->name . ' × ' . $qty . ' — ' . number_format($lineTotal, 0, ',', '.') . ' ₫';
            }

            foreach ($surgeRows as $row) {
                $surcharge = $booking->surcharges()->create([
                    'service_id' => null,
                    'reason' => $row['reason'],
                    'quantity' => 1,
                    'amount' => $row['amount'],
                ]);
                $totalAdded += (float) $surcharge->amount;
                $surgeLogParts[] = $surcharge->reason . ' — ' . number_format((float) $surcharge->amount, 0, ',', '.') . ' ₫';
            }

            $booking->total_price = (float) $booking->total_price + $totalAdded;
            $booking->save();

            $payment = $booking->payments()->orderByDesc('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->amount = (float) $payment->amount + $totalAdded;
                $payment->save();
            }

            DB::commit();

            $booking->refresh();
            $booking->reconcilePaymentStatusWithPayments();
            $invoiceNote = $this->syncInvoiceExtrasIfExists($booking);

            $noteBits = [];
            if ($svcLogParts !== []) {
                $noteBits[] = count($svcLines) === 1
                    ? 'Thêm dịch vụ danh mục: ' . ($svcLogParts[0] ?? '')
                    : 'Thêm ' . count($svcLines) . ' dịch vụ danh mục: ' . implode(' | ', $svcLogParts);
            }
            if ($surgeLogParts !== []) {
                $c = count($surgeRows);
                $noteBits[] = $c === 1
                    ? 'Phụ phí: ' . ($surgeLogParts[0] ?? '')
                    : 'Phụ phí (' . $c . ' dòng): ' . implode(' | ', $surgeLogParts);
            }

            try {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'notes' => implode(' — ', $noteBits),
                    'changed_at' => now(),
                ]);
            } catch (\Throwable) {
            }

            $msgParts = [];
            if (count($svcLines) > 0) {
                $msgParts[] = count($svcLines) === 1 ? '1 dịch vụ danh mục' : count($svcLines) . ' dịch vụ danh mục';
            }
            if (count($surgeRows) > 0) {
                $msgParts[] = count($surgeRows) === 1 ? '1 phụ phí' : count($surgeRows) . ' phụ phí';
            }

            return redirect()
                ->route('admin.bookings.show', $booking->fresh())
                ->with('success', 'Đã lưu: ' . implode(' và ', $msgParts) . '.' . $invoiceNote);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('storeBookingExtras failed', [
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Không lưu được: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Phụ phí / bồi thường phát sinh (không cố định): chỉ mô tả + số tiền — không dùng danh mục dịch vụ.
     */
    public function storeSurcharge(Request $request, Booking $booking)
    {
        if ($response = $this->guardBookingOpenForExtras($booking)) {
            return $response;
        }

        $rows = $this->normalizeSurchargeRows($request);
        if ($rows instanceof \Illuminate\Http\RedirectResponse) {
            return $rows;
        }

        DB::beginTransaction();
        try {
            $totalAdded = 0.0;
            $logParts = [];

            foreach ($rows as $row) {
                $surcharge = $booking->surcharges()->create([
                    'service_id' => null,
                    'reason' => $row['reason'],
                    'quantity' => 1,
                    'amount' => $row['amount'],
                ]);

                $totalAdded += (float) $surcharge->amount;

                $logParts[] = $surcharge->reason . ' — ' . number_format((float) $surcharge->amount, 0, ',', '.') . ' ₫';
            }

            $booking->total_price = (float) $booking->total_price + $totalAdded;
            $booking->save();

            $payment = $booking->payments()->orderByDesc('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->amount = (float) $payment->amount + $totalAdded;
                $payment->save();
            }

            DB::commit();

            $booking->refresh();
            $booking->reconcilePaymentStatusWithPayments();
            $invoiceNote = $this->syncInvoiceExtrasIfExists($booking);

            $count = count($rows);
            $logNote = $count === 1
                ? 'Phụ phí: ' . ($logParts[0] ?? '')
                : 'Phụ phí (' . $count . ' dòng): ' . implode(' | ', $logParts);

            try {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'old_status' => $booking->status,
                    'new_status' => $booking->status,
                    'notes' => $logNote,
                    'changed_at' => now(),
                ]);
            } catch (\Throwable) {
            }

            $msg = $count === 1
                ? 'Đã ghi nhận phụ phí.'
                : 'Đã ghi nhận ' . $count . ' khoản phụ phí.';

            return redirect()
                ->route('admin.bookings.show', $booking->fresh())
                ->with('success', $msg . $invoiceNote);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Không ghi nhận được phụ phí: ' . $e->getMessage());
        }
    }

    /**
     * @return list<array{reason: string, amount: float}>|\Illuminate\Http\RedirectResponse
     */
    private function normalizeSurchargeRows(Request $request)
    {
        $items = $request->input('items');

        if (is_array($items) && count($items) > 0) {
            $filtered = [];
            foreach ($items as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $reason = trim((string) ($row['reason'] ?? ''));
                $amount = (float) ($row['amount'] ?? 0);
                if ($reason === '' || $amount <= 0) {
                    continue;
                }
                $filtered[] = $row;
            }

            if (count($filtered) === 0) {
                return back()->with('error', 'Điền ít nhất một dòng: mô tả phụ phí và số tiền lớn hơn 0.');
            }

            if (count($filtered) > 50) {
                return back()->with('error', 'Tối đa 50 dòng phụ phí mỗi lần gửi.');
            }

            $validator = Validator::make(
                ['items' => $filtered],
                [
                    'items' => ['required', 'array', 'min:1'],
                    'items.*.reason' => ['required', 'string', 'max:500'],
                    'items.*.amount' => ['required', 'numeric', 'min:0.01'],
                ],
                [
                    'items.*.reason.required' => 'Mỗi dòng cần có mô tả / lý do (phụ phí, bồi thường…).',
                    'items.*.amount.min' => 'Số tiền mỗi dòng phải lớn hơn 0.',
                ]
            );

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            /** @var list<array<string, mixed>> $clean */
            $clean = [];
            foreach ($validator->validated()['items'] as $row) {
                $clean[] = [
                    'reason' => trim((string) $row['reason']),
                    'amount' => (float) $row['amount'],
                ];
            }

            return $clean;
        }

        $legacy = $request->validate([
            'reason' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        return [[
            'reason' => trim((string) $legacy['reason']),
            'amount' => (float) $legacy['amount'],
        ]];
    }

    /** Sau khi đơn đã commit — nếu có hóa đơn thì cập nhật dịch vụ/phụ phí trên HĐ theo đơn. */
    private function syncInvoiceExtrasIfExists(Booking $booking): string
    {
        $booking->loadMissing('invoice');
        if (! $booking->invoice) {
            return '';
        }

        try {
            InvoiceBookingSynchronizer::syncFullFromBooking($booking);

            return ' Đã cập nhật hóa đơn theo đơn.';
        } catch (\Throwable $e) {
            Log::warning('invoice_extras_auto_sync_failed', [
                'booking_id' => $booking->id,
                'invoice_id' => $booking->invoice->id,
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private function findOrCreateGuestUser(array $validated): User
    {
        $guestEmail = Str::lower(trim((string) $validated['email']));
        $user = User::firstOrCreate(
            ['email' => $guestEmail],
            [
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make(Str::random(12)),
            ]
        );

        $user->forceFill([
            'email' => $guestEmail,
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'] ?? $user->phone,
        ])->save();

        return $user;
    }

    private function calculateMultiRoomData(array $roomsData, array $dates): array
    {
        $subtotal = 0;
        $totalGuests = 0;
        $calculatedRoomData = [];

        $sortedDates = array_values(array_unique($dates));
        sort($sortedDates);
        $windowStart = $sortedDates[0] ?? null;
        $lastNight = $sortedDates !== [] ? (string) $sortedDates[array_key_last($sortedDates)] : null;
        $windowEnd = $lastNight !== null && $lastNight !== ''
            ? Carbon::parse($lastNight)->addDay()->toDateString()
            : null;

        foreach ($roomsData as $roomIndex => $roomData) {
            $roomTypeId = $roomData['room_type_id'];
            $room = $windowStart && $windowEnd
                ? Room::where('room_type_id', $roomTypeId)
                    ->vacantForGuestBookingWindow($windowStart, $windowEnd)
                    ->first()
                : null;
            $room = $room ?? Room::firstCatalogueRoomForRoomType($roomTypeId);

            $basePrice = (float) ($room ? $room->catalogueBasePrice() : 0);
            $roomType = $room?->roomType;

            $adults = $roomData['adults'] ?? 1;
            $children05 = $roomData['children_0_5'] ?? 0;
            $children611 = $roomData['children_6_11'] ?? 0;

            RoomOccupancyPricing::validate($adults, $children611, $children05, $roomType);
            $breakdown = RoomOccupancyPricing::breakdown($basePrice, $adults, $children611, $children05, $roomType);

            $actualPricePerNight = $breakdown['price_per_night'];
            $roomSubtotal = $actualPricePerNight * $roomData['quantity'] * count($dates);

            $subtotal += $roomSubtotal;
            $totalGuests += $adults * $roomData['quantity'];

            $key = $roomTypeId . '_' . $roomIndex;
            $calculatedRoomData[$key] = [
                'room_type_id' => $roomTypeId,
                'quantity' => $roomData['quantity'],
                'actualPricePerNight' => $actualPricePerNight,
                'roomSubtotalPerRoom' => $actualPricePerNight * count($dates),
                'adults' => $adults,
                'children05' => $children05,
                'children611' => $children611,
            ];
        }

        return [
            'subtotal' => $subtotal,
            'total_guests' => $totalGuests,
            'calculatedRoomData' => $calculatedRoomData
        ];
    }

    private function assignRoomsToBooking(Booking $booking, array $calculatedRoomData, array $dates): void
    {
        foreach ($calculatedRoomData as $key => $calculated) {
            $roomTypeId = (int) $calculated['room_type_id'];
            $quantity = (int) $calculated['quantity'];

            $physicalAvailable = Room::query()
                ->where('room_type_id', $roomTypeId)
                ->vacantForGuestBookingWindow(
                    (string) $booking->check_in,
                    (string) $booking->check_out
                )
                ->count();
            $unassigned = BookingRoom::unassignedCountForRoomTypeBetween(
                $roomTypeId,
                (string) $booking->check_in,
                (string) $booking->check_out
            );
            $bookableSlots = max(0, $physicalAvailable - $unassigned);
            if ($bookableSlots < $quantity) {
                throw new \Exception("Không đủ phòng trống cho loại phòng đã chọn");
            }

            for ($i = 0; $i < $quantity; $i++) {
                $booking->bookingRooms()->create([
                    'room_type_id' => $roomTypeId,
                    'room_id' => null,
                    'price_per_night' => $calculated['actualPricePerNight'],
                    'nights' => count($dates),
                    'subtotal' => $calculated['roomSubtotalPerRoom'],
                    'adults' => $calculated['adults'],
                    'children_0_5' => $calculated['children05'],
                    'children_6_11' => $calculated['children611'],
                ]);
            }
        }
    }

    private function createBookingLegacyGuests(Booking $booking, array $guestRows): void
    {
        if (empty($guestRows)) {
            return;
        }

        foreach ($guestRows as $index => $guestData) {
            if (!empty($guestData['name'])) {
                Guest::create([
                    'booking_id' => $booking->id,
                    'room_type' => $guestData['room_type'] ?? null,
                    'name' => $guestData['name'],
                    'cccd' => $guestData['cccd'] ?? null,
                    'type' => $guestData['type'] ?? 'adult',
                    'checkin_status' => 'pending',
                    'room_index' => $guestData['room_index'] ?? 0,
                    'is_representative' => isset($guestData['is_representative']) ? (int) $guestData['is_representative'] : ($index === 0 ? 1 : 0),
                ]);
            }
        }

        Cache::forget("guest_info_{$booking->id}");
    }

    private function flattenGuestPayloadByRoomType(array $guests): array
    {
        $rows = [];

        foreach ($guests as $roomKey => $roomGuests) {
            if (!is_array($roomGuests)) {
                continue;
            }

            if (isset($roomGuests['name']) || isset($roomGuests['cccd']) || isset($roomGuests['room_index'])) {
                $rows[] = [
                    'room_type' => null,
                    'room_index' => isset($roomGuests['room_index']) ? (int) $roomGuests['room_index'] : 0,
                    'name' => trim((string) ($roomGuests['name'] ?? '')),
                    'cccd' => trim((string) ($roomGuests['cccd'] ?? '')),
                    'type' => $roomGuests['type'] ?? 'adult',
                ];
                continue;
            }

            foreach ($roomGuests as $guestData) {
                if (!is_array($guestData)) {
                    continue;
                }

                $rows[] = [
                    'room_type' => trim((string) $roomKey),
                    'room_index' => isset($guestData['room_index']) ? (int) $guestData['room_index'] : 0,
                    'name' => trim((string) ($guestData['name'] ?? '')),
                    'cccd' => trim((string) ($guestData['cccd'] ?? '')),
                    'type' => $guestData['type'] ?? 'adult',
                ];
            }
        }

        return $rows;
    }

    public function toggleGuestStatus(Guest $guest): \Illuminate\Http\JsonResponse
    {
        try {
            $newStatus = ($guest->checkin_status === 'checked_in') ? 'pending' : 'checked_in';
            $guest->update(['checkin_status' => $newStatus]);

            // Xóa cache thông tin khách của đơn này để khi load lại sẽ lấy data mới
            Cache::forget("guest_info_{$guest->booking_id}");

            return response()->json([
                'success' => true,
                'new_status' => $newStatus,
                'message' => 'Đã cập nhật trạng thái khách hàng.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /** Khách bảng `guests` — không dùng $booking->guests (scalar cột số khách trên bookings). */
    private function legacyGuestRecords(Booking $booking): \Illuminate\Database\Eloquent\Collection
    {
        return $booking->guests()->get();
    }

    /**
     * CCCD người đại diện: ưu tiên theo đơn (bookings.cccd), sau user, cuối cùng bản ghi khách legacy.
     * Chuỗi rỗng không được coi là có giá trị (tránh lỗi ?? giữ '' và bỏ qua bookings.cccd).
     */
    private function resolveRepresentativeCccdFromBooking(Booking $booking): ?string
    {
        foreach ([$booking->cccd, $booking->user?->cccd] as $c) {
            $t = trim((string) ($c ?? ''));
            if ($t !== '') {
                return $t;
            }
        }

        $legacyGuests = $this->legacyGuestRecords($booking);
        $legacyRep = $legacyGuests->firstWhere('is_representative', 1)
            ?? $legacyGuests->first();
        $t = trim((string) ($legacyRep?->cccd ?? ''));

        return $t !== '' ? $t : null;
    }

    /** Chuẩn hóa CCCD để so khớp cờ người đại diện (chỉ chữ số). */
    private function normalizeCccdForRepMatch(?string $cccd): string
    {
        return preg_replace('/\D/', '', (string) ($cccd ?? ''));
    }

    /**
     * Một đơn chỉ một booking_guest là đại diện: khớp CCCD đơn/user nếu có, không thì ưu tiên bản ghi đang được đánh dấu, sau đó là khách đầu tiên.
     */
    private function normalizeBookingGuestRepresentativeFlags(Booking $booking): void
    {
        if (! Schema::hasColumn((new BookingGuest)->getTable(), 'is_representative')) {
            return;
        }

        $booking->loadMissing('bookingGuests');
        $guests = $booking->bookingGuests;
        if ($guests->isEmpty()) {
            return;
        }

        $target = $this->normalizeCccdForRepMatch($this->resolveRepresentativeCccdFromBooking($booking));

        $winner = null;
        if ($target !== '') {
            $winner = $guests->first(fn ($g) => $this->normalizeCccdForRepMatch($g->cccd) === $target);
        }
        if (! $winner) {
            $winner = $guests->firstWhere('is_representative', true) ?? $guests->first();
        }

        foreach ($guests as $g) {
            $should = (int) $g->id === (int) $winner->id;
            if ((bool) $g->is_representative !== $should) {
                $g->is_representative = $should;
                $g->save();
            }
        }
    }

    /** Vị trí (0-based) khách đại diện trong mảng submit check-in — cùng quy tắc với normalizeBookingGuestRepresentativeFlags. */
    private function representativeGuestIndexForCheckIn(Booking $booking, array $validGuests): int
    {
        $target = $this->normalizeCccdForRepMatch($this->resolveRepresentativeCccdFromBooking($booking));
        if ($target !== '') {
            foreach ($validGuests as $i => $g) {
                if ($this->normalizeCccdForRepMatch($g['cccd'] ?? '') === $target) {
                    return $i;
                }
            }
        }

        foreach ($validGuests as $i => $g) {
            if (BookingGuest::isAdultGuestType($g['type'] ?? 'adult')) {
                return $i;
            }
        }

        return 0;
    }

    /** Nhãn hiển thị phòng cho check-in: số phòng trước, loại phòng sau. */
    private function checkInRoomDisplayLabel(?Room $room): string
    {
        if (! $room) {
            return '';
        }

        $num = trim((string) ($room->room_number ?? ''));
        $type = trim((string) ($room->roomType?->name ?? ''));
        if ($num !== '') {
            return $type !== '' ? "Phòng {$num} — {$type}" : "Phòng {$num}";
        }

        $fallback = trim((string) ($room->name ?? ''));

        if ($fallback !== '') {
            if ($type !== '' && strcasecmp($fallback, $type) === 0) {
                return $type;
            }
            if ($type !== '' && stripos($fallback, $type) !== false) {
                return $fallback;
            }

            return $type !== '' ? "{$fallback} — {$type}" : $fallback;
        }

        return $type !== '' ? $type : 'Phòng';
    }

    /** Phòng vật lý cùng loại có thể chọn khi check-in (trừ đơn khác còn hiệu lực trong kỳ). */
    private function roomsSelectableForCheckIn(Booking $booking, BookingRoom $br): array
    {
        $baseRoom  = $br->room;
        $currentId = (int) ($br->room_id ?? 0);

        // Phòng legacy không gắn room_type_id: chỉ một lựa chọn
        if ($baseRoom && ! $baseRoom->room_type_id) {
            return [[
                'room_id' => $baseRoom->id,
                'label'   => $this->checkInRoomDisplayLabel($baseRoom),
            ]];
        }

        // Đặt theo loại — room_id có thể null; lấy loại từ phòng đã gán hoặc từ booking_room.room_type_id
        $roomTypeId = null;
        if ($baseRoom?->room_type_id) {
            $roomTypeId = (int) $baseRoom->room_type_id;
        } elseif ($br->room_type_id) {
            $roomTypeId = (int) $br->room_type_id;
        }

        if (! $roomTypeId) {
            return [];
        }

        $checkIn = Carbon::parse($booking->check_in)->toDateString();
        $checkOut = Carbon::parse($booking->check_out)->toDateString();
        $period = CarbonPeriod::create($checkIn, Carbon::parse($checkOut)->subDay());
        $dates = collect($period)->map(fn ($d) => $d->toDateString())->all();

        $blockedRoomIds = RoomBookedDate::query()
            ->whereIn('booked_date', $dates)
            ->where(function ($q) use ($booking) {
                $q->whereNull('booking_id')
                    ->orWhere('booking_id', '!=', $booking->id);
            })
            ->where(function ($q) {
                $q->whereNull('booking_id')
                    ->orWhereHas('booking', static function ($bq) {
                        $bq->whereNotIn('status', ['cancelled']);
                    });
            })
            ->pluck('room_id')
            ->unique()
            ->all();

        $candidates = Room::query()
            ->with('roomType')
            ->where('room_type_id', $roomTypeId)
            ->where(function ($q) use ($blockedRoomIds, $currentId) {
                $q->whereNotIn('id', $blockedRoomIds)
                    ->orWhere('id', $currentId);
            })
            ->where(function ($q) use ($currentId) {
                $q->whereNotIn('status', ['maintenance'])
                    ->orWhere('id', $currentId);
            })
            ->orderBy('room_number')
            ->get();

        $out = [];
        foreach ($candidates as $r) {
            $out[] = [
                'room_id' => $r->id,
                'label'   => $this->checkInRoomDisplayLabel($r),
            ];
        }

        // Luôn đảm bảo phòng hiện tại có trong danh sách
        if ($currentId && ! collect($out)->contains('room_id', $currentId)) {
            $roomForLabel = $baseRoom ?? Room::with('roomType')->find($currentId);
            if ($roomForLabel) {
                array_unshift($out, [
                    'room_id' => $currentId,
                    'label'   => $this->checkInRoomDisplayLabel($roomForLabel),
                ]);
            }
        }

        return $out;
    }

    private function syncRoomBookedDatesForBooking(Booking $booking): void
    {
        RoomBookedDate::syncForBooking($booking);
    }

    /** API: Lấy danh sách khách và phòng để gán cho check-in modal. */
    public function getCheckInData(Booking $booking): \Illuminate\Http\JsonResponse
    {
        try {
            if (!$booking->isAdminCheckinAllowed()) {
                return response()->json(['error' => 'Không thể thực hiện check-in cho đơn này.'], 403);
            }

            $booking->load([
                'user',
                'bookingRooms.room.roomType',
                'bookingRooms.roomType',
                'rooms.roomType',
                'bookingGuests.bookingRoom.room.roomType',
            ]);

            // Luôn dùng phòng đầu tiên làm mặc định cho người đại diện
            // (dù 1 hay nhiều phòng — staff có thể đổi slot sau).
            $firstBookingRoom = $booking->bookingRooms->first();
            $defaultBookingRoomId = $firstBookingRoom?->id;

            $resolvedRepCccd = $this->resolveRepresentativeCccdFromBooking($booking);

            // Nếu chưa có khách nào, tự động thêm người đại diện từ đơn / user / khách legacy
            if ($booking->bookingGuests->isEmpty()) {
                $legacyGuests = $this->legacyGuestRecords($booking);
                $legacyRep = $legacyGuests->firstWhere('is_representative', 1)
                    ?? $legacyGuests->first();
                $nameFromLegacy = trim((string) ($legacyRep?->name ?? ''));
                $nameValue = $nameFromLegacy !== ''
                    ? $legacyRep->name
                    : ($booking->user?->full_name ?? $booking->user?->name ?? 'Khách hàng');

                BookingGuest::create(BookingGuest::filterAttributesForStorage([
                    'booking_id' => $booking->id,
                    'name' => $nameValue,
                    'cccd' => $resolvedRepCccd,
                    'type' => 'adult',
                    'status' => 'pending',
                    'is_representative' => 1,
                    'booking_room_id' => $defaultBookingRoomId,
                    'checkin_status' => 'pending',
                ]));

                $booking->load(['bookingGuests.bookingRoom.room.roomType']);
            }

            $booking->load(['bookingGuests.bookingRoom.room.roomType']);

            // Một đơn — một khách đại diện trong booking_guests (không theo từng slot phòng).
            $this->normalizeBookingGuestRepresentativeFlags($booking);
            $booking->load(['bookingGuests.bookingRoom.room.roomType']);

            $repGuest = $booking->bookingGuests->firstWhere('is_representative', true);
            if ($repGuest) {
                $dirty = false;
                if (trim((string) ($repGuest->cccd ?? '')) === '' && $resolvedRepCccd) {
                    $repGuest->cccd = $resolvedRepCccd;
                    $dirty = true;
                }
                if ($repGuest->booking_room_id === null && $defaultBookingRoomId !== null) {
                    $repGuest->booking_room_id = $defaultBookingRoomId;
                    $dirty = true;
                }
                if ($dirty) {
                    $repGuest->save();
                }
            }

            $booking->load(['bookingGuests.bookingRoom.room.roomType']);

            // Lấy danh sách khách
            $guests = $booking->bookingGuests->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'cccd' => $guest->cccd,
                    'type' => BookingGuest::normalizeTypeForStorage((string) ($guest->type ?? 'adult')),
                    'status' => $guest->status,
                    'booking_room_id' => $guest->booking_room_id,
                    'room_id' => $guest->bookingRoom?->room_id,
                    'room_name' => $this->checkInRoomDisplayLabel($guest->bookingRoom?->room),
                ];
            });

            // Parse dates nếu là string
            $checkIn = $booking->check_in;
            $checkOut = $booking->check_out;
            if (is_string($checkIn)) {
                $checkIn = \Carbon\Carbon::parse($checkIn);
            }
            if (is_string($checkOut)) {
                $checkOut = \Carbon\Carbon::parse($checkOut);
            }

            $repBg = $booking->bookingGuests->firstWhere('is_representative', true)
                ?? $booking->bookingGuests->first();
            $repDisplayName = $repBg?->name
                ?? $booking->user?->full_name
                ?? $booking->user?->name;
            $repCccdOut = trim((string) ($repBg?->cccd ?? ''));
            if ($repCccdOut === '' && $resolvedRepCccd) {
                $repCccdOut = $resolvedRepCccd;
            }

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'user' => $booking->user?->only(['id', 'full_name', 'email', 'phone']),
                    'representative' => [
                        'full_name' => $repDisplayName,
                        'email' => $booking->user?->email,
                        'cccd' => $repCccdOut !== '' ? $repCccdOut : null,
                    ],
                    'check_in' => $checkIn?->format('d/m/Y'),
                    'check_out' => $checkOut?->format('d/m/Y'),
                ],
                'guests' => $guests,
                'booking_rooms' => $booking->bookingRooms->values()->map(function ($br, $idx) use ($booking) {
                    $typeName = $br->room?->roomType?->name ?? $br->roomType?->name ?? 'Phòng đã đặt';

                    return [
                        'id'               => $br->id,
                        'room_id'          => $br->room_id,
                        'room_type_name'   => $typeName,
                        'adults'           => (int) ($br->adults ?? 0),
                        'children_6_11'    => (int) ($br->children_6_11 ?? 0),
                        'children_0_5'     => (int) ($br->children_0_5 ?? 0),
                        'slot_label'       => ($idx + 1) . '. ' . $typeName,
                        'room_options'     => $this->roomsSelectableForCheckIn($booking, $br),
                    ];
                }),
            ]);

        } catch (\Throwable $e) {
            Log::error('getCheckInData failed', [
                'booking_id' => $booking->id ?? null,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Lỗi tải dữ liệu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xử lý check-in: admin chọn phòng vật lý theo từng dòng đặt (booking_room), sau đó nhập khách.
     */
    public function checkInWithAssignment(Request $request, Booking $booking)
    {
        try {
            if (!$booking->isAdminCheckinAllowed()) {
                return back()->with('error', 'Không thể thực hiện check-in cho đơn này.');
            }

            $slotPicks = $this->resolveCheckInSlotRoomPicks($request, $booking);
            if ($slotPicks instanceof RedirectResponse) {
                return $slotPicks;
            }

            $result = $this->processGuestAssignmentData($request, $booking, $slotPicks);
            if ($result instanceof RedirectResponse) {
                return $result;
            }

            [$validGuests, $guestsBySlot] = $result;

            return $this->executeCheckInTransaction($booking, $validGuests, $guestsBySlot, $slotPicks);

        } catch (\Throwable $e) {
            Log::error('checkInWithAssignment failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Kiểm tra và trả về map [booking_room_id => room_id] từ form check-in.
     *
     * @return array<int, int>|RedirectResponse
     */
    private function resolveCheckInSlotRoomPicks(Request $request, Booking $booking): array|RedirectResponse
    {
        $slotRoomIds   = $request->input('slot_room_id', []);
        $bookingRooms  = $booking->bookingRooms()->with('room')->get();
        $picks         = [];

        foreach ($bookingRooms as $br) {
            $picked = isset($slotRoomIds[$br->id]) ? (int) $slotRoomIds[$br->id] : 0;
            if ($picked < 1) {
                return back()
                    ->with('error', 'Vui lòng chọn phòng cụ thể cho từng loại phòng đã đặt trước khi check-in.')
                    ->withInput();
            }

            $allowed = collect($this->roomsSelectableForCheckIn($booking, $br))
                ->pluck('room_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($allowed === []) {
                return back()
                    ->with('error', 'Không có phòng vật lý khả dụng cho một dòng đặt trên đơn. Kiểm tra loại phòng hoặc lịch trống.')
                    ->withInput();
            }

            if (! in_array($picked, $allowed, true)) {
                return back()
                    ->with('error', 'Phòng đã chọn không hợp lệ hoặc không còn trống trong kỳ đặt. Vui lòng chọn lại.')
                    ->withInput();
            }

            $picks[(int) $br->id] = $picked;
        }

        return $picks;
    }

    /**
     * Đọc danh sách khách từ request, phân nhóm theo booking_room_id.
     * Phòng vật lý lấy từ lựa chọn admin (slot_room_id), không dùng tạm gán cũ trên booking_room.
     *
     * @param  array<int, int>  $slotRoomPicks
     */
    private function processGuestAssignmentData(Request $request, Booking $booking, array $slotRoomPicks): array|RedirectResponse
    {
        $requestGuests = $request->input('guests', []);
        $validGuests   = [];
        $guestsBySlot  = [];

        $bookingRoomsMap = $booking->bookingRooms()->with('room.roomType', 'roomType')->get()->keyBy('id');
        $defaultBrId     = $bookingRoomsMap->keys()->first();
        $roomBySlot      = [];

        foreach ($requestGuests as $guest) {
            if (empty($guest['name'])) {
                continue;
            }

            $bookingRoomId = isset($guest['booking_room_id']) ? (int) $guest['booking_room_id'] : $defaultBrId;
            $br            = $bookingRoomsMap->get($bookingRoomId);

            if (! $br) {
                return back()->with('error', "Khách «{$guest['name']}»: dòng đặt phòng #{$bookingRoomId} không thuộc đơn này.")->withInput();
            }

            $physicalRoomId = $slotRoomPicks[$bookingRoomId] ?? 0;
            if ($physicalRoomId < 1) {
                return back()->with('error', 'Thiếu phòng vật lý cho một dòng đặt. Vui lòng chọn phòng và thử lại.')->withInput();
            }

            $typeNorm = BookingGuest::normalizeTypeForStorage((string) ($guest['type'] ?? 'adult'));
            $cccdDigits = preg_replace('/\D/', '', (string) ($guest['cccd'] ?? ''));

            if (BookingGuest::isAdultGuestType($typeNorm)) {
                if ($cccdDigits !== '' && strlen($cccdDigits) !== 12) {
                    return back()->with('error', 'Khách người lớn «'.$guest['name'].'»: CCCD nếu có phải đủ 12 chữ số.')->withInput();
                }
            } elseif ($cccdDigits !== '' && strlen($cccdDigits) !== 12) {
                return back()->with('error', 'Trẻ em «'.$guest['name'].'»: Nếu nhập CCCD phải đúng 12 chữ số hoặc để trống.')->withInput();
            }

            $guest['type'] = $typeNorm;
            $guest['cccd'] = $cccdDigits === '' ? null : $cccdDigits;

            $guest['booking_room_id'] = $bookingRoomId;
            $guest['room_id']         = $physicalRoomId;
            $validGuests[]            = $guest;

            if (! isset($guestsBySlot[$bookingRoomId])) {
                if (! isset($roomBySlot[$bookingRoomId])) {
                    $roomBySlot[$bookingRoomId] = Room::query()->with('roomType')->find($physicalRoomId);
                }
                $guestsBySlot[$bookingRoomId] = [
                    'adults'         => 0,
                    'children'       => 0,
                    'children_6_11'  => 0,
                    'children_0_5'   => 0,
                    'room'           => $roomBySlot[$bookingRoomId],
                ];
            }
            if (BookingGuest::isAdultGuestType($guest['type'] ?? 'adult')) {
                $guestsBySlot[$bookingRoomId]['adults']++;
            } else {
                $guestsBySlot[$bookingRoomId]['children']++;
                if (($guest['type'] ?? 'adult') === 'child_6_11') {
                    $guestsBySlot[$bookingRoomId]['children_6_11']++;
                } else {
                    $guestsBySlot[$bookingRoomId]['children_0_5']++;
                }
            }
        }

        if (empty($validGuests)) {
            return back()->with('error', 'Vui lòng thêm ít nhất 1 khách')->withInput();
        }

        foreach ($bookingRoomsMap as $brId => $br) {
            $brId = (int) $brId;
            if (! isset($guestsBySlot[$brId])) {
                return back()->with(
                    'error',
                    'Mỗi phòng trên đơn cần có ít nhất một khách khi check-in. Thiếu dữ liệu cho: '.$br->guestFacingLine()
                )->withInput();
            }
            $hasAdultWithCccd12 = false;
            foreach ($validGuests as $vg) {
                if ((int) ($vg['booking_room_id'] ?? 0) !== $brId) {
                    continue;
                }
                if (! BookingGuest::isAdultGuestType((string) ($vg['type'] ?? 'adult'))) {
                    continue;
                }
                $d = preg_replace('/\D/', '', (string) ($vg['cccd'] ?? ''));
                if (strlen($d) === 12) {
                    $hasAdultWithCccd12 = true;
                    break;
                }
            }
            if (! $hasAdultWithCccd12) {
                return back()->with(
                    'error',
                    'Mỗi phòng cần ít nhất 1 người lớn có CCCD đủ 12 số — người đại diện tại phòng ('.$br->guestFacingLine().'). Một người lớn có CCCD là đủ.'
                )->withInput();
            }

            $slot = $guestsBySlot[$brId];
            $actualAdults = (int) ($slot['adults'] ?? 0);
            $actualChildren611 = (int) ($slot['children_6_11'] ?? 0);
            $actualChildren05 = (int) ($slot['children_0_5'] ?? 0);
            $actualRoom = $slot['room'] ?? null;
            $roomType = $actualRoom?->roomType ?? $br->room?->roomType ?? $br->roomType;
            try {
                RoomOccupancyPricing::validate($actualAdults, $actualChildren611, $actualChildren05, $roomType);
            } catch (\InvalidArgumentException $e) {
                return back()->with(
                    'error',
                    'Phòng '.$br->guestFacingLine().' không hợp lệ về sức chứa: '.$e->getMessage()
                )->withInput();
            }
        }

        return [$validGuests, $guestsBySlot];
    }

    /**
     * @param  array<int, int>  $slotRoomPicks  booking_room.id => rooms.id
     */
    private function executeCheckInTransaction(Booking $booking, array $validGuests, array $guestsBySlot, array $slotRoomPicks): RedirectResponse
    {
        DB::beginTransaction();
        try {
            foreach ($slotRoomPicks as $brId => $roomId) {
                BookingRoom::query()
                    ->where('booking_id', $booking->id)
                    ->where('id', $brId)
                    ->update(['room_id' => $roomId]);
            }
            $booking->refresh();
            $this->syncRoomBookedDatesForBooking($booking);

            $bookingRooms = $booking->bookingRooms()->with('room')->get();
            $this->saveGuestsForCheckin($booking, $validGuests, $bookingRooms);
            $booking->refresh();
            $booking->loadMissing('user');
            $this->normalizeBookingGuestRepresentativeFlags($booking);
            $occupancyPricing = $this->reconcileOccupancyAtCheckIn($booking, $guestsBySlot);
            $result = $this->finalizeCheckInWithAssignment($booking, $guestsBySlot, $occupancyPricing);
            DB::commit();
            return $result;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('executeCheckInTransaction failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Lỗi: ' . $e->getMessage())->withInput();
        }
    }

    private function saveGuestsForCheckin(Booking $booking, array $validGuests, $bookingRooms): void
    {
        $existingGuestIds = $booking->guests()->pluck('id')->toArray();
        $processedGuestIds = [];
        $repIndex = $this->representativeGuestIndexForCheckIn($booking, $validGuests);

        foreach ($validGuests as $index => $guestData) {
            $roomId = (int) $guestData['room_id'];
            $bookingRoomId = isset($guestData['booking_room_id']) ? (int) $guestData['booking_room_id'] : null;
            if (! $bookingRoomId || ! $bookingRooms->firstWhere('id', $bookingRoomId)) {
                $bookingRoom = $bookingRooms->firstWhere('room_id', $roomId);
                $bookingRoomId = $bookingRoom?->id ?? $bookingRooms->first()?->id;
            }

            $isRepresentative = $index === $repIndex;

            // Lưu vào bảng guests (legacy)
            $guestId = $this->saveLegacyGuest($booking, $guestData, $roomId, $existingGuestIds, $isRepresentative);
            if ($guestId) {
                $processedGuestIds[] = $guestId;
            }

            // Lưu vào bảng booking_guests (mới)
            $this->saveBookingGuest($booking, $guestData, $bookingRoomId, $isRepresentative);
        }

        // Xóa khách không còn trong danh sách
        $guestsToDelete = array_diff($existingGuestIds, $processedGuestIds);
        if (!empty($guestsToDelete)) {
            Guest::whereIn('id', $guestsToDelete)->delete();
        }
    }

    /** Lưu khách vào bảng guests (legacy). */
    private function saveLegacyGuest(Booking $booking, array $guestData, int $roomId, array $existingGuestIds, bool $isRepresentative): ?int
    {
        if (isset($guestData['id']) && in_array($guestData['id'], $existingGuestIds)) {
            $guest = Guest::find($guestData['id']);
            if ($guest && $guest->booking_id == $booking->id) {
                $guest->update([
                    'name' => $guestData['name'],
                    'cccd' => (($c = trim((string) ($guestData['cccd'] ?? ''))) === '' ? null : $c),
                    'type' => BookingGuest::normalizeTypeForStorage((string) ($guestData['type'] ?? 'adult')),
                    'room_id' => $roomId,
                    'checkin_status' => 'checked_in',
                    'is_representative' => $isRepresentative ? 1 : 0,
                ]);
                return $guest->id;
            }
        } else {
            $guest = Guest::create([
                'booking_id' => $booking->id,
                'room_id' => $roomId,
                'name' => $guestData['name'],
                'cccd' => (($c = trim((string) ($guestData['cccd'] ?? ''))) === '' ? null : $c),
                'type' => BookingGuest::normalizeTypeForStorage((string) ($guestData['type'] ?? 'adult')),
                'checkin_status' => 'checked_in',
                'is_representative' => $isRepresentative ? 1 : 0,
            ]);
            return $guest->id;
        }
        return null;
    }

    /** Lưu khách vào bảng booking_guests (mới). */
    private function saveBookingGuest(Booking $booking, array $guestData, ?int $bookingRoomId, bool $isRepresentative): void
    {
        $bgGuest = null;
        if (! empty($guestData['id'])) {
            $bgGuest = BookingGuest::where('booking_id', $booking->id)
                ->where('id', (int) $guestData['id'])
                ->first();
        }
        if (! $bgGuest) {
            $bgGuest = BookingGuest::where('booking_id', $booking->id)
                ->where('name', $guestData['name'])
                ->first();
        }

        $type = BookingGuest::normalizeTypeForStorage((string) ($guestData['type'] ?? 'adult'));
        $cccdStored = (($c = trim((string) ($guestData['cccd'] ?? ''))) === '' ? null : $c);

        $data = BookingGuest::filterAttributesForStorage([
            'cccd' => $cccdStored,
            'type' => $type,
            'status' => 'checked_in',
            'checkin_status' => 'checked_in',
            'booking_room_id' => $bookingRoomId,
            'is_representative' => $isRepresentative ? 1 : 0,
        ]);

        if ($bgGuest) {
            $bgGuest->update($data);
        } else {
            BookingGuest::create(BookingGuest::filterAttributesForStorage(array_merge($data, [
                'booking_id' => $booking->id,
                'booking_room_id' => $bookingRoomId,
                'name' => $guestData['name'],
            ])));
        }
    }

    /**
     * Đối soát số người thực tế khi check-in với dữ liệu đặt ban đầu theo từng booking_room.
     * Nếu tăng người hợp lệ theo chính sách sức chứa, tự tạo phụ thu chênh lệch.
     *
     * @return array{changed_rooms:int, added_surcharge:float}
     */
    private function reconcileOccupancyAtCheckIn(Booking $booking, array $guestsBySlot): array
    {
        $bookingRooms = $booking->bookingRooms()->with('room.roomType', 'roomType')->get()->keyBy('id');
        $changedRooms = 0;
        $addedSurcharge = 0.0;
        $notes = [];

        foreach ($guestsBySlot as $bookingRoomId => $slot) {
            $br = $bookingRooms->get((int) $bookingRoomId);
            if (! $br) {
                continue;
            }

            $plannedAdults = max(1, (int) ($br->adults ?? 1));
            $plannedChildren611 = max(0, (int) ($br->children_6_11 ?? 0));
            $plannedChildren05 = max(0, (int) ($br->children_0_5 ?? 0));

            $actualAdults = max(0, (int) ($slot['adults'] ?? 0));
            $actualChildren611 = max(0, (int) ($slot['children_6_11'] ?? 0));
            $actualChildren05 = max(0, (int) ($slot['children_0_5'] ?? 0));

            if (
                $plannedAdults === $actualAdults
                && $plannedChildren611 === $actualChildren611
                && $plannedChildren05 === $actualChildren05
            ) {
                continue;
            }

            $changedRooms++;

            $lineLabel = $br->guestFacingLine();
            $plannedText = $this->formatOccupancyText($plannedAdults, $plannedChildren611, $plannedChildren05);
            $actualText = $this->formatOccupancyText($actualAdults, $actualChildren611, $actualChildren05);

            $roomType = ($slot['room'] ?? null)?->roomType ?? $br->room?->roomType ?? $br->roomType;
            $deltaAmount = 0.0;
            try {
                $bookedUnit = RoomOccupancyPricing::breakdown(1.0, $plannedAdults, $plannedChildren611, $plannedChildren05, $roomType);
                $actualUnit = RoomOccupancyPricing::breakdown(1.0, $actualAdults, $actualChildren611, $actualChildren05, $roomType);

                $bookedPricePerNight = (float) ($br->price_per_night ?? 0);
                $nights = max(1, (int) ($br->nights ?? 1));
                if ($bookedPricePerNight <= 0) {
                    $bookedPricePerNight = round(((float) ($br->subtotal ?? 0)) / $nights, 2);
                }

                $bookedUnitMultiplier = max(0.0001, (float) ($bookedUnit['price_per_night'] ?? 1.0));
                $estimatedBasePrice = $bookedPricePerNight / $bookedUnitMultiplier;
                $actualPricePerNight = $estimatedBasePrice * (float) ($actualUnit['price_per_night'] ?? 1.0);
                $deltaPerNight = $actualPricePerNight - $bookedPricePerNight;
                $deltaAmount = round($deltaPerNight * $nights, 2);
            } catch (\Throwable $e) {
                Log::warning('checkin_occupancy_pricing_reconcile_failed', [
                    'booking_id' => $booking->id,
                    'booking_room_id' => $br->id,
                    'message' => $e->getMessage(),
                ]);
                throw new \RuntimeException(
                    'Không thể đối soát giá theo số người check-in cho '.$lineLabel.'. Vui lòng kiểm tra dữ liệu sức chứa/phụ thu loại phòng trước khi check-in.'
                );
            }

            if ($deltaAmount > 0) {
                $booking->surcharges()->create([
                    'service_id' => null,
                    'reason' => 'Phụ thu chênh lệch số người check-in ('.$lineLabel.'): đặt '.$plannedText.', nhận '.$actualText,
                    'quantity' => 1,
                    'amount' => $deltaAmount,
                ]);
                $addedSurcharge += $deltaAmount;
                $notes[] = $lineLabel.': '.$plannedText.' -> '.$actualText.' (+'.number_format($deltaAmount, 0, ',', '.').' đ)';
            } else {
                $notes[] = $lineLabel.': '.$plannedText.' -> '.$actualText.' (không tự động giảm/hoàn tiền)';
            }
        }

        if ($addedSurcharge > 0) {
            $booking->total_price = (float) $booking->total_price + $addedSurcharge;
            $booking->save();

            $payment = $booking->payments()->orderByDesc('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->amount = (float) $payment->amount + $addedSurcharge;
                $payment->save();
            }
        }

        if ($notes !== []) {
            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'old_status' => $booking->status,
                'new_status' => $booking->status,
                'notes' => 'Đối soát số người check-in: '.implode(' | ', $notes),
                'changed_at' => now(),
            ]);
        }

        $booking->reconcilePaymentStatusWithPayments();

        if ($changedRooms > 0) {
            $booking->refresh();
            BookingFinancialAudit::record($booking, 'checkin_occupancy_reconcile', [
                'changed_rooms' => $changedRooms,
                'added_surcharge' => round($addedSurcharge, 2),
                'note_lines' => $notes,
            ], Auth::id());
        }

        return [
            'changed_rooms' => $changedRooms,
            'added_surcharge' => round($addedSurcharge, 2),
        ];
    }

    private function formatOccupancyText(int $adults, int $children611, int $children05): string
    {
        $parts = [];
        if ($adults > 0) {
            $parts[] = $adults.' NL';
        }
        if ($children611 > 0) {
            $parts[] = $children611.' TE 6-11';
        }
        if ($children05 > 0) {
            $parts[] = $children05.' TE 0-5';
        }

        return $parts === [] ? '0 khách' : implode(', ', $parts);
    }

    private function finalizeCheckInWithAssignment(
        Booking $booking,
        array $guestsBySlot,
        array $occupancyPricing = []
    ): \Illuminate\Http\RedirectResponse
    {
        $old = $booking->status;
        $booking->status = 'checked_in';
        $booking->actual_check_in = Carbon::now();
        $booking->save();

        $occupiedIds = $booking->bookingRooms()
            ->whereNotNull('room_id')
            ->pluck('room_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        if ($occupiedIds !== []) {
            Room::whereIn('id', $occupiedIds)->update(['status' => 'booked']);
        }

        /** @var \App\Models\User|null $user */
        $user      = Auth::user();
        $staffName = $user?->full_name ?? 'Lễ tân';
        $rooms     = $booking->bookingRooms()->with('room')->get()
            ->map(fn ($br) => $br->room?->room_number ?? $br->room?->name)
            ->filter()->implode(', ');
        $roomText = $rooms ? " phòng {$rooms}" : '';

        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id'    => Auth::id(),
            'old_status' => $old,
            'new_status' => 'checked_in',
            'notes'      => "{$staffName} check-in{$roomText}.",
            'changed_at' => now(),
        ]);

        Cache::forget("guest_info_{$booking->id}");

        $totalAdults   = collect($guestsBySlot)->sum('adults');
        $totalChildren = collect($guestsBySlot)->sum('children');
        $roomNumbers   = collect($guestsBySlot)->map(fn ($s) => $s['room']?->room_number ?? $s['room']?->name)->filter()->implode(', ');

        $msg = "Check-in thành công! {$totalAdults} người lớn";
        if ($totalChildren) $msg .= ", {$totalChildren} trẻ em";
        if ($roomNumbers)   $msg .= " — Phòng: {$roomNumbers}";
        $addedSurcharge = (float) ($occupancyPricing['added_surcharge'] ?? 0);
        $changedRooms = (int) ($occupancyPricing['changed_rooms'] ?? 0);
        if ($addedSurcharge > 0) {
            $msg .= ' — Phụ thu chênh lệch người ở: '.number_format($addedSurcharge, 0, ',', '.').' ₫';
        } elseif ($changedRooms > 0) {
            $msg .= ' — Đã ghi nhận chênh lệch số người thực tế khi check-in.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Cập nhật thông tin người đại diện
     */
    public function updateRepresentative(Request $request, Booking $booking)
    {
        try {
            $validated = $request->validate([
                'representative_name' => 'required|string|max:150',
                'cccd' => 'nullable|string|max:20',
            ]);

            $booking->update([
                'representative_name' => $validated['representative_name'],
                'cccd' => $validated['cccd'] ?? null,
            ]);

            return back()->with('success', 'Đã cập nhật thông tin người đại diện');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function deleteBookingGuest(BookingGuest $bookingGuest)
    {
        try {
            $booking = Booking::find($bookingGuest->booking_id);

            // Kiểm tra quyền (admin hoặc staff)
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if (!$booking || !$user) {
                return response()->json(['error' => 'Không có quyền'], 403);
            }
            /** @var \Illuminate\Database\Eloquent\Collection $roles */
            $roles = $user->roles();
            $hasRole = $roles->whereIn('name', ['admin', 'staff'])->exists();
            if (!$hasRole) {
                return response()->json(['error' => 'Không có quyền'], 403);
            }

            // Không cho xóa nếu đã check-in
            if ($bookingGuest->status === 'checked_in') {
                return response()->json(['error' => 'Không thể xóa khách đã check-in'], 400);
            }

            $bookingGuest->delete();

            return response()->json(['success' => true, 'message' => 'Đã xóa khách']);

        } catch (\Throwable $e) {
            Log::error('deleteBookingGuest failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    public function deleteBookingService(BookingServiceRow $bookingService)
    {
        try {
            $booking = Booking::find($bookingService->booking_id);

            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if (!$booking || !$user) {
                return back()->with('error', 'Không có quyền xóa dịch vụ.');
            }

            $roles = $user->roles();
            if (!$roles->whereIn('name', ['admin', 'staff'])->exists()) {
                return back()->with('error', 'Không có quyền xóa dịch vụ.');
            }

            if ($booking->status === 'cancelled' || !is_null($booking->actual_check_out)) {
                return back()->with('error', 'Không thể xóa dịch vụ của đơn đã hủy hoặc đã checkout.');
            }

            DB::beginTransaction();

            $lineTotal = (float) $bookingService->price * (int) $bookingService->quantity;
            $bookingService->delete();

            $booking->total_price = max(0.0, (float) $booking->total_price - $lineTotal);
            $booking->save();

            $payment = $booking->payments()->orderByDesc('id')->first();
            if ($payment && $payment->status === 'pending') {
                $payment->amount = max(0.0, (float) $payment->amount - $lineTotal);
                $payment->save();
            }

            $invoiceNote = $this->syncInvoiceExtrasIfExists($booking);

            DB::commit();

            $booking->refresh();
            $booking->reconcilePaymentStatusWithPayments();

            return back()->with('success', 'Đã xóa dịch vụ.' . $invoiceNote);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('deleteBookingService failed', [
                'booking_service_id' => $bookingService->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Có lỗi khi xóa dịch vụ: ' . $e->getMessage());
        }
    }

    public function changeRoom(RoomChangeRequest $request, Booking $booking, RoomChangeService $roomChangeService): \Illuminate\Http\RedirectResponse
    {
        // Điều phối về RoomChangeService để đồng nhất công thức tính chênh lệch, ghi history và log tài chính.
        return $this->changeRoomV2($request, $booking, $roomChangeService);
    }

    private function isRoomOccupied(int $roomId, int $bookingId, string $checkIn, string $checkOut): bool
    {
        return RoomBookedDate::where('room_id', $roomId)
            ->where('booking_id', '!=', $bookingId)
            ->whereBetween('booked_date', [$checkIn, \Carbon\Carbon::parse($checkOut)->subDay()->toDateString()])
            ->exists();
    }

    /**
     * @return list<array{service_id: int, quantity: int}>
     */
    private function normalizeSvcCatalogRows(Request $request): array
    {
        $raw = $request->input('svc_items', []);
        if (! is_array($raw)) {
            return [];
        }

        $filtered = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sid = $row['service_id'] ?? null;
            $qty = (int) ($row['quantity'] ?? 0);
            if ($sid === null || $sid === '' || $qty < 1) {
                continue;
            }
            $filtered[] = [
                'service_id' => (int) $sid,
                'quantity' => $qty,
            ];
        }

        return $filtered;
    }

    private function guardBookingOpenForExtras(Booking $booking): ?RedirectResponse
    {
        if ($booking->status === 'cancelled') {
            return back()->with('error', 'Không thể thao tác: đơn đã hủy.');
        }
        if ($booking->actual_check_out !== null) {
            return back()->with('error', 'Không thể thao tác: đơn đã checkout.');
        }

        return null;
    }

    private function calculateBookingServicesTotal(Booking $booking): float
    {
        return (float) $booking->bookingServices()->get()->sum(function ($bs) {
            return $bs->quantity * $bs->price;
        });
    }

    /**
     * Chỉ cập nhật mức «chốt thanh toán» trên giao dịch pending (VNPay / chờ chuyển khoản).
     * Không ghi đè payments.amount khi đã paid — đó là tiền thực thu; phụ phí/dịch vụ/gia hạn sau đó chỉ làm tăng total_price và công nợ.
     */
    private function updatePaymentAmount(Booking $booking): void
    {
        $booking->refresh();
        $payment = Payment::where('booking_id', $booking->id)->orderByDesc('id')->first();
        if ($payment && $payment->status === 'pending') {
            $payment->update([
                'amount' => (float) $booking->total_price,
            ]);
        }

        $booking->reconcilePaymentStatusWithPayments();
    }

    /**
     * Thay đổi phòng cho khách hàng - Sử dụng RoomChangeService (NEW)
     */
    public function changeRoomV2(RoomChangeRequest $request, Booking $booking, RoomChangeService $roomChangeService)
    {
        try {
            $result = $roomChangeService->changeRoom(
                $booking,
                (int) $request->old_room_id,
                (int) $request->new_room_id,
                $request->reason,
                Auth::id(),
                null,
                $request->boolean('is_emergency'),
                $request->boolean('keep_price')
            );

            $message = 'Đổi phòng thành công!';
            if ($result['price_difference'] > 0) {
                $message .= ' Giá tăng thêm: ' . number_format($result['price_difference'], 0, ',', '.') . ' ₫';
            } elseif ($result['price_difference'] < 0) {
                $message .= ' Giá giảm: ' . number_format(abs($result['price_difference']), 0, ',', '.') . ' ₫';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Room change failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Đổi phòng thất bại: ' . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách phòng có thể đổi (API)
     */
    public function getAvailableRoomsForChange(Request $request, Booking $booking, RoomChangeService $roomChangeService)
    {
        $request->validate([
            'current_room_id' => 'required|integer|exists:rooms,id',
        ]);

        $rooms = $roomChangeService->getAvailableRoomsForChange(
            $booking,
            (int) $request->current_room_id
        );

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    /**
     * Lấy lịch sử đổi phòng của booking (API)
     */
    public function getRoomChangeHistory(Booking $booking, RoomChangeService $roomChangeService)
    {
        $history = $roomChangeService->getChangeHistory($booking->id);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
