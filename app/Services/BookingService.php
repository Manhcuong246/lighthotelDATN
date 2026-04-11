<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\User;
use App\Models\Coupon;
use App\Models\BookingRoom;
use App\Models\BookingGuest;
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

        if (count($data['room_ids']) !== count(array_unique($data['room_ids']))) {
            throw new BookingException('Trong một đơn không được chọn trùng cùng một phòng.');
        }

        // 1. Lấy danh sách phòng và kiểm tra tính hợp lệ
        $rooms = [];
        foreach ($data['room_ids'] as $id) {
            $room = Room::with('roomType')->find($id);
            if (!$room) {
                throw new BookingException("Phòng ID {$id} không tồn tại.");
            }
            $rooms[] = $room;
        }

        // 2. Tạo danh sách ngày cần block
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = collect($period)->map(fn($date) => $date->toDateString());

        // 3. Kiểm tra conflict (phòng đã có người đặt)
        $uniqueRoomIds = array_unique($data['room_ids']);
        foreach ($uniqueRoomIds as $rid) {
            $conflict = RoomBookedDate::query()
                ->where('room_id', $rid)
                ->whereIn('booked_date', $dates)
                ->exists();
            if ($conflict) {
                $roomName = Room::find($rid)->name ?? 'Phòng';
                throw new BookingException("Phòng \"{$roomName}\" đã có người đặt trong khoảng thời gian này.");
            }
        }

        // 4. Tính toán tổng tiền
        $totalPrice = 0;
        $roomPriceDetails = [];

        foreach ($rooms as $index => $room) {
            $guestAdults = (int) ($data['adults'][$index] ?? 1);
            $guest05     = (int) ($data['children_0_5'][$index] ?? 0);
            $guest611    = (int) ($data['children_6_11'][$index] ?? 0);

            try {
                $priceData = $this->calculateRoomPrice($room, $nights, $guestAdults, $guest05, $guest611);

                $totalPrice += $priceData['subtotal'];
                $roomPriceDetails[] = array_merge($priceData, [
                    'room_id'       => $room->id,
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

        // 6. Thực hiện giao dịch DB
        return DB::transaction(function () use ($data, $checkIn, $checkOut, $totalPrice, $discountAmount, $couponCode, $roomPriceDetails, $dates) {
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
                    'room_id'         => $detail['room_id'],
                    'adults'          => $detail['adults'],
                    'children_0_5'    => $detail['children_0_5'],
                    'children_6_11'   => $detail['children_6_11'],
                    'price_per_night' => $detail['price_per_night'],
                    'nights'          => $detail['nights'],
                    'subtotal'        => $detail['subtotal'],
                ]);

                foreach ($dates as $d) {
                    RoomBookedDate::create([
                        'room_id'    => $detail['room_id'],
                        'booked_date'=> $d,
                        'booking_id' => $booking->id,
                    ]);
                }
            }

            // Tạo Payment
            Payment::create([
                'booking_id' => $booking->id,
                'amount'     => $totalPrice,
                'method'     => $data['payment_method'],
                'status'     => 'pending',
            ]);

            // Tạo BookingGuests
             if (isset($data['guests']) && is_array($data['guests'])) {
                foreach ($data['guests'] as $guestData) {
                                        BookingGuest::create([
                        'booking_id' => $booking->id,
                        'name'       => $guestData['name'],
                         'cccd'       => $guestData['cccd'] ?? null,
                        'type'       => $guestData['type'],
                        'status'     => 'pending',
                    ]);
                }
            }

            // Trả về booking để controller xử lý tiếp (vnpay)
            return $booking;
        });
    }

    /**
     * Calc room price based on guests and nights.
     */
    private function calculateRoomPrice(Room $room, int $nights, int $adults, int $children_0_5, int $children_6_11): array
    {
        $basePrice = (float) $room->catalogueBasePrice();
        $roomType = $room->roomType;

        try {
            RoomOccupancyPricing::validate($adults, $children_6_11, $children_0_5, $roomType);
        } catch (\InvalidArgumentException $e) {
            throw new \Exception("Phòng \"{$room->name}\": " . $e->getMessage());
        }

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
