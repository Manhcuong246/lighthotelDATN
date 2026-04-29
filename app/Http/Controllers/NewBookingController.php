<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Guest;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NewBookingController extends Controller
{
    /**
     * Hiển thị form tìm phòng và đặt phòng
     */
    public function index()
    {
        return view('bookings.new-index');
    }

    /**
     * Tìm phòng trống theo ngày
     */
    public function search(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn = $request->check_in;
        $checkOut = $request->check_out;

        // Tìm phòng trống
        $availableRooms = Room::whereDoesntHave('bookedDates', function ($query) use ($checkIn, $checkOut) {
            $query->whereBetween('booked_date', [
                $checkIn,
                Carbon::parse($checkOut)->subDay()->toDateString()
            ]);
        })->with('roomType')->get();

        return view('bookings.search-results', [
            'availableRooms' => $availableRooms,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]);
    }

    /**
     * Hiển thị form đặt phòng động
     */
    public function bookingForm(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn = $request->check_in;
        $checkOut = $request->check_out;
        $roomCountSelection = count($request->room_ids ?? []);

        return view('bookings.booking-form-working', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'roomCountSelection' => $roomCountSelection,
        ]);
    }

    /**
     * Xử lý đặt phòng
     */
    public function store(Request $request)
    {
        $request->validate([
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'full_name' => 'required|string|max:150',
            'email'     => 'required|email|max:150',
            'phone'     => 'required|string|max:20',
            'rooms'     => 'required|integer|min:1|max:10',
            'name'      => 'required|string|max:150|min:2',
            'cccd'      => 'required|string|regex:/^[0-9]{12}$/',
            'payment_method' => 'required|in:cash,vnpay',
        ], [
            'cccd.regex' => 'CCCD phải gồm 12 số',
        ]);

        try {
            DB::beginTransaction();

            // Tính tổng giá dựa trên số phòng
            $roomCount = (int) $request->rooms;
            $totalPrice = $this->calculateTotalPrice($roomCount);

            // Tạo booking
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'room_id' => 1, // Tạm thời, sẽ cập nhật sau
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
            ]);

            // Lưu thông tin 1 người đại diện duy nhất
            Guest::create([
                'booking_id'       => $booking->id,
                'room_index'       => 0,
                'name'             => $request->name,
                'cccd'             => $request->cccd,
                'type'             => 'adult',
                'is_representative'=> 1,
                'checkin_status'   => 'pending',
            ]);

            DB::commit();

            return redirect()->route('bookings.confirmation', $booking->id)
                ->with('success', 'Đặt phòng thành công! Vui lòng đến đúng giờ để check-in.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Hiển thị trang xác nhận đặt phòng
     */
    public function confirmation(Booking $booking)
    {
        $booking->load(['guests', 'room.roomType', 'user']);

        return view('bookings.confirmation', compact('booking'));
    }

    /**
     * Admin check-in khách
     */
    public function checkIn(Request $request, Booking $booking)
    {
        $oldStatus = $booking->status;
        $staffName = auth()->user()?->full_name ?? 'Lễ tân';
        $rooms = $booking->bookingRooms()->with('room')->get()->map(fn($br) => $br->room?->name)->filter()->implode(', ');
        $roomText = $rooms ? " phòng {$rooms}" : '';
        $logNotes = "{$staffName} check-in{$roomText}.";

        // Nếu có guest_id và cccd_input (từ form đơn lẻ)
        if ($request->has('guest_id')) {
            $request->validate([
                'guest_id' => 'required|exists:guests,id',
                'cccd_input' => 'required|string|regex:/^[0-9]{12}$/',
            ], [
                'cccd_input.regex' => 'CCCD không hợp lệ',
            ]);

            $guest = Guest::findOrFail($request->guest_id);

            // Kiểm tra CCCD
            if ($guest->cccd !== $request->cccd_input) {
                return back()->withErrors('CCCD không khớp với dữ liệu trong hệ thống.');
            }

            // Cập nhật trạng thái booking và khách đơn lẻ
            $booking->update([
                'status' => 'checked_in',
                'actual_check_in' => Carbon::now(),
            ]);

            $guest->update([
                'checkin_status' => 'checked_in',
            ]);

            if ($oldStatus !== 'checked_in') {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => auth()->id(),
                    'old_status' => $oldStatus,
                    'new_status' => 'checked_in',
                    'notes' => $logNotes,
                    'changed_at' => now(),
                ]);
            }

            return back()->with('success', "Check-in thành công cho khách {$guest->name}");
        }

        // Nếu check-in hàng loạt từ Modal (không truyền guest_id)
        DB::beginTransaction();
        try {
            $booking->update([
                'status' => 'checked_in',
                'actual_check_in' => Carbon::now(),
            ]);

            // Cập nhật tất cả khách của đơn này
            $booking->guests()->update([
                'checkin_status' => 'checked_in',
            ]);

            if ($oldStatus !== 'checked_in') {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => auth()->id(),
                    'old_status' => $oldStatus,
                    'new_status' => 'checked_in',
                    'notes' => $logNotes,
                    'changed_at' => now(),
                ]);
            }

            DB::commit();
            return back()->with('success', "Đã check-in thành công cho toàn bộ khách trong đơn #{$booking->id}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra khi check-in: ' . $e->getMessage());
        }
    }

    /**
     * Admin check-out khách
     */
    public function checkOut(Booking $booking)
    {
        if (is_null($booking->actual_check_in)) {
            return back()->withErrors('Đơn hàng này chưa được check-in.');
        }

        $oldStatus = $booking->status;
        $staffName = auth()->user()?->full_name ?? 'Lễ tân';
        $rooms = $booking->bookingRooms()->with('room')->get()->map(fn($br) => $br->room?->name)->filter()->implode(', ');
        $roomText = $rooms ? " phòng {$rooms}" : '';

        $booking->update([
            'status' => 'completed',
            'actual_check_out' => now(),
        ]);

        if ($oldStatus !== 'completed') {
            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'completed',
                'notes' => "{$staffName} check-out{$roomText}.",
                'changed_at' => now(),
            ]);
        }

        return back()->with('success', 'Check-out thành công.');
    }

    /**
     * Admin hiển thị chi tiết booking
     */
    public function show(Booking $booking)
    {
        $booking->load(['guests', 'room.roomType', 'user', 'bookingServices.service', 'logs.user']);
        $services = \App\Models\Service::query()->orderBy('name')->get();

        return view('bookings.admin-show', compact('booking', 'services'));
    }

    /**
     * Tính tổng giá (đơn giản hóa)
     */
    private function calculateTotalPrice($roomCount): float
    {
        $pricePerRoomPerNight = 1000000; // 1.000.000đ/đêm/phòng
        $nights = 1; // Đơn giản hóa, có thể tính sau

        return $pricePerRoomPerNight * $roomCount * $nights;
    }
}
