<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestCheckInController extends Controller
{
    /**
     * Display the check-in page for a specific booking
     */
    public function index(Booking $booking)
    {
          \Log::info('Check-in page accessed', ['booking_id' => $booking->id]);
        // Check if user has permission to view this booking
        $this->authorizeBookingAccess($booking);

        // Load booking with guests
        $booking->load(['bookingGuests', 'rooms', 'user']);

        return view('checkin.index', compact('booking'));
    }

    /**
     * Update guest check-in status
     */
    public function updateGuestStatus(Request $request, Booking $booking, BookingGuest $guest)
    {
        // Check if guest belongs to this booking
        if ($guest->booking_id !== $booking->id) {
            return response()->json(['error' => 'Guest not found in this booking'], 404);
        }

        // Check permissions
        $this->authorizeBookingAccess($booking);

        $request->validate([
            'status' => 'required|in:pending,checked_in'
        ]);

        $guest->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái khách thành công',
            'guest' => $guest->fresh()
        ]);
    }

    /**
     * Check in all guests for a booking
     */
    public function checkInAll(Request $request, Booking $booking)
    {
        $this->authorizeBookingAccess($booking);

        $booking->bookingGuests()->update(['status' => 'checked_in']);

        return back()->with('success', 'Tất cả khách đã được check-in thành công');
    }

    /**
     * Get guest list for AJAX requests
     */
    public function getGuestList(Booking $booking)
    {
        $this->authorizeBookingAccess($booking);

        $guests = $booking->bookingGuests()->orderBy('type')->orderBy('name')->get();

        return response()->json([
            'guests' => $guests,
            'total_guests' => $guests->count(),
            'checked_in_count' => $guests->where('status', 'checked_in')->count()
        ]);
    }

    /**
     * Check if user has permission to access booking
     */
    private function authorizeBookingAccess(Booking $booking)
    {
        $user = Auth::user();

        // Allow access if user is the booking owner
        if ($user && $booking->user_id === $user->id) {
            return true;
        }

        // Allow access if user is admin/staff
        if ($user && $user->canAccessAdmin()) {
            return true;
        }

        abort(403, 'Bạn không có quyền truy cập thông tin đặt phòng này');
    }
}
