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
            ->with(['room', 'rooms.roomType', 'bookingRooms.roomType', 'bookingRooms.room.roomType', 'payment'])
            ->withCount('bookingServices')
            ->latest('id')
            ->paginate(10);

        return view('account.bookings', compact('bookings'));
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

    public function refundForm(Booking $booking, \App\Services\RefundService $refundService)
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        $eligibility = $refundService->canCustomerRequestRefund($booking, (int) Auth::id());
        if (! ($eligibility['allowed'] ?? false)) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', $eligibility['message'] ?? 'Đơn chưa đủ điều kiện yêu cầu hoàn tiền.');
        }

        $calc = $eligibility['calc'];
        $latestRefundRequest = $eligibility['existing'] ?? null;

        return view('account.refund', compact('booking', 'calc', 'latestRefundRequest'));
    }

    public function submitRefund(Request $request, Booking $booking, \App\Services\RefundService $refundService)
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        $eligibility = $refundService->canCustomerRequestRefund($booking, (int) Auth::id());
        if (! ($eligibility['allowed'] ?? false)) {
            return back()->with('error', $eligibility['message'] ?? 'Không đủ điều kiện hoàn tiền.');
        }

        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'bank_name' => 'required|string|max:255',
            'qr_image' => 'nullable|image|max:2048',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('qr_image')) {
            $validated['qr_image'] = $request->file('qr_image')->store('refunds', 'public');
        }

        $result = $refundService->submitCustomerRefundRequest($booking, (int) Auth::id(), $validated);
        if (! $result['success']) {
            return back()->with('error', $result['message'] ?? 'Không thể gửi yêu cầu.');
        }

        return redirect()->route('bookings.show', $booking)->with('success', 'Yêu cầu hoàn tiền của bạn đã được gửi và đang chờ xử lý.');
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
