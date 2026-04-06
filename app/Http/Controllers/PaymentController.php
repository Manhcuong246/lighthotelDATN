<?php

namespace App\Http\Controllers;

use App\Models\Booking;

class PaymentController extends Controller
{
    public function success(Booking $booking)
    {
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
