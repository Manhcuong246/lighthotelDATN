<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewAdminController extends Controller
{
    public function __construct()
    {
        // Apply admin middleware to all methods in this controller
        $this->middleware('admin');
    }

    public function index()
    {
        $reviews = Review::with(['user', 'room'])->latest()->paginate(15);
        return view('admin.reviews.index', compact('reviews'));
    }

    public function show(Review $review)
    {
        return view('admin.reviews.show', compact('review'));
    }

    public function edit(Review $review)
    {
        return view('admin.reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'reply' => 'nullable|string',
        ]);

        $review->update($validated);

        return redirect()->route('admin.reviews.index')->with('success', 'Cập nhật đánh giá thành công.');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')->with('success', 'Xóa đánh giá thành công.');
    }
}