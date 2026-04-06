<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only('store');
    }

    public function store(Request $request, Room $room)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|max:2000',
        ]);

        $userId = (int) auth()->id();
        $roomId = (int) $room->id;

        if (! \App\Models\Booking::userHasCheckedOutRoom($userId, $roomId)) {
            return back()->with(
                'error',
                'Chỉ khách đã thanh toán đơn này, đã đặt đúng phòng và đã check-out mới được đánh giá.'
            );
        }

        if (Review::userHasReviewedRoom($userId, $roomId)) {
            return back()->with('error', 'Bạn đã đánh giá phòng này rồi. Mỗi tài khoản chỉ được gửi một đánh giá cho một phòng.');
        }

        Review::create([
            'user_id' => auth()->id(),
            'room_id' => $room->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'Cảm ơn bạn đã đánh giá phòng.');
    }
}
