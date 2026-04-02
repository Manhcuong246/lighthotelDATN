<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

<<<<<<< HEAD
    public function index()
    {
        $reviews = Review::with(['user', 'room'])->latest()->paginate(15);
=======
    public function index(Request $request)
    {
        $query = Review::with(['user', 'room'])->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('name', 'like', "%{$q}%"))
                    ->orWhere('comment', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%");
            });
        }

        $reviews = $query->paginate(15)->withQueryString();
>>>>>>> vinam
        return view('admin.reviews.index', compact('reviews'));
    }

    public function show(Review $review)
    {
        return view('admin.reviews.show', compact('review'));
    }

<<<<<<< HEAD
=======
    public function reply(Request $request, Review $review)
    {
        $validated = $request->validate([
            'reply' => 'nullable|string',
        ]);

        $reply = $validated['reply'] ?? null;
        if ($reply !== null) {
            $reply = trim($reply);
            if ($reply === '') {
                $reply = null;
            }
        }

        $review->update([
            'reply' => $reply,
            'replied_at' => $reply ? now() : null,
        ]);

        return redirect()
            ->route('admin.reviews.show', $review)
            ->with('success', 'Đã cập nhật phản hồi cho đánh giá.');
    }

>>>>>>> vinam
    public function edit(Review $review)
    {
        return view('admin.reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
<<<<<<< HEAD
=======
            'title' => 'nullable|string|max:255',
>>>>>>> vinam
            'comment' => 'nullable|string',
            'reply' => 'nullable|string',
        ]);

<<<<<<< HEAD
        $review->update($validated);
=======
        $reply = $validated['reply'] ?? null;
        if ($reply !== null) {
            $reply = trim($reply);
            if ($reply === '') {
                $reply = null;
            }
        }

        $repliedAt = $reply ? now() : null;
        $review->update([
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'reply' => $reply,
            'replied_at' => $repliedAt,
        ]);
>>>>>>> vinam

        return redirect()->route('admin.reviews.index')->with('success', 'Cập nhật đánh giá thành công.');
    }

    public function destroy(Review $review)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới được xóa đánh giá.');
        }
        $review->delete();

        return redirect()->route('admin.reviews.index')->with('success', 'Xóa đánh giá thành công.');
    }
}