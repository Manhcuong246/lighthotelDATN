<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Room;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['store', 'create']);
    }

    public function create(Request $request, Room $room)
    {
        $room->load('roomType');
        $userId = (int) auth()->id();
        $roomId = (int) $room->id;

        $reviewable = Booking::reviewableBookingsForRoom($userId, $roomId);

        if ($reviewable->isEmpty()) {
            return redirect()
                ->route('account.bookings')
                ->with('error', 'Bạn chưa có lượt lưu trú hoàn tất (thanh toán + check-out) nào cho phòng này, hoặc đã gửi đánh giá cho tất cả các lượt đó.');
        }

        $prefillBookingId = null;
        if ($request->filled('booking')) {
            $bid = (int) $request->query('booking');
            if ($reviewable->firstWhere('id', $bid)) {
                $prefillBookingId = $bid;
            }
        }
        if ($prefillBookingId === null) {
            $prefillBookingId = (int) $reviewable->first()->id;
        }

        return view('reviews.create', compact('room', 'reviewable', 'prefillBookingId'));
    }

    public function store(Request $request, Room $room)
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|max:2000',
        ]);

        $userId = (int) auth()->id();
        $roomId = (int) $room->id;
        $bookingId = (int) $validated['booking_id'];

        $booking = Booking::query()->whereKey($bookingId)->where('user_id', $userId)->first();
        if (! $booking) {
            return back()->with('error', 'Đơn đặt không hợp lệ.')->withInput();
        }

        if (! $booking->userCanSubmitReviewForRoom($roomId)) {
            return back()->with(
                'error',
                'Bạn không thể gửi đánh giá cho lượt lưu trú này (chưa check-out / chưa thanh toán, hoặc phòng không thuộc đơn, hoặc đã đánh giá rồi).'
            )->withInput();
        }

        if (Review::existsForBookingAndRoom($bookingId, $roomId)) {
            return back()->with('error', 'Đơn này đã có đánh giá cho phòng này.')->withInput();
        }

        Review::create([
            'user_id' => $userId,
            'room_id' => $roomId,
            'booking_id' => $bookingId,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
        ]);

        $return = $request->input('_return');
        if (is_string($return) && $return !== '' && str_starts_with($return, '/') && ! str_starts_with($return, '//')) {
            return redirect()->to($return)->with('success', 'Cảm ơn bạn đã đánh giá phòng.');
        }

        return back()->with('success', 'Cảm ơn bạn đã đánh giá phòng.');
    }
}
