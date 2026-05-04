<?php

namespace App\Http\Controllers;

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
            ->with(['room', 'rooms.roomType', 'bookingRooms.roomType', 'bookingRooms.room.roomType', 'payment'])
            ->withCount('bookingServices')
            ->whereNotIn('status', ['cancelled', 'cancel_requested'])
            ->latest('id')
            ->paginate(10);

        return view('account.bookings', compact('bookings'));
    }

    public function profile()
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->canAccessAdmin()) {
            return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'staff.dashboard');
        }

        return view('account.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->canAccessAdmin()) {
            abort(403);
        }
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
        /** @var User $user */
        $user = Auth::user();
        if ($user->canAccessAdmin()) {
            abort(403);
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }
        $user->update(['password' => Hash::make($request->password)]);
        return redirect()->route('account.profile')->with('success', 'Đổi mật khẩu thành công.');
    }

    public function closeAccount(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->canSelfCloseAccountFromWebsite()) {
            abort(403);
        }

        $request->validate([
            'current_password' => 'required|string',
            'confirm_close' => 'accepted',
        ], [
            'confirm_close.accepted' => 'Vui lòng xác nhận bạn muốn đóng tài khoản.',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Mật khẩu không đúng.',
            ]);
        }

        if ($user->hasBookingsBlockingAccountClosure()) {
            return back()->with(
                'error',
                'Bạn còn đơn đặt phòng chưa hoàn tất (ví dụ: đang chờ thanh toán, đang lưu trú hoặc chờ xử lý hủy). '
                . 'Vui lòng hoàn tất hoặc liên hệ lễ tân trước khi đóng tài khoản.'
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect()
            ->route('home')
            ->with(
                'success',
                'Tài khoản của bạn đã được đóng. Dữ liệu đặt phòng trong hệ thống được giữ theo chính sách lưu trữ. '
                . 'Bạn có thể đăng ký lại với cùng email.'
            );
    }
}
