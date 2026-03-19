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
    public function store(Request $request, Room $room)
    {
        if (Auth::check() && Auth::user()?->canAccessAdmin()) {
            return back()->withErrors('Tài khoản nhân viên/quản trị không thể đặt phòng trên giao diện khách.')->withInput();
        }

        $maxGuests = $room->max_guests ?? 99;
        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $maxGuests,
            'payment_method' => 'required|in:vnpay',
            'bank_code' => 'nullable|string|max:50',
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
            return back()->withErrors('Có lỗi xảy ra, vui lòng thử lại sau.')->withInput();
        }

        DB::beginTransaction();
        try {
            // Attach or create a user for the guest (so admin lists show a name)
            if (Auth::check()) {
                $userId = Auth::id();
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

            // Tính tổng tiền dịch vụ
            $servicesTotal = 0;
            $selectedServices = [];
            if ($request->has('services')) {
                $services = Service::whereIn('id', $request->services)->get();
                foreach ($services as $service) {
                    $price = is_numeric($service->price) ? (float) $service->price : 0;
                    $servicesTotal += $price;
                    $selectedServices[] = [
                        'service_id' => $service->id,
                        'price' => $price,
                        'quantity' => 1,
                    ];
                }
            }

            $finalTotalPrice = $totalPrice + $servicesTotal;

            $booking = Booking::create([
                'user_id' => $userId ?? null,
                'room_id' => $room->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $data['guests'],
                'total_price' => $finalTotalPrice,
                'status' => 'pending',
            ]);

            // Lưu dịch vụ đi kèm
            foreach ($selectedServices as $serviceData) {
                BookingService::create([
                    'booking_id' => $booking->id,
                    'service_id' => $serviceData['service_id'],
                    'quantity' => $serviceData['quantity'],
                    'price' => $serviceData['price'],
                ]);
            }

            foreach ($dates as $d) {
                RoomBookedDate::create([
                    'room_id' => $room->id,
                    'booked_date' => $d,
                    'booking_id' => $booking->id,
                ]);
            }

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $finalTotalPrice,
                'method' => $data['payment_method'],
                'status' => 'pending',
            ]);

            DB::commit();

            if ($data['payment_method'] === 'vnpay') {
                $vnPayService = app(VnPayService::class);
                $returnUrl = route('payment.vnpay.return');
                $orderInfo = 'Dat phong Light Hotel #' . $booking->id;
                $txnRef = 'LIGHT' . $booking->id;
                $amount = (int) round($finalTotalPrice);
                $bankCode = $request->input('bank_code') ?: null;

                $paymentUrl = $vnPayService->createPaymentUrl(
                    $txnRef,
                    $amount,
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
            }

            if (! Auth::check() && isset($user)) {
                Auth::login($user);
            }

            return redirect()->route('account.bookings')->with('success', 'Đặt phòng thành công! Vui lòng kiểm tra lịch đặt phòng để xem thông tin thanh toán.');
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


