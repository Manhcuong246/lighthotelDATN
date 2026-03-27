<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function bookings()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user?->canAccessAdmin()) {
            abort(403);
        }

        $bookings = $user
            ->bookings()
            ->with('room')
            ->withCount('bookingServices')
            ->latest('id')
            ->paginate(10);

        return view('account.bookings', compact('bookings'));
    }

    public function showBooking(Booking $booking)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user?->canAccessAdmin()) {
            abort(403);
        }

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }
        $booking->load(['room.roomType', 'payment', 'bookingServices.service']);
        return view('account.booking-show', compact('booking'));
    }

    public function cancelBooking(Booking $booking)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user?->canAccessAdmin() || $booking->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Không thể hủy đơn đặt phòng này.');
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        \App\Models\RoomBookedDate::where('booking_id', $booking->id)->delete();

        return back()->with('success', 'Hủy đơn đặt phòng thành công.');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('account.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_url && ! str_starts_with($user->avatar_url, 'http')) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            $validated['avatar_url'] = $request->file('avatar')->store('avatars', 'public');
        }

        unset($validated['avatar']);
        $user->update($validated);
        return redirect()->route('account.profile')->with('success', 'Cập nhật hồ sơ thành công.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);
        /** @var User $user */
        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }
        $user->update(['password' => Hash::make($request->password)]);
        return redirect()->route('account.profile')->with('success', 'Đổi mật khẩu thành công.');
    }
}
