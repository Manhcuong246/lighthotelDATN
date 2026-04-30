@extends('layouts.app')

@section('title', $room->name)

@php
    $imageUrls = $room->getDisplayImageUrls();
    $placeholderSvg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='500' viewBox='0 0 800 500'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%231e293b'/%3E%3Cstop offset='100%25' style='stop-color:%230f172a'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect fill='url(%23g)' width='800' height='500'/%3E%3Ctext fill='%2394a3b8' font-family='system-ui,sans-serif' font-size='20' x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle'%3ELight Hotel%3C/text%3E%3C/svg%3E";
    $avgRating = $room->reviews()->avg('rating');
    $reviewCount = $room->reviews()->count();
@endphp

@section('content')
<div class="room-detail py-5">
    <div class="container">
        {{-- Full-width Header --}}
        <div class="mb-4">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Phòng nghỉ</a></li>
                    <li class="breadcrumb-item active">{{ $room->name }}</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
                <div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 mb-2 rounded-3 fw-bold text-uppercase small letter-spacing-1">
                        {{ $room->type ?? 'Standard' }}
                    </span>
                    <h1 class="display-5 fw-bold text-dark mb-2">{{ $room->name }}</h1>
                    @if($reviewCount > 0)
                        <div class="d-flex align-items-center gap-2">
                            <div class="text-warning">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star{{ $i <= round($avgRating) ? '-fill' : '' }}"></i>
                                @endfor
                            </div>
                            <span class="fw-bold">{{ number_format($avgRating, 1) }}</span>
                            <span class="text-muted">({{ $reviewCount }} đánh giá)</span>
                        </div>
                    @endif
                </div>
                <div class="text-lg-end">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Giá từ</div>
                    <span class="h2 fw-bold text-primary mb-0">{{ number_format($room->catalogueBasePrice(), 0, ',', '.') }} VNĐ</span>
                    <span class="text-muted">/ đêm</span>
                </div>
            </div>
        </div>

        {{-- Main Row with Carousel and Sidebar --}}
        <div class="row g-5 align-items-start">
            {{-- Left Content: Room Images & Info --}}
            <div class="col-lg-8">
                {{-- Image Gallery --}}
                <div class="room-hero rounded-4 overflow-hidden mb-5 shadow-lg position-relative border">
                    <div id="roomCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            @forelse($imageUrls as $index => $url)
                                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                    <div class="ratio ratio-16x9">
                                        <img src="{{ $url }}" class="room-hero-img" alt="{{ $room->name }}"
                                             loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                             onerror="this.src='{{ $placeholderSvg }}'">
                                    </div>
                                </div>
                            @empty
                                <div class="carousel-item active">
                                    <div class="ratio ratio-16x9">
                                        <img src="{{ $placeholderSvg }}" class="room-hero-img" alt="Placeholder">
                                    </div>
                                </div>
                            @endforelse
                        </div>
                        @if(count($imageUrls) > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon p-3 bg-dark bg-opacity-50 rounded-circle" aria-hidden="true"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon p-3 bg-dark bg-opacity-50 rounded-circle" aria-hidden="true"></span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Room Features --}}
                <div class="row g-4 mb-5">
                    <div class="col-6 col-md-3">
                        <div class="feature-card h-100 p-3 rounded-4 bg-white shadow-sm border text-center">
                            <i class="bi bi-grid-3x3-gap text-primary fs-3 mb-2"></i>
                            <div class="fw-bold">{{ $room->beds }} Giường</div>
                            <div class="text-muted small">Loại giường lớn</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="feature-card h-100 p-3 rounded-4 bg-white shadow-sm border text-center">
                            <i class="bi bi-people text-primary fs-3 mb-2"></i>
                            <div class="fw-bold">Tối đa {{ $room->catalogueMaxGuests() }}</div>
                            <div class="text-muted small">Người lớn & Trẻ em</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="feature-card h-100 p-3 rounded-4 bg-white shadow-sm border text-center">
                            <i class="bi bi-rulers text-primary fs-3 mb-2"></i>
                            <div class="fw-bold">{{ $room->area ?? '28' }} m²</div>
                            <div class="text-muted small">Diện tích phòng</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="feature-card h-100 p-3 rounded-4 bg-white shadow-sm border text-center">
                            <i class="bi bi-droplet text-primary fs-3 mb-2"></i>
                            <div class="fw-bold">{{ $room->baths ?? '1' }} Toilet</div>
                            <div class="text-muted small">Phòng tắm riêng</div>
                        </div>
                    </div>
                </div>

                {{-- Description & Amenities --}}
                <div class="card border-0 rounded-4 shadow-sm mb-5">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="fw-bold mb-4">Chi tiết phòng</h4>
                        <div class="room-description fs-5 text-muted mb-5" style="line-height: 1.8;">
                            {!! nl2br(e($room->description)) !!}
                        </div>

                        <h4 class="fw-bold mb-4">Tiện ích phòng</h4>
                        <div class="amenities-grid">
                            @foreach($room->amenities as $amenity)
                                <div class="amenity-item">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    {{ $amenity->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Reviews --}}
                <div id="reviews" class="card border-0 rounded-4 shadow-sm mb-5 overflow-hidden scroll-review-section">
                    <div class="card-header bg-white p-4 border-bottom-0">
                        <h4 class="fw-bold mb-0">Đánh giá từ khách hàng</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="review-list">
                            @forelse($reviews as $review)
                                <div class="review-card p-4 rounded-4 bg-light mb-4 border-0">
                                    <div class="d-flex gap-3 mb-3">
                                        <div class="review-avatar flex-shrink-0">
                                            @if($review->user?->avatar_url)
                                                <img src="{{ str_starts_with($review->user->avatar_url, 'http') ? $review->user->avatar_url : asset('storage/' . $review->user->avatar_url) }}" class="rounded-circle shadow-sm" width="50" height="50">
                                            @else
                                                <div class="avatar-placeholder rounded-circle shadow-sm">{{ strtoupper(mb_substr($review->user->full_name ?? 'K', 0, 1)) }}</div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="fw-bold mb-0">{{ $review->user->full_name ?? 'Khách ẩn danh' }}</h6>
                                                <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                            </div>
                                            <div class="text-warning small">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                    @if($review->title)
                                        <h6 class="fw-bold mb-2">{{ $review->title }}</h6>
                                    @endif
                                    <p class="text-muted mb-0">{{ $review->comment }}</p>
                                    @if($review->reply)
                                        <div class="mt-3 p-3 rounded-3 bg-white border-start border-4 border-secondary">
                                            <div class="fw-bold text-secondary mb-1 small text-uppercase">Phản hồi từ khách sạn:</div>
                                            <p class="mb-0 text-dark">{{ $review->reply }}</p>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <i class="bi bi-chat-dots-fill display-4 text-light mb-3"></i>
                                    <p class="text-muted">Chưa có đánh giá nào.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Viết đánh giá (sau khi đã checkout + thanh toán — điều kiện giống ReviewController) --}}
                <div id="write-review" class="card border-0 rounded-4 shadow-sm mb-5 overflow-hidden scroll-review-section">
                    <div class="card-header bg-white p-4 border-bottom-0">
                        <h4 class="fw-bold mb-0">Viết đánh giá</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <p class="small text-muted mb-4">
                            Bạn có thể gửi <strong>nhiều đánh giá</strong> cho <strong>phòng vật lý này</strong>
                            (<strong>{{ $room->name }}</strong>@if($room->roomType), loại <strong>{{ $room->roomType->name }}</strong>@endif) sau mỗi lần đã thanh toán và check-out phòng đó.
                            Trong cùng một đơn có <strong>nhiều phòng khác số</strong>, mỗi phòng có trang riêng — đánh giá theo từng phòng bạn đã ở.
                        </p>
                        @include('rooms.partials.review-write-form', ['room' => $room])
                    </div>
                </div>
            </div>

            {{-- Right Content: Sidebar Booking Form --}}
            <div class="col-lg-4">
                <div class="booking-sidebar">
                    @include('rooms._booking-form')
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .room-hero-img { object-fit: cover; width: 100%; height: 100%; }
    .feature-card { transition: transform 0.3s; }
    .feature-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(15,23,42,0.1); }
    .amenities-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
    .amenity-item { display: flex; align-items: center; color: #4b5563; }
    .avatar-placeholder { width: 50px; height: 50px; background: #6366f1; color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; }

    /* Professional Sticky Sidebar CSS */
    .booking-sidebar {
        position: sticky;
        top: 24px; /* Fixed vertical offset for sticky mode */
        z-index: 1000;
        margin-top: 0; /* Ensures starting alignment is perfect */
    }

    /* Remove flex stretching to ensure sticky calculates against container correctly */
    .align-items-start {
        align-items: flex-start !important;
    }

    .scroll-review-section {
        scroll-margin-top: 6rem;
    }
</style>
@endpush
@endsection
