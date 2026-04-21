<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Support\BookingInvoiceViewData;
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
            ->with(['room', 'rooms.roomType', 'bookingRooms', 'payment'])
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
        $booking->load([
            'room.roomType',
            'rooms.roomType',
            'bookingRooms',
            'payment',
            'bookingServices.service',
            'surcharges.service',
        ]);
        return view('account.booking-show', compact('booking'));
    }

    /**
     * Hóa đơn / biên lai chuẩn sau khi checkout (đủ phòng, dịch vụ, phụ phí, thanh toán).
     */
    public function bookingInvoice(Booking $booking)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user?->canAccessAdmin()) {
            abort(403);
        }

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        if (! BookingInvoiceViewData::customerCanView($booking)) {
            return redirect()
                ->route('account.bookings.show', $booking)
                ->with('error', 'Hóa đơn chỉ xem được khi đơn đã checkout và đã thanh toán.');
        }

        return view('account.booking-invoice', BookingInvoiceViewData::make($booking));
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

        if ($booking->status !== 'confirmed') {
            return redirect()->route('account.bookings.show', $booking)->with('error', 'Chỉ có thể yêu cầu hoàn tiền cho đơn đã xác nhận.');
        }

        $calc = $refundService->calculateRefund($booking);

        // Cho phép xem form ngay cả khi 0% (calc['eligible'] = false) 
        // để người dùng vẫn có thể gửi thông tin tài khoản cho admin xem xét.

        return view('account.refund', compact('booking', 'calc'));
    }

    public function submitRefund(Request $request, Booking $booking, \App\Services\RefundService $refundService)
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Trạng thái đơn không hợp lệ.');
        }

        $calc = $refundService->calculateRefund($booking);
        if (!$calc['eligible']) {
            return back()->with('error', 'Không đủ điều kiện hoàn tiền.');
        }

        if (\App\Models\RefundRequest::where('booking_id', $booking->id)->where('status', 'pending_refund')->exists()) {
            return back()->with('error', 'Đơn này đã có yêu cầu hoàn tiền đang chờ xử lý.');
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

        $refundRequest = \App\Models\RefundRequest::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'bank_name' => $validated['bank_name'],
            'qr_image' => $validated['qr_image'] ?? null,
            'refund_percentage' => $calc['percentage'],
            'refund_amount' => $calc['amount'],
            'note' => $validated['note'],
            'status' => 'pending_refund',
        ]);

        $booking->update(['status' => 'cancel_requested']);

        // Log
        \App\Models\BookingLog::create([
            'booking_id' => $booking->id,
            'old_status' => 'confirmed',
            'new_status' => 'cancel_requested',
            'changed_at' => now(),
        ]);

        return redirect()->route('account.bookings.show', $booking)->with('success', 'Yêu cầu hoàn tiền của bạn đã được gửi và đang chờ xử lý.');
    }
}
