<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingCancellationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class BookingCancellationController extends Controller
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

    public function __construct(
        private BookingCancellationService $cancellationService
    ) {}

    protected function authorizeBookingAccess(Request $request, Booking $booking): void
    {
        $isOwner = auth()->check() && (int) auth()->id() === (int) $booking->user_id;
        $signedOk = $this->hasValidSignedAccess($request);
        abort_unless(
            $isOwner || $signedOk,
            403,
            'Bạn không có quyền thực hiện thao tác này. Đăng nhập đúng tài khoản hoặc dùng link được gửi qua email.'
        );
    }

    protected function publicBookingShowUrl(Booking $booking): string
    {
        $isOwner = auth()->check() && (int) auth()->id() === (int) $booking->user_id;
        if ($isOwner) {
            return route('bookings.show', $booking);
        }

        return $booking->signedPublicShowUrl();
    }

    /**
     * Show cancellation confirmation page.
     */
    public function show(Request $request, Booking $booking): View|RedirectResponse
    {
        $this->authorizeBookingAccess($request, $booking);

        $booking->load(['user', 'room', 'room.roomType']);

        if ($booking->status === 'cancelled') {
            return redirect()->to($this->publicBookingShowUrl($booking))
                ->with('error', 'Booking này đã bị hủy trước đó.');
        }

        $policy = $this->cancellationService->getCancellationPolicy($booking);

        $isOwner = auth()->check() && (int) auth()->id() === (int) $booking->user_id;
        $cancelPostUrl = $isOwner
            ? route('bookings.cancel.post', $booking)
            : URL::temporarySignedRoute(
                'bookings.cancel.post',
                now()->addHours(24),
                ['booking' => $booking->id],
                false
            );
        $bookingShowUrl = $this->publicBookingShowUrl($booking);

        return view('bookings.cancel', compact('booking', 'policy', 'cancelPostUrl', 'bookingShowUrl'));
    }

    /**
     * Process booking cancellation.
     *
     * Trả JSON cho fetch/AJAX (trang bookings/cancel); redirect + flash cho form HTML.
     */
    public function cancel(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $this->authorizeBookingAccess($request, $booking);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->cancellationService->cancelBooking(
            $booking->id,
            $request->input('reason'),
            auth()->id()
        );

        $respondJson = $request->expectsJson() || $request->isJson() || $request->wantsJson();

        if ($result['success']) {
            $booking = $result['booking'];
            $redirectTo = $this->publicBookingShowUrl($booking);

            if ($respondJson) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'refund_amount' => $result['refund_amount'],
                    'refund_type' => $result['refund_type'],
                    'redirect_url' => $redirectTo,
                ]);
            }

            return redirect()->to($redirectTo)->with('success', $result['message']);
        }

        if ($respondJson) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return back()->withInput()->withErrors(['reason' => $result['message']]);
    }

    /**
     * Get cancellation policy via AJAX.
     */
    public function getPolicy(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBookingAccess($request, $booking);

        $policy = $this->cancellationService->getCancellationPolicy($booking);

        return response()->json([
            'success' => true,
            'policy' => $policy,
        ]);
    }

    /**
     * Admin cancel booking (with additional permissions).
     */
    public function adminCancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'force_cancel' => 'boolean',
        ]);

        $result = $this->cancellationService->cancelBooking(
            $id,
            $request->input('reason'),
            auth()->id(),
            true
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Đã hủy booking thành công.',
                'refund_amount' => $result['refund_amount'],
                'refund_type' => $result['refund_type'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }

    private function hasValidSignedAccess(Request $request): bool
    {
        return $request->hasValidSignatureWhileIgnoring(self::SIGNATURE_IGNORE_QUERY_PARAMS, true)
            || $request->hasValidSignatureWhileIgnoring(self::SIGNATURE_IGNORE_QUERY_PARAMS, false);
    }
}
