<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomBookedDate;
use App\Models\RoomPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class BookingController extends Controller
{
    // Xử lý đặt phòng đơn giản cho 1 phòng
    public function store(Request $request, Room $room)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $room->max_guests,
        ]);

        // Tính tổng tiền
        $checkIn = new \Carbon\Carbon($data['check_in']);
        $checkOut = new \Carbon\Carbon($data['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights <= 0) {
            return back()->withErrors('Ngày trả phòng phải sau ngày nhận phòng.')->withInput();
        }

        $totalPrice = $this->calculateTotalPrice($room, $checkIn, $checkOut);

        // Kiểm tra trùng lịch
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = collect();
        foreach ($period as $date) {
            $dates->push($date->toDateString());
        }

        $conflict = RoomBookedDate::where('room_id', $room->id)
            ->whereIn('booked_date', $dates)
            ->exists();

        if ($conflict) {
            return back()->withErrors('Phòng đã được đặt trong khoảng thời gian này. Vui lòng chọn ngày khác.')->withInput();
        }

        DB::beginTransaction();
        try {
            // Nếu chưa có user đăng nhập, bạn có thể gắn tạm user_id = null hoặc 1 user guest.
            $booking = Booking::create([
                'user_id' => null,
                'room_id' => $room->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $data['guests'],
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            // Lưu ngày đã đặt
            foreach ($dates as $d) {
                RoomBookedDate::create([
                    'room_id' => $room->id,
                    'booked_date' => $d,
                    'booking_id' => $booking->id,
                ]);
            }

            DB::commit();

            return redirect()->route('rooms.show', $room)->with('success', 'Đặt phòng thành công! Vui lòng chờ xác nhận.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors('Có lỗi xảy ra, vui lòng thử lại sau.')->withInput();
        }
    }

    // Hàm tính tổng tiền theo base_price và bảng room_prices
    protected function calculateTotalPrice(Room $room, \Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut): float
    {
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $prices = RoomPrice::where('room_id', $room->id)->get();

        $total = 0;
        foreach ($period as $date) {
            $priceForDate = $room->base_price;

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
}


