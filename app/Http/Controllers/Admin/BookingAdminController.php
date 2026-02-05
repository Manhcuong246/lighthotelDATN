<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $bookings = Booking::with(['user', 'room'])->latest()->paginate(15);
        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        return view('admin.bookings.show', compact('booking'));
    }

    public function edit(Booking $booking)
    {
        return view('admin.bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'guests' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $old_status = $booking->status;
        $booking->update($validated);

        // Log status change if status was updated
        if ($old_status !== $booking->status) {
            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => $old_status,
                'new_status' => $booking->status,
                'changed_at' => now(),
            ]);
        }

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Cập nhật đơn đặt phòng thành công.');
    }

    public function destroy(Booking $booking)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Chỉ quản trị viên mới được xóa đơn đặt phòng.');
        }
        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Xóa đơn đặt phòng thành công.');
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $old = $booking->status;
        $booking->status = $request->status;
        $booking->save();

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => $booking->status,
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Cập nhật trạng thái thành công.');
    }

    public function checkIn(Booking $booking)
    {
        if ($booking->status !== 'confirmed' || $booking->actual_check_in) {
            return back()->with('error', 'Không thể thực hiện check-in cho đơn này.');
        }

        $old = $booking->status;
        $booking->actual_check_in = now();
        $booking->save();

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => 'checked_in',
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Khách đã được check-in.');
    }

    public function checkOut(Booking $booking)
    {
        if (!$booking->actual_check_in || $booking->actual_check_out) {
            return back()->with('error', 'Không thể thực hiện check-out cho đơn này.');
        }

        $old = $booking->status;
        $booking->actual_check_out = now();
        // mark completed on checkout
        $booking->status = 'completed';
        $booking->save();

        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => 'completed',
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Khách đã check-out.');
    }
}
