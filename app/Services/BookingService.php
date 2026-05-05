<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Coupon;
use App\Models\BookingRoom;
use App\Models\Guest;
use App\Support\RoomOccupancyPricing;
use App\Exceptions\BookingException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class BookingService
{
    /**
     * Tính giá + kiểm tra tồn kho chỗ (đọc DB, không khóa bản ghi đặt phòng).
     *
     * @return array{
     *   checkIn: \Carbon\Carbon,
     *   checkOut: \Carbon\Carbon,
     *   totalPrice: float,
     *   discountAmount: float,
     *   couponCode: string|null,
     *   roomPriceDetails: array,
     *   neededByType: array<int, int>,
     *   dates: \Illuminate\Support\Collection<int, string>
     * }
     *
     * @throws BookingException
     */
    public function composeCheckoutContext(array $data): array
    {
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights <= 0) {
            throw new BookingException('Ngày trả phòng phải sau ngày nhận phòng.');
        }
        if ($nights > 30) {
            throw new BookingException('Bạn chỉ có thể đặt phòng tối đa 30 đêm.');
        }

        $roomTypes = [];
        $pricingRooms = [];
        foreach ($data['room_type_ids'] as $index => $typeId) {
            $roomType = RoomType::find($typeId);
            if (! $roomType) {
                throw new BookingException("Loại phòng ID {$typeId} không tồn tại.");
            }
            $roomTypes[$index] = $roomType;
            $pricingRoom = Room::query()
                ->where('room_type_id', $roomType->id)
                ->vacantForGuestBookingWindow($checkIn->toDateString(), $checkOut->toDateString())
                ->orderBy('id')
                ->first();
            if (! $pricingRoom) {
                throw new BookingException('Loại phòng "'.$roomType->name.'" không còn phòng trống trong khoảng ngày đã chọn (hoặc đang có khách).');
            }
            $pricingRooms[$index] = $pricingRoom;
        }

        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        /** @var \Illuminate\Support\Collection<int, string> $dates */
        $dates = collect($period)->map(fn ($date) => $date->toDateString());

        $neededByType = [];
        foreach ($data['room_type_ids'] as $typeId) {
            $tid = (int) $typeId;
            $neededByType[$tid] = ($neededByType[$tid] ?? 0) + 1;
        }

        foreach ($neededByType as $typeId => $needQty) {
            $physical = $this->countPhysicalFreeRoomsOfType($typeId, $dates);
            $unassigned = BookingRoom::unassignedCountForRoomTypeBetween(
                $typeId,
                $checkIn->toDateString(),
                $checkOut->toDateString()
            );
            if ($physical - $unassigned < $needQty) {
                $name = RoomType::find((int) $typeId)?->name ?? 'Loại phòng';
                throw new BookingException("Không đủ phòng trống cho \"{$name}\" trong khoảng thời gian này (đã trừ các đơn đang giữ chỗ hợp lệ).");
            }
        }

        $totalPrice = 0;
        $roomPriceDetails = [];

        foreach ($pricingRooms as $index => $room) {
            $guestAdults = (int) ($data['adults'][$index] ?? 1);
            $guest05 = (int) ($data['children_0_5'][$index] ?? 0);
            $guest611 = (int) ($data['children_6_11'][$index] ?? 0);
            $roomType = $roomTypes[$index];

            try {
                $priceData = $this->calculateRoomPrice($room, $nights, $guestAdults, $guest05, $guest611);

                $totalPrice += $priceData['subtotal'];
                $roomPriceDetails[] = array_merge($priceData, [
                    'room_type_id' => (int) $roomType->id,
                    'nights' => $nights,
                    'adults' => $guestAdults,
                    'children_0_5' => $guest05,
                    'children_6_11' => $guest611,
                ]);
            } catch (\Exception $e) {
                throw new BookingException($e->getMessage());
            }
        }

        $discountAmount = 0;
        $couponCode = $data['coupon_code'] ?? null;
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expired_at')
                        ->orWhere('expired_at', '>=', Carbon::today()->toDateString());
                })
                ->first();

            if ($coupon) {
                $discountAmount = ($totalPrice * $coupon->discount_percent) / 100;
                $totalPrice -= $discountAmount;
            } else {
                throw new BookingException('Mã giảm giá không hợp lệ hoặc đã hết hạn.');
            }
        }

        return [
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'totalPrice' => $totalPrice,
            'discountAmount' => $discountAmount,
            'couponCode' => $couponCode,
            'roomPriceDetails' => $roomPriceDetails,
            'neededByType' => $neededByType,
            'dates' => $dates,
        ];
    }

    /**
     * Ghi nhận đặt phòng web sau khi VNPay thành công (bắt buộc có mã giao dịch — không tạo đơn treo chưa trả tiền).
     *
     * @throws BookingException
     */
    public function createBooking(
        array $data,
        ?string $vnPayTxnNo = null,
        ?int $expectedAmountVnd = null
    ): Booking {
        $ctx = $this->composeCheckoutContext($data);

        $totalRounded = (int) round((float) $ctx['totalPrice']);
        if ($expectedAmountVnd !== null && $totalRounded !== $expectedAmountVnd) {
            throw new BookingException(
                'Số tiền thanh toán không còn khớp giá đã chốt trong phiên. Vui lòng đặt lại để được báo giá mới nhất.'
            );
        }

        $checkIn = $ctx['checkIn'];
        $checkOut = $ctx['checkOut'];
        $totalPrice = $ctx['totalPrice'];
        $discountAmount = $ctx['discountAmount'];
        $couponCode = $ctx['couponCode'];
        /** @var array<int, mixed> $roomPriceDetails */
        $roomPriceDetails = $ctx['roomPriceDetails'];
        /** @var array<int, int> $neededByType */
        $neededByType = $ctx['neededByType'];
        /** @var \Illuminate\Support\Collection<int, string> $dates */
        $dates = $ctx['dates'];

        if ($vnPayTxnNo === null || trim((string) $vnPayTxnNo) === '') {
            throw new BookingException('Không thể tạo đơn khi chưa có giao dịch thanh toán thành công.');
        }

        $bookingStatus = 'confirmed';
        $bookingPaymentStatus = 'paid';

        return DB::transaction(function () use (
            $data,
            $checkIn,
            $checkOut,
            $totalPrice,
            $discountAmount,
            $couponCode,
            $roomPriceDetails,
            $neededByType,
            $dates,
            $bookingStatus,
            $bookingPaymentStatus,
            $vnPayTxnNo
        ) {
            foreach ($neededByType as $typeId => $needQty) {
                Room::query()
                    ->where('room_type_id', (int) $typeId)
                    ->vacantForGuestBookingWindow($checkIn->toDateString(), $checkOut->toDateString())
                    ->lockForUpdate()
                    ->pluck('id');

                $physical = $this->countPhysicalFreeRoomsOfType((int) $typeId, $dates);
                $unassigned = BookingRoom::unassignedCountForRoomTypeBetween(
                    (int) $typeId,
                    $checkIn->toDateString(),
                    $checkOut->toDateString()
                );
                if ($physical - $unassigned < $needQty) {
                    $name = RoomType::find((int) $typeId)?->name ?? 'Loại phòng';
                    throw new BookingException("Không đủ phòng trống cho \"{$name}\" trong khoảng thời gian này — có thể vừa có người đặt trước. Vui lòng thử lại hoặc chọn ngày/phòng khác.");
                }
            }

            if (Auth::check()) {
                $userId = (int) Auth::id();
            } else {
                $email = Str::lower(trim((string) $data['email']));
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'full_name' => $data['full_name'],
                        'phone' => $data['phone'] ?? null,
                        'password' => bcrypt(Str::random(32)),
                    ]
                );
                $user->forceFill([
                    'email' => $email,
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'] ?? $user->phone,
                ])->save();
                $userId = $user->id;
            }

            $booking = Booking::create([
                'user_id' => $userId,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'total_price' => $totalPrice,
                'status' => $bookingStatus,
                'payment_status' => $bookingPaymentStatus,
                'payment_method' => $data['payment_method'],
                'placed_via' => Booking::PLACED_VIA_CUSTOMER_WEB,
                'coupon_code' => $couponCode,
                'discount_amount' => $discountAmount,
            ]);

            foreach ($roomPriceDetails as $detail) {
                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_type_id' => $detail['room_type_id'],
                    'room_id' => null,
                    'adults' => $detail['adults'],
                    'children_0_5' => $detail['children_0_5'],
                    'children_6_11' => $detail['children_6_11'],
                    'price_per_night' => $detail['price_per_night'],
                    'nights' => $detail['nights'],
                    'subtotal' => $detail['subtotal'],
                ]);
            }

            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $totalPrice,
                'method' => $data['payment_method'],
                'status' => 'paid',
                'transaction_id' => $vnPayTxnNo,
                'paid_at' => now(),
            ]);

            $repName = trim((string) ($data['name'] ?? ''));
            if ($repName !== '') {
                $repCccd = trim((string) ($data['cccd'] ?? ''));
                Guest::create([
                    'booking_id' => $booking->id,
                    'room_type' => null,
                    'room_index' => 0,
                    'name' => $repName,
                    'cccd' => $repCccd !== '' ? $repCccd : null,
                    'type' => 'adult',
                    'is_representative' => 1,
                    'checkin_status' => 'pending',
                ]);
            }

            return $booking;
        });
    }

    /**
     * Đơn admin VNPay: sau khi cổng thanh toán thành công, tạo các dòng booking_rooms từ payload (trước đó không chiếm slot).
     *
     * @throws BookingException
     */
    public function materializeAdminPendingCheckoutFromPayload(Booking $booking): void
    {
        $booking->refresh();
        $payload = $booking->pending_checkout_payload;
        if (! is_array($payload) || empty($payload['mode'])) {
            return;
        }

        if ($booking->bookingRooms()->exists()) {
            Booking::query()->whereKey($booking->id)->update(['pending_checkout_payload' => null]);

            return;
        }

        $dates = $payload['dates'] ?? [];
        if (! is_array($dates) || $dates === []) {
            Booking::query()->whereKey($booking->id)->update(['pending_checkout_payload' => null]);

            return;
        }

        $mode = (string) $payload['mode'];
        if ($mode === 'single') {
            $calculatedRoomData = [[
                'room_type_id' => (int) ($payload['room_type_id'] ?? 0),
                'quantity' => 1,
                'actualPricePerNight' => (float) ($payload['price_per_night'] ?? 0),
                'roomSubtotalPerRoom' => (float) ($payload['subtotal'] ?? 0),
                'adults' => (int) ($payload['adults'] ?? 1),
                'children05' => (int) ($payload['children_0_5'] ?? 0),
                'children611' => (int) ($payload['children_6_11'] ?? 0),
            ]];
        } elseif ($mode === 'multi') {
            $raw = $payload['calculated_room_data'] ?? $payload['calculatedRoomData'] ?? [];
            $calculatedRoomData = is_array($raw) ? array_values($raw) : [];
            if ($calculatedRoomData === []) {
                Booking::query()->whereKey($booking->id)->update(['pending_checkout_payload' => null]);

                return;
            }
        } else {
            return;
        }

        DB::transaction(function () use ($booking, $calculatedRoomData, $dates): void {
            $this->createUnassignedBookingRoomLines($booking, $calculatedRoomData, $dates);
            Booking::query()->whereKey($booking->id)->update(['pending_checkout_payload' => null]);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $calculatedRoomData
     * @param  array<int, string>  $dates
     *
     * @throws BookingException
     */
    private function createUnassignedBookingRoomLines(Booking $booking, array $calculatedRoomData, array $dates): void
    {
        foreach ($calculatedRoomData as $calculated) {
            $roomTypeId = (int) ($calculated['room_type_id'] ?? 0);
            $quantity = (int) ($calculated['quantity'] ?? 0);
            if ($roomTypeId < 1 || $quantity < 1) {
                continue;
            }

            $physicalAvailable = Room::query()
                ->where('room_type_id', $roomTypeId)
                ->vacantForGuestBookingWindow(
                    Carbon::parse((string) $booking->check_in)->toDateString(),
                    Carbon::parse((string) $booking->check_out)->toDateString()
                )
                ->count();
            $unassigned = BookingRoom::unassignedCountForRoomTypeBetween(
                $roomTypeId,
                (string) $booking->check_in,
                (string) $booking->check_out
            );
            $bookableSlots = max(0, $physicalAvailable - $unassigned);
            if ($bookableSlots < $quantity) {
                $name = RoomType::find($roomTypeId)?->name ?? 'Loại phòng';
                throw new BookingException(
                    "Sau thanh toán, không còn đủ phòng trống cho \"{$name}\" trong khoảng ngày đã chọn. Liên hệ lễ tân để xử lý hoàn tiền."
                );
            }

            for ($i = 0; $i < $quantity; $i++) {
                $booking->bookingRooms()->create([
                    'room_type_id' => $roomTypeId,
                    'room_id' => null,
                    'price_per_night' => (float) ($calculated['actualPricePerNight'] ?? 0),
                    'nights' => count($dates),
                    'subtotal' => (float) ($calculated['roomSubtotalPerRoom'] ?? 0),
                    'adults' => (int) ($calculated['adults'] ?? 1),
                    'children_0_5' => (int) ($calculated['children05'] ?? $calculated['children_0_5'] ?? 0),
                    'children_6_11' => (int) ($calculated['children611'] ?? $calculated['children_6_11'] ?? 0),
                ]);
            }
        }
    }

    /**
     * Đếm phòng vật lý trống thật trong cửa sổ ngày (đồng bộ với {@see Room::scopeVacantForGuestBookingWindow}).
     *
     * @param  \Illuminate\Support\Collection<int, string>  $dates
     */
    private function countPhysicalFreeRoomsOfType(int $roomTypeId, Collection $dates): int
    {
        if ($dates->isEmpty()) {
            return 0;
        }

        $checkIn = (string) $dates->min();
        $checkOut = Carbon::parse((string) $dates->max())->addDay()->toDateString();

        return (int) Room::query()
            ->where('room_type_id', $roomTypeId)
            ->vacantForGuestBookingWindow($checkIn, $checkOut)
            ->count();
    }

    /**
     * Calc room price based on guests and nights.
     */
    private function calculateRoomPrice(Room $room, int $nights, int $adults, int $children_0_5, int $children_6_11): array
    {
        $basePrice = (float) $room->catalogueBasePrice();
        $roomType = $room->roomType;

        RoomOccupancyPricing::validate($adults, $children_6_11, $children_0_5, $roomType);

        $t = RoomOccupancyPricing::total($basePrice, $nights, $adults, $children_6_11, $children_0_5, $roomType);

        return [
            'base_price' => $basePrice,
            'extra_adult_fee' => $t['adult_surcharge_per_night'] * $nights,
            'child_fee' => $t['child_surcharge_per_night'] * $nights,
            'price_per_night' => $t['price_per_night'],
            'subtotal' => $t['grand_total'],
            'nights' => $nights,
        ];
    }
}
