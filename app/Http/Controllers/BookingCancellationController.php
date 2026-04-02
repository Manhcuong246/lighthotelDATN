<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingCancellationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BookingCancellationController extends Controller
{
    public function __construct(
        private BookingCancellationService $cancellationService
    ) {}

    /**
     * Show cancellation confirmation page.
     */
    public function show(int $id): View
    {
        $booking = Booking::with(['user', 'room', 'room.roomType'])
            ->findOrFail($id);

        // Check if booking can be cancelled
        if ($booking->status === 'cancelled') {
            return redirect()->back()
                ->with('error', 'Booking này đã bị hủy trước đó.');
        }

        // Get cancellation policy
        $policy = $this->cancellationService->getCancellationPolicy($booking);

        return view('bookings.cancel', compact('booking', 'policy'));
    }

    /**
     * Process booking cancellation.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->cancellationService->cancelBooking(
            $id,
            $request->input('reason'),
            auth()->id()
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'refund_amount' => $result['refund_amount'],
                'refund_type' => $result['refund_type'],
                'redirect_url' => route('bookings.show', $id),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }

    /**
     * Get cancellation policy via AJAX.
     */
    public function getPolicy(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
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

        // Add admin-specific logic if needed
        $result = $this->cancellationService->cancelBooking(
            $id,
            $request->input('reason'),
            auth()->id()
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
}
