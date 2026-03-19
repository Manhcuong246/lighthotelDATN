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

        $existing = Review::where('room_id', $room->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            return back()->with('error', 'Bạn đã đánh giá phòng này rồi. Chỉ có thể sửa qua quản trị.');
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
