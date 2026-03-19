@extends('layouts.app')

@section('title', $room->name)

@php
    $imageUrls = $room->getDisplayImageUrls();
    $placeholderSvg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='500' viewBox='0 0 800 500'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%231e293b'/%3E%3Cstop offset='100%25' style='stop-color:%230f172a'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect fill='url(%23g)' width='800' height='500'/%3E%3Ctext fill='%2394a3b8' font-family='system-ui,sans-serif' font-size='20' x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle'%3ELight Hotel%3C/text%3E%3C/svg%3E";
    $avgRating = $room->reviews()->avg('rating');
    $reviewCount = $room->reviews()->count();
    $userHasReviewed = auth()->check() && $room->reviews()->where('user_id', auth()->id())->exists();
@endphp

@section('content')
<div class="room-detail">
    <div class="container">
        <div class="row g-4">
            <div class="col-12">
                {{-- Top layout: slideshow (left) + description & amenities (right) --}}
                <div class="row g-4 align-items-stretch mb-4">
                    <div class="col-lg-6 d-flex">
                        <div class="room-hero position-relative rounded-4 overflow-hidden shadow-lg flex-grow-1">
                            {{-- Fixed aspect container - ảnh luôn đồng đều --}}
                            <div class="room-hero-aspect">
                                <div id="roomCarousel" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        @forelse($imageUrls as $index => $url)
                                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                                <div class="room-hero-img-wrap">
                                                    <img src="{{ $url }}" class="room-hero-img" alt="{{ $room->name }}"
                                                         loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                                         data-fallback="{{ $placeholderSvg }}"
                                                         onerror="var f=this.dataset.fallback;if(f){this.onerror=null;this.src=f}">
                                                </div>
                                            </div>
                                        @empty
                                            <div class="carousel-item active">
                                                <div class="room-hero-img-wrap">
                                                    <img src="{{ $placeholderSvg }}" class="room-hero-img" alt="{{ $room->name }}">
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>

                                    @if(count($imageUrls) > 1)
                                        <button class="carousel-control-prev room-carousel-btn" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <button class="carousel-control-next room-carousel-btn" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                        <div class="carousel-indicators room-carousel-dots">
                                            @foreach($imageUrls as $i => $_)
                                                <button type="button" data-bs-target="#roomCarousel" data-bs-slide-to="{{ $i }}"
                                                        class="{{ $i === 0 ? 'active' : '' }}" aria-label="Ảnh {{ $i + 1 }}"></button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex flex-column h-100">
                        {{-- Summary --}}
                        <section class="mb-4">
                            <p class="text-muted mb-1 small" style="opacity: 0.9;">{{ $room->type ?? 'Đang cập nhật' }}</p>
                            <h1 class="room-summary-title mb-2">{{ $room->name }}</h1>

                            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                <span class="room-price-display">{{ number_format($room->base_price, 0, ',', '.') }} VNĐ</span>
                                <span class="opacity-75">/ đêm</span>
                                @if($reviewCount > 0)
                                    <span class="d-flex align-items-center gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="bi bi-star{{ $i <= round($avgRating) ? '-fill' : '' }} text-warning"></i>
                                        @endfor
                                        <span class="ms-1">{{ number_format($avgRating, 1) }}</span>
                                        <span class="opacity-75 small">({{ $reviewCount }} đánh giá)</span>
                                    </span>
                                @endif
                            </div>

                            <div class="room-summary-details d-flex flex-wrap gap-3 text-muted small" style="opacity: 0.95;">
                                <span><i class="bi bi-grid-3x3-gap me-1"></i>{{ $room->beds }} giường</span>
                                <span><i class="bi bi-droplet me-1"></i>{{ $room->baths }} phòng tắm</span>
                                <span><i class="bi bi-people me-1"></i>Tối đa {{ $room->max_guests }} khách</span>
                                @if($room->area)
                                    <span><i class="bi bi-rulers me-1"></i>{{ $room->area }} m²</span>
                                @endif
                            </div>
                        </section>

                        {{-- Description --}}
                        <section class="room-section-card mb-4">
                            <h5 class="room-section-title">Mô tả</h5>
                            <p class="room-description mb-0">{{ $room->description ?? 'Đang cập nhật.' }}</p>
                        </section>

                        {{-- Amenities --}}
                        <section class="room-section-card mb-0 mt-auto">
                            <h5 class="room-section-title">Tiện ích</h5>
                            @if($room->amenities->isNotEmpty())
                                <div class="room-amenities-grid">
                                    @foreach($room->amenities as $amenity)
                                        <span class="room-amenity-tag">{{ $amenity->name }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">Chưa có thông tin tiện ích.</p>
                            @endif
                        </section>
                    </div>
                </div>

                {{-- Reviews: Xem công khai, viết cần đăng nhập --}}
                <section class="room-section-card room-reviews mb-4" id="reviews">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                        <h5 class="room-section-title mb-0">Đánh giá</h5>
                        @if($reviewCount > 0)
                            <span class="text-muted small">{{ $reviewCount }} đánh giá</span>
                        @endif
                    </div>

                    {{-- Danh sách đánh giá: ai cũng xem được (không cần đăng nhập) --}}
                    <div class="review-list">
                        @forelse($reviews as $review)
                            <article class="room-review-card">
                                <div class="d-flex gap-3">
                                    <div class="review-avatar">
                                        @if($review->user?->avatar_url)
                                            <img src="{{ str_starts_with($review->user->avatar_url, 'http') ? $review->user->avatar_url : asset('storage/' . $review->user->avatar_url) }}" alt="">
                                        @else
                                            <span>{{ strtoupper(mb_substr($review->user->full_name ?? 'K', 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
                                            <strong class="review-author">{{ $review->user->full_name ?? 'Khách ẩn danh' }}</strong>
                                            <span class="review-date text-muted small">{{ $review->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="review-rating text-warning mb-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                            @endfor
                                        </div>
                                        @if($review->title)
                                            <h6 class="review-title mb-1">{{ $review->title }}</h6>
                                        @endif
                                        <p class="review-comment mb-0">{{ $review->comment }}</p>
                                        @if($review->reply)
                                            <div class="review-reply mt-2">
                                                <strong class="small text-muted">Phản hồi từ khách sạn:</strong>
                                                <p class="mb-0 small">{{ $review->reply }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="text-muted mb-0">Chưa có đánh giá nào cho phòng này.</p>
                        @endforelse
                    </div>
                    @if($reviews->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $reviews->links('pagination::bootstrap-5') }}
                        </div>
                    @endif

                    {{-- Form viết đánh giá: chỉ hiện khi đã đăng nhập --}}
                    @if(session('error'))
                        <div class="alert alert-warning mt-4">{{ session('error') }}</div>
                    @endif
                    @if($errors->has('rating') || $errors->has('comment'))
                        <div class="alert alert-danger mt-4">
                            @foreach($errors->get('rating') as $e) <div>{{ $e }}</div> @endforeach
                            @foreach($errors->get('comment') as $e) <div>{{ $e }}</div> @endforeach
                        </div>
                    @endif
                    @auth
                        @if(!auth()->user()->canAccessAdmin() && !$userHasReviewed)
                            <div class="room-review-form mt-4 pt-4 border-top">
                                <label class="form-label fw-semibold">Viết đánh giá của bạn</label>
                                <form method="POST" action="{{ route('reviews.store', $room) }}" class="review-form">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Điểm đánh giá</label>
                                        <div class="rating-input d-flex gap-1">
                                            @for($i = 5; $i >= 1; $i--)
                                                <input type="radio" name="rating" value="{{ $i }}" id="r{{ $i }}" required>
                                                <label for="r{{ $i }}" class="rating-star"><i class="bi bi-star"></i></label>
                                            @endfor
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Tiêu đề (tùy chọn)</label>
                                        <input type="text" name="title" class="form-control" placeholder="VD: Phòng rất sạch sẽ"
                                               value="{{ old('title') }}" maxlength="255">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Nội dung đánh giá <span class="text-danger">*</span></label>
                                        <textarea name="comment" class="form-control" rows="3" required
                                                  placeholder="Chia sẻ trải nghiệm của bạn..."
                                                  maxlength="2000">{{ old('comment') }}</textarea>
                                        <small class="text-muted">Tối đa 2000 ký tự</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                                </form>
                            </div>
                        @elseif($userHasReviewed)
                            <p class="text-muted small mt-4 pt-4 border-top mb-0">Bạn đã đánh giá phòng này.</p>
                        @endif
                    @else
                        <div class="mt-4 pt-4 border-top">
                            <p class="text-muted mb-2">Bạn muốn để lại đánh giá? Vui lòng đăng nhập.</p>
                            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Đăng nhập để viết đánh giá</a>
                        </div>
                    @endauth
                </section>
            </div>

        </div>

        {{-- Booking form: full width at bottom --}}
        <section id="booking-form" class="room-booking-section mt-5 mb-5">
            @auth
                @if(!auth()->user()->canAccessAdmin())
                    @include('rooms._booking-form')
                @endif
            @else
                @include('rooms._booking-form')
            @endauth
        </section>
    </div>
</div>

@push('styles')
<style>
/* === Room Detail Base === */
.room-detail { padding-bottom: 3rem; }
html { scroll-behavior: smooth; }

/* === Slideshow: Fixed aspect - ảnh luôn đồng đều === */
.room-hero-wrapper { max-width: 1400px; margin-left: auto; margin-right: auto; padding: 0 1rem; }
.room-hero { background: #0f172a; }
.room-hero {
    display: flex;
    height: 100%;
}
.room-hero-aspect {
    aspect-ratio: auto;
    width: 100%;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    position: relative;
}
.room-hero-aspect .carousel,
.room-hero-aspect .carousel-inner,
.room-hero-aspect .carousel-item {
    height: 100%;
}
.room-hero-aspect .carousel-item { transition: transform 0.5s ease; }
.room-hero-img-wrap {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}
.room-hero-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}
.room-hero .carousel-item { position: relative; }

/* Carousel controls */
.room-carousel-btn {
    position: absolute;
    width: 36px; height: 36px;
    border-radius: 0;
    background: transparent !important;
    color: #ffffff !important;
    opacity: 1;
    border: none;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
    transition: background 0.2s, transform 0.2s;
}
.room-carousel-btn:hover { background: transparent !important; transform: translateY(-50%) scale(1.08); }
.room-carousel-btn i { font-size: 1.25rem; }
.room-carousel-btn.carousel-control-prev { left: 0.5rem; right: auto; }
.room-carousel-btn.carousel-control-next { right: 0.5rem; left: auto; }
.room-carousel-dots {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0.75rem;
    margin-bottom: 0;
    z-index: 15;
    gap: 8px;
    display: flex;
    justify-content: center;
}
.room-carousel-dots button,
.room-carousel-dots [data-bs-target] {
    width: 24px !important;
    height: 2px !important;
    min-width: 24px !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    border-radius: 0 !important;
    background: rgba(255,255,255,0.85) !important;
    flex: 0 0 24px !important;
    transition: background 0.2s, opacity 0.2s;
}
.room-carousel-dots button.active,
.room-carousel-dots [data-bs-target].active {
    background: #fff !important;
    opacity: 1;
}

/* Hero overlay */
.room-hero-overlay {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: linear-gradient(transparent 0%, rgba(0,0,0,0.6) 40%, rgba(0,0,0,0.92) 100%);
    pointer-events: none;
    z-index: 10;
}
.room-hero-overlay .btn { pointer-events: auto; box-shadow: 0 4px 14px rgba(59,130,246,0.4); }
.room-title { font-size: clamp(1.5rem, 4vw, 2.25rem); font-weight: 700; text-shadow: 0 2px 8px rgba(0,0,0,0.5); letter-spacing: -0.02em; }
.room-price-display { font-size: 1.5rem; font-weight: 700; }
/* Title on the right side (không dùng overlay nên cần màu/dạng text khác) */
.room-summary-title {
    font-size: clamp(1.45rem, 3vw, 2.1rem);
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.room-summary-details i {
    color: #3b82f6;
    opacity: 0.95;
}

/* === Section cards === */
.room-section-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.06);
}
.room-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 1rem;
    letter-spacing: -0.01em;
}
.room-description { color: #475569; line-height: 1.75; font-size: 0.95rem; }
.room-amenities-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.room-amenity-tag {
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    color: #475569;
    padding: 0.4em 0.9em;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
}

/* === Reviews === */
.room-review-form {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
}
.rating-input { flex-direction: row-reverse; justify-content: flex-end; }
.rating-input input { display: none; }
.rating-input label { cursor: pointer; font-size: 1.5rem; color: #cbd5e1; transition: color 0.15s; }
.rating-input label:hover, .rating-input label:hover ~ label,
.rating-input input:checked ~ label { color: #f59e0b; }

.room-review-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    transition: box-shadow 0.2s;
}
.room-review-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.review-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: #fff; font-weight: 600; font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.review-avatar img { width: 100%; height: 100%; object-fit: cover; }
.review-author { color: #0f172a; font-weight: 600; }
.review-date { font-size: 0.8rem; color: #94a3b8; }
.review-title { font-size: 0.95rem; color: #334155; font-weight: 500; }
.review-comment { color: #475569; line-height: 1.65; }
.review-reply { background: #f8fafc; padding: 0.75rem; border-radius: 8px; border-left: 3px solid #3b82f6; }

.room-booking-section { scroll-margin-top: 2rem; }

/* === Responsive: Mobile === */
@media (max-width: 767px) {
    .room-hero-aspect {
        aspect-ratio: 4 / 3;
        flex: initial;
        min-height: auto;
    }
    .room-title {
        font-size: clamp(1.15rem, 5vw, 1.45rem);
        margin-bottom: 0.5rem !important;
        line-height: 1.15;
    }
    .room-price-display { font-size: 1.05rem; }
    .room-hero-img {
        /* Mobile: đảm bảo ảnh hiển thị đầy đủ, hạn chế bị cắt */
        object-fit: contain;
        background: #0f172a;
    }
    .room-hero-overlay {
        bottom: 0;
        padding-bottom: env(safe-area-inset-bottom, 0);
    }
    .room-hero-overlay .py-4 {
        padding-top: 0.35rem !important;
        padding-bottom: 0.35rem !important;
    }
    .room-carousel-btn {
        width: 36px; height: 36px;
        z-index: 40 !important;
    }
    .room-carousel-btn i { font-size: 1rem; }
    .room-carousel-btn.carousel-control-prev { left: 0.25rem; }
    .room-carousel-btn.carousel-control-next { right: 0.25rem; }
    .room-hero-overlay .container { padding-left: 0.75rem; padding-right: 0.75rem; }
    .room-hero-details {
        display: none !important;
    }
    .room-hero-overlay .col-lg-4 .btn {
        margin-top: 0.25rem;
    }
    /* Rating chiếm chỗ khá nhiều trên mobile => ẩn để overlay gọn */
    .room-hero-overlay .d-flex.align-items-center.gap-1 { display: none !important; }
    /* Dots điều hướng phải nổi trên overlay */
    .room-carousel-dots {
        z-index: 40 !important;
        bottom: 0.35rem !important;
    }
    /* Thu nhỏ CTA "Đặt phòng" */
    .room-hero-overlay .btn.btn-lg {
        padding: 0.5rem 0.95rem !important;
        font-size: 0.92rem !important;
        border-radius: 0.75rem;
        width: auto !important;
        display: inline-flex !important;
        justify-content: center;
    }
}
</style>
@endpush
@endsection
