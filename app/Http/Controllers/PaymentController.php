<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\VnPaySuccessSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    private const SIGNATURE_IGNORE_QUERY_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'mc_cid',
        'mc_eid',
        'igshid',
        'si',
        'source',
    ];

    public function success(Request $request, Booking $booking)
    {
        $isOwner = Auth::check() && (int) Auth::id() === (int) $booking->user_id;
        $signedOk = $this->hasValidSignedAccess($request);

        if ($isOwner || $signedOk) {
            VnPaySuccessSession::forget();
        }

        $fromVnpayReturn = false;
        if (! $isOwner && ! $signedOk) {
            $fromVnpayReturn = VnPaySuccessSession::consume($request, $booking);
        }

        abort_unless(
            $isOwner || $signedOk || $fromVnpayReturn,
            403,
            'Bạn không có quyền xem trang này.'
        );

        $payment = $booking->payment;
        if (! $payment || $payment->status !== 'paid') {
            return redirect()->route('home')->withErrors('Đơn hàng chưa được thanh toán.');
        }

        $booking->load([
            'rooms.roomType',
            'bookingRooms.roomType',
            'bookingRooms.room.roomType',
            'room.roomType',
        ]);
        $signedBookingViewUrl = $booking->signedPublicShowUrl();

        return view('payment.success', compact('booking', 'signedBookingViewUrl'));
    }

    public function failed()
    {
        return view('payment.failed');
    }

    private function hasValidSignedAccess(Request $request): bool
    {
        return $request->hasValidSignatureWhileIgnoring(self::SIGNATURE_IGNORE_QUERY_PARAMS, true)
            || $request->hasValidSignatureWhileIgnoring(self::SIGNATURE_IGNORE_QUERY_PARAMS, false);
    }
}
