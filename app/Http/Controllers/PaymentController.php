<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function success(Request $request, Booking $booking)
    {
        $isOwner = Auth::check() && (int) Auth::id() === (int) $booking->user_id;
        abort_unless(
            $isOwner || $request->hasValidSignature(),
            403,
            'Bạn không có quyền xem trang này.'
        );

        $payment = $booking->payment;
        if (! $payment || $payment->status !== 'paid') {
            return redirect()->route('home')->withErrors('Đơn hàng chưa được thanh toán.');
        }

        $booking->load('rooms.roomType');
        $signedBookingViewUrl = $booking->signedPublicShowUrl();

        return view('payment.success', compact('booking', 'signedBookingViewUrl'));
    }

    public function failed()
    {
        return view('payment.failed');
    }
}
