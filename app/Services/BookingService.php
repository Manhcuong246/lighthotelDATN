<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundLog;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\User;
use App\Models\Coupon;
use App\Models\BookingRoom;
use App\Exceptions\BookingException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            $conflict = RoomBookedDate::where('room_id', $rid)
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
            // Một đơn luôn gắn user_id (tài khoản đăng nhập hoặc tài khoản shadow theo email).
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

            // Trả về booking để controller xử lý tiếp (vnpay)
            return $booking;
        });
    }

    /**
     * Calc room price based on guests and nights.
     */
    private function calculateRoomPrice(Room $room, int $nights, int $adults, int $children_0_5, int $children_6_11): array
    {
        $basePrice = (float) $room->base_price;
        $roomType = $room->roomType;

        $maxAdults = $roomType->adult_capacity ?? $room->max_guests ?? 2;
        $maxChildren = $roomType->child_capacity ?? 0;

        $extraAdults = max(0, $adults - $maxAdults);
        $totalChildren = $children_0_5 + $children_6_11;
        $extraChildrenLimit = max(0, $totalChildren - $maxChildren);
        $chargeableChildren = max(0, $children_6_11 - $maxChildren);

        // Giới hạn +2
        if ($extraAdults > 2 || $extraChildrenLimit > 2) {
            throw new \Exception("Số lượng người vượt quá giới hạn của phòng \"{$room->name}\", vui lòng đặt thêm phòng.");
        }

        $extraAdultFeePerNight = $extraAdults * (0.4 * $basePrice);
        $childFeePerNight = $chargeableChildren * (0.3 * $basePrice); // Giảm từ 50% xuống 30%

        $pricePerNight = $basePrice + $extraAdultFeePerNight + $childFeePerNight;
        $subtotal = $pricePerNight * $nights;

        return [
            'base_price'      => $basePrice,
            'extra_adult_fee' => $extraAdultFeePerNight * $nights,
            'child_fee'       => $childFeePerNight * $nights,
            'price_per_night' => $pricePerNight,
            'subtotal'        => $subtotal,
            'nights'          => $nights
        ];
    }

    /**
     * Refund calculation constants
     */
    const REFUND_FULL = 'full';
    const REFUND_PARTIAL = 'partial';
    const REFUND_NONE = 'none';

    /**
     * Cancel a booking and calculate refund based on cancellation timing.
     *
     * @param int $bookingId
     * @param string|null $reason
     * @return array
     * @throws \Exception
     */
    public function cancelBooking(int $bookingId, ?string $reason = null): array
    {
        try {
            DB::beginTransaction();

            $booking = Booking::find($bookingId);

            if (!$booking) {
                throw new \Exception('Không tìm thấy đơn đặt phòng.', 404);
            }

            if ($booking->status === 'cancelled') {
                throw new \Exception('Đơn đặt phòng này đã được hủy trước đó.', 400);
            }

            if ($booking->status === 'completed') {
                throw new \Exception('Không thể hủy đơn đặt phòng đã hoàn thành.', 400);
            }

            $refundResult = $this->calculateRefund($booking);

            $booking->status = 'cancelled';
            $booking->payment_status = $this->getPaymentStatusFromRefund($refundResult['type']);
            $booking->cancelled_at = now();
            $booking->cancellation_reason = $reason;
            $booking->refund_amount = $refundResult['amount'];
            $booking->save();

            // Create refund log
            RefundLog::create([
                'booking_id' => $booking->id,
                'refund_amount' => $refundResult['amount'],
                'refund_type' => $refundResult['type'],
                'reason' => $reason ?? $refundResult['message'],
                'processed_by' => Auth::id(),
                'refunded_at' => now(),
            ]);

            DB::commit();

            Log::info('Booking cancelled successfully', [
                'booking_id' => $booking->id,
                'refund_amount' => $refundResult['amount'],
                'refund_type' => $refundResult['type'],
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => true,
                'booking_id' => $booking->id,
                'refund_amount' => $refundResult['amount'],
                'refund_type' => $refundResult['type'],
                'message' => $refundResult['message'],
                'details' => [
                    'total_price' => $booking->total_price,
                    'check_in_date' => Carbon::parse($booking->check_in_date ?? $booking->check_in)->format('d/m/Y H:i'),
                    'cancellation_time' => now()->format('d/m/Y H:i'),
                    'hours_before_checkin' => $this->getHoursBeforeCheckIn($booking),
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Booking cancellation failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate refund amount based on cancellation timing.
     *
     * Business Logic:
     * - > 24h before check-in: 100% refund
     * - 0-24h before check-in: 50% refund
     * - After check-in: 0% refund
     *
     * @param Booking $booking
     * @return array
     */
    protected function calculateRefund(Booking $booking): array
    {
        $now = Carbon::now();
        $checkInDate = Carbon::parse($booking->check_in_date ?? $booking->check_in);
        $totalPrice = $booking->total_price;

        $hoursBeforeCheckIn = $now->diffInHours($checkInDate, false);

        // Case 1: More than 24 hours before check-in → Full refund (100%)
        if ($hoursBeforeCheckIn > 24) {
            return [
                'amount' => $totalPrice,
                'type' => self::REFUND_FULL,
                'message' => sprintf(
                    'Hoàn tiền 100%% (%,.0f VNĐ) vì hủy trước %d giờ.',
                    $totalPrice,
                    ceil($hoursBeforeCheckIn)
                ),
            ];
        }

        // Case 2: Between 0 and 24 hours before check-in → Partial refund (50%)
        if ($hoursBeforeCheckIn > 0 && $hoursBeforeCheckIn <= 24) {
            $refundAmount = $totalPrice * 0.5;
            return [
                'amount' => $refundAmount,
                'type' => self::REFUND_PARTIAL,
                'message' => sprintf(
                    'Hoàn tiền 50%% (%,.0f VNĐ) vì hủy trong vòng 24 giờ trước nhận phòng.',
                    $refundAmount
                ),
            ];
        }

        // Case 3: After check-in time → No refund (0%)
        return [
            'amount' => 0,
            'type' => self::REFUND_NONE,
            'message' => 'Không hoàn tiền vì đã quá thời hạn nhận phòng.',
        ];
    }

    /**
     * Get hours before check-in for display purposes.
     *
     * @param Booking $booking
     * @return float
     */
    protected function getHoursBeforeCheckIn(Booking $booking): float
    {
        $now = Carbon::now();
        $checkInDate = Carbon::parse($booking->check_in_date ?? $booking->check_in);

        return $now->diffInHours($checkInDate, false);
    }

    /**
     * Get payment status based on refund type.
     *
     * @param string $refundType
     * @return string
     */
    protected function getPaymentStatusFromRefund(string $refundType): string
    {
        return match ($refundType) {
            self::REFUND_FULL => 'refunded',
            self::REFUND_PARTIAL => 'partial_refunded',
            self::REFUND_NONE => 'paid',
            default => 'paid',
        };
    }

    /**
     * Check if a booking can be cancelled (preview without actual cancellation).
     *
     * @param int $bookingId
     * @return array
     * @throws \Exception
     */
    public function previewCancellation(int $bookingId): array
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            throw new \Exception('Không tìm thấy đơn đặt phòng.', 404);
        }

        if ($booking->status === 'cancelled') {
            throw new \Exception('Đơn đặt phòng này đã được hủy trước đó.', 400);
        }

        if ($booking->status === 'completed') {
            throw new \Exception('Không thể hủy đơn đặt phòng đã hoàn thành.', 400);
        }

        $refundResult = $this->calculateRefund($booking);
        $hoursBefore = $this->getHoursBeforeCheckIn($booking);

        return [
            'can_cancel' => true,
            'booking_id' => $booking->id,
            'refund_preview' => $refundResult,
            'hours_before_checkin' => $hoursBefore,
            'check_in_date' => Carbon::parse($booking->check_in_date ?? $booking->check_in)->format('d/m/Y H:i'),
            'total_price' => $booking->total_price,
        ];
    }
}
