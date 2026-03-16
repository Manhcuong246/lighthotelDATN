<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\RoomBookedDate;
use App\Models\RoomPrice;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class BookingController extends Controller
{
    /**
     * Booking theo loại phòng (thay vì phòng cụ thể)
     */
    public function storeByType(Request $request, RoomType $roomType)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $roomType->capacity,
            'preferred_room_number' => 'nullable|string|max:20',
        ]);

        $checkIn = new \Carbon\Carbon($data['check_in']);
        $checkOut = new \Carbon\Carbon($data['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights <= 0) {
            return back()->withErrors('Ngày trả phòng phải sau ngày nhận phòng.')->withInput();
        }

        // Auto-assign room dựa trên room_type_id và dates
        $assignedRoom = Booking::assignAvailableRoom(
            $roomType->id,
            $checkIn->toDateString(),
            $checkOut->toDateString(),
            $data['preferred_room_number'] ?? null
        );

        if (!$assignedRoom) {
            return back()->withErrors('Rất tiếc, loại phòng này đã hết phòng trống trong khoảng thời gian này. Vui lòng chọn ngày khác hoặc loại phòng khác.')->withInput();
        }

        $totalPrice = $this->calculateTotalPriceByType($roomType, $checkIn, $checkOut);

        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $dates = collect();
        foreach ($period as $date) {
            $dates->push($date->toDateString());
        }

        DB::beginTransaction();
        try {
            // Attach or create a user for the guest
            if (auth()->check()) {
                $userId = auth()->id();
            } else {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'full_name' => $data['full_name'],
                        'phone' => $data['phone'] ?? null,
                        'password' => bcrypt(Str::random(12)),
                    ]
                );
                $userId = $user->id;
            }

            $depositAmount = round($totalPrice * 0.3, 2); // 30% deposit
            
            $booking = Booking::create([
                'user_id' => $userId ?? null,
                'room_type_id' => $roomType->id,
                'room_id' => $assignedRoom->id, // Auto-assign room
                'preferred_room_number' => $data['preferred_room_number'] ?? null,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $data['guests'],
                'total_price' => $totalPrice,
                'deposit_amount' => $depositAmount,
                'status' => 'pending',
            ]);

            foreach ($dates as $d) {
                RoomBookedDate::create([
                    'room_id' => $assignedRoom->id,
                    'booked_date' => $d,
                    'booking_id' => $booking->id,
                ]);
            }

            DB::commit();

            return redirect()->route('roomtypes.show', $roomType)->with('success', 'Đặt phòng thành công! Phòng số ' . $assignedRoom->name . ' đã được giữ cho bạn.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra, vui lòng thử lại sau.')->withInput();
        }
    }
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

        $checkIn = new \Carbon\Carbon($data['check_in']);
        $checkOut = new \Carbon\Carbon($data['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights <= 0) {
            return back()->withErrors('Ngày trả phòng phải sau ngày nhận phòng.')->withInput();
        }

        $totalPrice = $this->calculateTotalPrice($room, $checkIn, $checkOut);

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
            // Attach or create a user for the guest (so admin lists show a name)
            if (auth()->check()) {
                $userId = auth()->id();
            } else {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'full_name' => $data['full_name'],
                        'phone' => $data['phone'] ?? null,
                        'password' => bcrypt(Str::random(12)),
                    ]
                );
                $userId = $user->id;
            }

            $booking = Booking::create([
                'user_id' => $userId ?? null,
                'room_id' => $room->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $data['guests'],
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

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

    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $booking->room->max_guests,
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $checkIn = new \Carbon\Carbon($data['check_in']);
        $checkOut = new \Carbon\Carbon($data['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights <= 0) {
            return back()->withErrors('Ngày trả phòng phải sau ngày nhận phòng.')->withInput();
        }

        DB::beginTransaction();
        try {
            // If check-in or check-out dates changed, update the RoomBookedDate records
            if ($booking->check_in != $checkIn->format('Y-m-d') || $booking->check_out != $checkOut->format('Y-m-d')) {
                // Delete old booked dates
                RoomBookedDate::where('booking_id', $booking->id)->delete();

                // Create new booked dates
                $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
                foreach ($period as $date) {
                    RoomBookedDate::create([
                        'room_id' => $booking->room_id,
                        'booked_date' => $date->toDateString(),
                        'booking_id' => $booking->id,
                    ]);
                }
            }

            // Update the booking
            $booking->update([
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $data['guests'],
                'total_price' => $data['total_price'],
                'status' => $data['status'],
            ]);

            DB::commit();

            return redirect()->route('admin.bookings.show', $booking)->with('success', 'Cập nhật đơn thành công');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra, vui lòng thử lại sau.')->withInput();
        }
    }

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

    protected function calculateTotalPriceByType(RoomType $roomType, \Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut): float
    {
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        
        $total = 0;
        foreach ($period as $date) {
            // Sử dụng giá từ room_type
            $total += (float) $roomType->price;
        }

        return $total;
    }
    public function checkIn(Booking $booking)
    {
        abort_unless($booking->isCheckinAllowed(), 403);

        $booking->update([
            'checked_in_at' => now(),
        ]);

        return back()->with('success', 'Check-in thành công');
    }

    public function checkOut(Booking $booking)
    {
        abort_unless($booking->isCheckoutAllowed(), 403);

        $booking->update([
            'checked_out_at' => now(),
            'status' => 'completed',
        ]);

        return back()->with('success', 'Check-out thành công');
    }

}


