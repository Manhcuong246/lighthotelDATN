<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingCancellationService;
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
            ->with(['room', 'rooms', 'bookingRooms'])
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
        $booking->load(['room.roomType', 'rooms.roomType', 'payment', 'bookingServices.service']);
        $cancelSvc = app(BookingCancellationService::class);
        $policy = $cancelSvc->evaluatePolicy($booking);
        $nonRefundable = $cancelSvc->isNonRefundableBooking($booking);

        return view('account.booking-show', compact('booking', 'policy', 'nonRefundable'));
    }

    public function cancelBooking(Request $request, Booking $booking)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user?->canAccessAdmin() || $booking->user_id !== Auth::id()) {
            abort(403);
        }

        if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
            return back()->with('error', 'Không thể hủy đơn đặt phòng này.');
        }

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:2000',
        ]);

        /** @var BookingCancellationService $svc */
        $svc = app(BookingCancellationService::class);
        $result = $svc->cancelByCustomer($booking, (int) Auth::id(), $request->input('cancellation_reason'));

        if (! $result['ok']) {
            return back()->with('error', $result['error'] ?? 'Không thể hủy đơn.');
        }

        $booking = $result['booking'];
        $booking->load('payment');

        $mode = $result['mode'] ?? 'cancelled';
        $message = match ($mode) {
            'cancelled_unpaid' => 'Đơn đặt phòng đã được hủy thành công.',
            'pending_admin' => 'Yêu cầu hủy đã gửi. Khách sạn sẽ xác nhận; phòng vẫn được giữ cho đến khi có quyết định.',
            default => 'Đơn đặt phòng đã được hủy thành công.',
        };

        if ($mode === 'cancelled' && $booking->payment?->needsRefundBankDetails()) {
            $message .= ' Vui lòng điền thông tin tài khoản nhận hoàn tiền (mục bên dưới).';
        }

        return back()->with('success', $message);
    }

    public function submitRefund(Request $request, Booking $booking)
    {
        if (Auth::user()?->canAccessAdmin() || $booking->user_id !== Auth::id()) {
            abort(403);
        }

        if ($booking->status !== 'cancelled') {
            return back()->withErrors('Chỉ đơn đã hủy mới có thể gửi thông tin hoàn tiền.');
        }

        $payment = $booking->payment;
        if (!$payment || !$payment->needsRefundBankDetails()) {
            return back()->withErrors('Không thể gửi thông tin hoàn tiền cho đơn này (chưa thanh toán hoặc đã gửi trước đó).');
        }

        $validated = $request->validate([
            'refund_account_name' => 'required|string|max:150',
            'refund_account_number' => 'required|string|max:64',
            'refund_qr' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'refund_user_note' => 'nullable|string|max:1000',
        ]);

        $qrPath = $payment->refund_qr_path;
        if ($request->hasFile('refund_qr')) {
            if ($payment->refund_qr_path) {
                Storage::disk('public')->delete($payment->refund_qr_path);
            }
            $qrPath = $request->file('refund_qr')->store('refunds/qr', 'public');
        }

        $payment->update([
            'refund_account_name' => $validated['refund_account_name'],
            'refund_account_number' => $validated['refund_account_number'],
            'refund_qr_path' => $qrPath,
            'refund_user_note' => $validated['refund_user_note'] ?? null,
            'refund_status' => Payment::REFUND_PENDING_ADMIN,
        ]);

        return back()->with('success', 'Đã gửi thông tin nhận hoàn tiền. Khách sạn sẽ xử lý và đăng chứng từ chuyển khoản lên đây để bạn đối chiếu.');
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
