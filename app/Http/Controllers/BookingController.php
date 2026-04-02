<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\RoomPrice;
use App\Models\User;
use App\Models\Service;
use App\Models\BookingService;
use App\Services\VnPayService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::check() && Auth::user()?->canAccessAdmin()) {
            return back()->withErrors('Tài khoản nhân viên/quản trị không thể đặt phòng trên giao diện khách.')->withInput();
        }

        // 1. Validate dữ liệu đầu vào
        $data = $request->validate([
            'room_ids'       => 'required|array|min:1',
            'room_ids.*'     => 'required|integer|exists:rooms,id',
            'full_name'      => 'required|string|max:150|min:2',
            'email'          => 'required|email|max:150',
            'phone'          => 'required|string|min:10|max:20|regex:/^[0-9\+\-\s]+$/',
            'check_in'       => 'required|date|after_or_equal:today',
            'check_out'      => 'required|date|after:check_in',
            'payment_method' => 'required|in:vnpay',
            'bank_code'      => 'nullable|string|max:50',
            'coupon_code'    => 'nullable|string|max:50',
            'adults'         => 'required|array',
            'children_0_5'   => 'required|array',
            'children_6_11'  => 'required|array',
        ], [
            'room_ids.required'      => 'Vui lòng chọn ít nhất 1 phòng.',
            'room_ids.*.exists'      => 'Phòng không tồn tại.',
            'full_name.required'     => 'Vui lòng nhập họ tên.',
            'full_name.min'          => 'Họ tên phải có ít nhất 2 ký tự.',
            'email.required'         => 'Vui lòng nhập email.',
            'email.email'            => 'Email không hợp lệ.',
            'phone.required'         => 'Vui lòng nhập số điện thoại.',
            'phone.min'              => 'Số điện thoại phải có ít nhất 10 số.',
            'phone.regex'            => 'Số điện thoại chỉ được chứa số, dấu +, dấu - và khoảng trắng.',
            'check_in.after_or_equal'=> 'Ngày nhận phòng phải từ hôm nay trở đi.',
            'check_out.after'        => 'Ngày trả phòng phải sau ngày nhận phòng.',
        ]);

        $checkIn  = new \Carbon\Carbon($data['check_in']);
        $checkOut = new \Carbon\Carbon($data['check_out']);
        $nights   = $checkIn->diffInDays($checkOut);

        if ($nights <= 0) {
            return back()->withErrors('Ngày trả phòng phải sau ngày nhận phòng.')->withInput();
        }
        if ($nights > 30) {
            return back()->withErrors('Bạn chỉ có thể đặt phòng tối đa 30 đêm.')->withInput();
        }

        // 2. Lấy danh sách phòng (giữ nguyên thứ tự để khớp với mảng guest)
        $rooms = [];
        foreach ($data['room_ids'] as $id) {
            $r = Room::with('roomType')->find($id);
            if (!$r) return back()->withErrors('Phòng không tồn tại.')->withInput();
            $rooms[] = $r;
        }

        // 3. Tạo danh sách ngày cần block
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates  = collect();
        foreach ($period as $date) {
            $dates->push($date->toDateString());
        }

        // 4. Kiểm tra conflict từng phòng (Dùng array_unique để ko check 1 physical room nhiều lần)
        $uniqueRoomIds = array_unique($data['room_ids']);
        foreach ($uniqueRoomIds as $rid) {
            $conflict = RoomBookedDate::where('room_id', $rid)
                ->whereIn('booked_date', $dates)
                ->exists();
            if ($conflict) {
                $roomName = Room::find($rid)->name ?? 'Phòng';
                return back()->withErrors("Phòng \"{$roomName}\" đã có người đặt trong khoảng thời gian này.")->withInput();
            }
        }

        // 5. Tính tổng tiền (Sử dụng logic có phụ thu và giới hạn người)
        $totalPrice = 0;
        $roomPriceDetails = [];
        
        foreach ($rooms as $index => $room) {
            $guestAdults = (int) ($data['adults'][$index] ?? 1);
            $guest05     = (int) ($data['children_0_5'][$index] ?? 0);
            $guest611    = (int) ($data['children_6_11'][$index] ?? 0);

            try {
                // Gọi function calculateTotalPrice() mới 
                // Theo yêu cầu: 
                // - Trẻ < 6 luôn miễn phí.
                // - Trẻ 6-11 tính phí khi vượt giới hạn (guest611)
                $priceData = $this->calculateTotalPrice($room, $nights, $guestAdults, $guest05, $guest611);
                
                $totalPrice += $priceData['subtotal'];
                $roomPriceDetails[] = array_merge($priceData, [
                    'room_id'       => $room->id,
                    'nights'        => $nights,
                    'adults'        => $guestAdults,
                    'children_0_5'  => $guest05,
                    'children_6_11' => $guest611,
                ]);
            } catch (\Exception $e) {
                // Nếu vượt giới hạn (+2) -> Trả về lỗi theo yêu cầu
                return back()->withErrors($e->getMessage())->withInput();
            }
        }

        // 5.1 Xử lý mã giảm giá
        $discountAmount = 0;
        $couponCode = $data['coupon_code'] ?? null;
        if ($couponCode) {
            $coupon = \App\Models\Coupon::where('code', $couponCode)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expired_at')
                          ->orWhere('expired_at', '>=', \Carbon\Carbon::today()->toDateString());
                })
                ->first();
            
            if ($coupon) {
                $discountAmount = ($totalPrice * $coupon->discount_percent) / 100;
                $totalPrice -= $discountAmount;
            } else {
                return back()->withErrors('Mã giảm giá không hợp lệ hoặc đã hết hạn.')->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // 6. Xử lý user
            if (Auth::check()) {
                $userId = Auth::id();
            } else {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'full_name' => $data['full_name'],
                        'phone'     => $data['phone'] ?? null,
                        'password'  => bcrypt(Str::random(12)),
                    ]
                );
                $userId = $user->id;
            }

            // 7. Tạo BOOKING duy nhất
            $booking = Booking::create([
                'user_id'         => $userId ?? null,
                'check_in'        => $checkIn->toDateString(),
                'check_out'       => $checkOut->toDateString(),
                'total_price'     => $totalPrice,
                'status'          => 'pending',
                'coupon_code'     => $couponCode,
                'discount_amount' => $discountAmount,
            ]);

            // 8. Tạo booking_rooms & Block lịch
            foreach ($roomPriceDetails as $detail) {
                \App\Models\BookingRoom::create([
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

            // 10. Tạo payment
            Payment::create([
                'booking_id' => $booking->id,
                'amount'     => $totalPrice,
                'method'     => $data['payment_method'],
                'status'     => 'pending',
            ]);

            DB::commit();

            // 11. Redirect VNPay
            $vnPayService = app(VnPayService::class);
            $returnUrl    = route('payment.vnpay.return');
            $orderInfo    = 'Dat phong Light Hotel #' . $booking->id;
            $txnRef       = 'LIGHT' . $booking->id;
            $amountVND    = (int) round($totalPrice);
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

            if (! Auth::check() && isset($user)) {
                Auth::login($user);
            }

            return redirect()->away($paymentUrl);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
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
        $booking->update(['actual_check_in' => now()]);
        return back()->with('success', 'Check-in thành công');
    }

    public function checkOut(Booking $booking)
    {
        abort_unless($booking->isCheckoutAllowed(), 403);
        $booking->update([
            'actual_check_out' => now(),
            'status' => 'completed',
        ]);
        return back()->with('success', 'Check-out thành công');
    }

    /**
     * Tính tổng giá phòng dựa trên số người và số đêm.
     * 
     * @param Room $room
     * @param int $nights
     * @param int $adults
     * @param int $children_0_5
     * @param int $children_6_11
     * @return array
     * @throws \Exception
     */
    private function calculateTotalPrice(Room $room, int $nights, int $adults, int $children_0_5, int $children_6_11): array
    {
        $basePrice = (float) $room->base_price;
        $roomType = $room->roomType;
        
        // Sử dụng adult_capacity và child_capacity từ RoomType nếu có, 
        // nếu không có thì fallback về max_guests/0
        $maxAdults = $roomType->adult_capacity ?? $room->max_guests ?? 2;
        $maxChildren = $roomType->child_capacity ?? 0;

        // 1. Tính số người vượt giới hạn
        $extraAdults = max(0, $adults - $maxAdults);
        
        // Tổng số trẻ em để kiểm tra giới hạn +2
        $totalChildren = $children_0_5 + $children_6_11;
        $extraChildrenLimit = max(0, $totalChildren - $maxChildren);

        // Trẻ em 6-11 tính phí khi vượt giới hạn
        $chargeableChildren = max(0, $children_6_11 - $maxChildren);

        // 2. Validate giới hạn vượt (+2 người lớn, +2 trẻ em)
        if ($extraAdults > 2 || $extraChildrenLimit > 2) {
            throw new \Exception("Số lượng người vượt quá giới hạn của phòng, vui lòng đặt thêm phòng.");
        }

        // 3. Quy tắc tính giá
        // Phụ thu người lớn: mỗi người vượt x 40% base_price
        $extraAdultFeePerNight = $extraAdults * (0.4 * $basePrice);
        
        // Phụ thu trẻ em: mỗi trẻ em (6-11) vượt hạn x 50% base_price
        $childFeePerNight = $chargeableChildren * (0.5 * $basePrice);

        // 4. Tính toán tổng
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
}


