<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\RoomBookedDate;
use App\Models\User;
use App\Models\Coupon;
use App\Models\BookingRoom;
use App\Support\RoomOccupancyPricing;
use App\Exceptions\BookingException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BookingService
{
    /**
     * Create a new booking with all associated records.
     *
     * @param array $data
     * @return Booking
     * @throws BookingException
     */
    public function createBooking(array $data): Booking
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

        // Nhiều phòng cùng loại trong một đơn là hợp lệ.

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
                ->where('status', 'available')
                ->excludeMaintenance()
                ->orderBy('id')
                ->first();
            if (! $pricingRoom) {
                throw new BookingException('Loại phòng "'.$roomType->name.'" chưa có phòng trống (không bảo trì) để báo giá.');
            }
            $pricingRooms[$index] = $pricingRoom;
        }

        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
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
                $name = RoomType::find($typeId)?->name ?? 'Loại phòng';
                throw new BookingException("Không đủ phòng trống cho \"{$name}\" trong khoảng thời gian này (đã trừ các đơn chưa gán số phòng).");
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
                    'room_type_id'  => (int) $roomType->id,
                    'nights'        => $nights,
                    'adults'        => $guestAdults,
                    'children_0_5'  => $guest05,
                    'children_6_11' => $guest611,
                ]);
            } catch (\Exception $e) {
                throw new BookingException($e->getMessage());
            }
        }

        // 5. Xử lý mã giảm giá
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

        // 6. Thực hiện giao dịch DB — khóa phòng theo từng loại và kiểm tra lại chỗ trống trong transaction (tránh race đặt trùng).
        return DB::transaction(function () use ($data, $checkIn, $checkOut, $totalPrice, $discountAmount, $couponCode, $roomPriceDetails, $neededByType, $dates) {
            foreach ($neededByType as $typeId => $needQty) {
                Room::query()
                    ->where('room_type_id', (int) $typeId)
                    ->where('status', 'available')
                    ->excludeMaintenance()
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
                    throw new BookingException("Không đủ phòng trống cho \"{$name}\" trong khoảng thời gian này (đã trừ các đơn chưa gán số phòng).");
                }
            }

            // Một đơn luôn gắn user_id (đăng nhập hoặc user tạm theo email — chưa gắn role guest, xem User::isProvisionalGuestAccount).
            if (Auth::check()) {
                $userId = (int) Auth::id();
            } else {
                $email = Str::lower(trim((string) $data['email']));
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'full_name' => $data['full_name'],
                        'phone'     => $data['phone'] ?? null,
                        'password'  => bcrypt(Str::random(32)),
                    ]
                );
                $user->forceFill([
                    'email'     => $email,
                    'full_name' => $data['full_name'],
                    'phone'     => $data['phone'] ?? $user->phone,
                ])->save();
                $userId = $user->id;
            }

            // Tạo Booking
            $booking = Booking::create([
                'user_id'         => $userId,
                'check_in'        => $checkIn->toDateString(),
                'check_out'       => $checkOut->toDateString(),
                'total_price'     => $totalPrice,
                'status'          => 'pending',
                'payment_status'   => 'pending',
                'payment_method'  => $data['payment_method'],
                'placed_via'      => Booking::PLACED_VIA_CUSTOMER_WEB,
                'coupon_code'     => $couponCode,
                'discount_amount' => $discountAmount,
            ]);

            // Tạo BookingRoom & Block ngày
            foreach ($roomPriceDetails as $detail) {
                BookingRoom::create([
                    'booking_id'      => $booking->id,
                    'room_type_id'    => $detail['room_type_id'],
                    'room_id'         => null,
                    'adults'          => $detail['adults'],
                    'children_0_5'    => $detail['children_0_5'],
                    'children_6_11'   => $detail['children_6_11'],
                    'price_per_night' => $detail['price_per_night'],
                    'nights'          => $detail['nights'],
                    'subtotal'        => $detail['subtotal'],
                ]);
            }

            // Tạo Payment
            Payment::create([
                'booking_id' => $booking->id,
                'amount'     => $totalPrice,
                'method'     => $data['payment_method'],
                'status'     => 'pending',
            ]);

            // 11/04: Gä bỏ logic lưu BookingGuest cũ tại đây để chuyển sang lưu Guest trong Controller
            // hoặc gộp chung vào một chỗ để tránh trùng lặp.
            // (Đã được thực hiện trong BookingController@store theo yêu cầu người dùng)

            // Trả về booking để controller xử lý tiếp (vnpay)
            return $booking;
        });
    }

    /**
     * Phòng vật lý đang available và không có RoomBookedDate trong các đêm đó.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $dates
     */
    private function countPhysicalFreeRoomsOfType(int $roomTypeId, $dates): int
    {
        $busyRoomIds = RoomBookedDate::query()
            ->whereIn('booked_date', $dates->all())
            ->distinct()
            ->pluck('room_id')
            ->filter()
            ->values()
            ->all();

        $q = Room::query()
            ->where('room_type_id', $roomTypeId)
            ->where('status', 'available')
            ->excludeMaintenance();
        if ($busyRoomIds !== []) {
            $q->whereNotIn('id', $busyRoomIds);
        }

        return (int) $q->count();
    }

    /**
     * Calc room price based on guests and nights.
     */
    private function calculateRoomPrice(Room $room, int $nights, int $adults, int $children_0_5, int $children_6_11): array
    {
        $basePrice = (float) $room->catalogueBasePrice();
        $roomType = $room->roomType;

        // Không giới hạn số khách - chỉ tính phụ thu khi vượt tiêu chuẩn
        RoomOccupancyPricing::validate($adults, $children_6_11, $children_0_5, $roomType);

        $t = RoomOccupancyPricing::total($basePrice, $nights, $adults, $children_6_11, $children_0_5, $roomType);

        return [
            'base_price'      => $basePrice,
            'extra_adult_fee' => $t['adult_surcharge_per_night'] * $nights,
            'child_fee'       => $t['child_surcharge_per_night'] * $nights,
            'price_per_night' => $t['price_per_night'],
            'subtotal'        => $t['grand_total'],
            'nights'          => $nights,
        ];
    }
}
