@extends('layouts.app')

@section('title', $room->displayLabel())

@php
    $imageUrls = $room->getDisplayImageUrls();
    $room->loadMissing('roomType');
    $placeholderSvg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='500' viewBox='0 0 800 500'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%231e293b'/%3E%3Cstop offset='100%25' style='stop-color:%230f172a'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect fill='url(%23g)' width='800' height='500'/%3E%3Ctext fill='%2394a3b8' font-family='system-ui,sans-serif' font-size='20' x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle'%3ELight Hotel%3C/text%3E%3C/svg%3E";
    $reviewsBaseQuery = $room->reviews();
    $avgRating = (float) ($reviewsBaseQuery->avg('rating') ?? 0);
    $reviewCount = (int) $reviewsBaseQuery->count();
    $roomTypeName = $room->roomType?->name ?? $room->type;
@endphp

@section('content')
<div class="room-detail py-5">
    <div class="container">
        {{-- Full-width Header --}}
        <div class="mb-4">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Danh sách phòng</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $room->displayLabel() }}</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
                <div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 mb-2 rounded-3 fw-bold text-uppercase small letter-spacing-1">
                        {{ $roomTypeName ?? 'Phòng' }}
                    </span>
                    <h1 class="display-5 fw-bold text-dark mb-2">{{ $room->name }}</h1>
                    <div class="text-muted small mb-2">
                        @if($room->room_number)
                            <span class="me-3"><i class="bi bi-hash me-1"></i>Số phòng: <strong>{{ $room->room_number }}</strong></span>
                        @endif
                        @if($room->roomType)
                            <span><i class="bi bi-layers me-1"></i>Loại: <strong>{{ $room->roomType->name }}</strong></span>
                        @endif
                    </div>
                    @if($reviewCount > 0)
                        <div class="d-flex align-items-center gap-2">
                            <div class="text-warning" aria-label="Điểm {{ number_format($avgRating, 1) }} trên 5">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star{{ $i <= round($avgRating) ? '-fill' : '' }}"></i>
                                @endfor
                            </div>
                            <span class="fw-bold">{{ number_format($avgRating, 1) }}</span>
                            <span class="text-muted">/ 5 · {{ $reviewCount }} đánh giá</span>
                        </div>
                    @else
                        <p class="text-muted small mb-0">Chưa có đánh giá. Hãy là khách đầu tiên chia sẻ trải nghiệm sau khi lưu trú.</p>
                    @endif
                </div>
                <div class="text-lg-end">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Giá tham khảo</div>
                    <span class="h2 fw-bold text-primary mb-0">{{ number_format($room->catalogueBasePrice(), 0, ',', '.') }} ₫</span>
                    <span class="text-muted">/ đêm</span>
                    <div class="mt-3">
                        <a href="{{ route('home') }}" class="btn btn-primary btn-sm rounded-pill px-4">Tìm phòng &amp; đặt</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Nội dung chính (full width, không còn form đặt phòng cạnh) --}}
        <div class="row g-5 align-items-start">
            <div class="col-12">
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
                        @php $desc = trim((string) $room->description); @endphp
                        @if($desc !== '')
                        <div class="room-description fs-6 text-muted mb-5" style="line-height: 1.8;">
                            {!! nl2br(e($desc)) !!}
                        </div>
                        @else
                        <p class="text-muted mb-5">Chưa có mô tả chi tiết cho phòng này.</p>
                        @endif

                        <h4 class="fw-bold mb-4">Tiện ích phòng</h4>
                        @if($room->amenities->isNotEmpty())
                        <div class="amenities-grid">
                            @foreach($room->amenities as $amenity)
                                <div class="amenity-item">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    {{ $amenity->name }}
                                </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted small mb-0">Chưa cập nhật danh sách tiện ích.</p>
                        @endif
                    </div>
                </div>

                {{-- Đánh giá (chỉ hiển thị đánh giá công khai; nội dung escape + xuống dòng) --}}
                <div id="reviews" class="card border-0 rounded-4 shadow-sm mb-5 overflow-hidden scroll-review-section">
                    <div class="card-header bg-white p-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h4 class="fw-bold mb-0">Đánh giá từ khách</h4>
                        @if($reviewCount > 0)
                            <span class="badge bg-light text-dark border">{{ $reviewCount }} nhận xét</span>
                        @endif
                    </div>
                    <div class="card-body p-4">
                        <div class="review-list">
                            @forelse($reviews as $review)
                                <article class="review-card p-4 rounded-4 bg-light mb-4 border-0">
                                    <div class="d-flex gap-3 mb-3">
                                        <div class="review-avatar flex-shrink-0">
                                            @if($review->user?->avatar_url)
                                                <img src="{{ str_starts_with($review->user->avatar_url, 'http') ? $review->user->avatar_url : asset('storage/' . $review->user->avatar_url) }}" class="rounded-circle shadow-sm" width="48" height="48" alt="">
                                            @else
                                                <div class="avatar-placeholder rounded-circle shadow-sm" style="width:48px;height:48px;">{{ strtoupper(mb_substr($review->user->full_name ?? 'K', 0, 1)) }}</div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
                                                <div>
                                                    <h6 class="fw-bold mb-0">{{ $review->user->full_name ?? 'Khách' }}</h6>
                                                    <div class="text-warning small mt-1" aria-label="{{ $review->rating }} sao">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                                        @endfor
                                                        <span class="text-muted ms-1">{{ $review->rating }}/5</span>
                                                    </div>
                                                </div>
                                                <div class="text-end small text-muted">
                                                    <time datetime="{{ $review->created_at?->toIso8601String() }}">{{ $review->created_at?->format('d/m/Y H:i') }}</time>
                                                    <div>{{ $review->created_at?->diffForHumans() }}</div>
                                                </div>
                                            </div>
                                            @if($review->booking_id)
                                                <span class="badge bg-white text-success border small">Đã xác minh lưu trú</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($review->title)
                                        <h6 class="fw-bold mb-2">{{ $review->title }}</h6>
                                    @endif
                                    <div class="review-comment text-body mb-0" style="line-height: 1.65;">
                                        {!! nl2br(e($review->comment)) !!}
                                    </div>
                                    @if($review->reply)
                                        <div class="mt-3 p-3 rounded-3 bg-white border-start border-4 border-secondary">
                                            <div class="fw-bold text-secondary mb-1 small text-uppercase">Phản hồi từ khách sạn</div>
                                            <div class="mb-0 text-dark">{!! nl2br(e($review->reply)) !!}</div>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="text-center py-5">
                                    <i class="bi bi-chat-quote display-5 text-muted mb-3 d-block"></i>
                                    <p class="text-muted mb-0">Chưa có đánh giá nào cho phòng này.</p>
                                </div>
                            @endforelse
                        </div>

                        @if($reviews->hasPages())
                            <div class="d-flex justify-content-center pt-2">
                                {{ $reviews->links() }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Viết đánh giá: 1 nhận xét / lượt lưu trú (booking + phòng), sau khi thanh toán & check-out --}}
                <div id="write-review" class="card border-0 rounded-4 shadow-sm mb-5 overflow-hidden scroll-review-section">
                    <div class="card-header bg-white p-4 border-bottom-0">
                        <h4 class="fw-bold mb-0">Viết đánh giá</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <p class="small text-muted mb-4">
                            Chính sách: <strong>một đánh giá cho mỗi đơn đặt</strong> (sau khi đã thanh toán và đã làm thủ tục trả phòng).
                            Bạn có thể đánh giá lại khi có <strong>lưu trú mới</strong> tại phòng này.
                        </p>
                        @include('rooms.partials.review-write-form', [
                            'room' => $room,
                            'reviewableBookings' => $reviewableBookings,
                            'reviewReturnUrl' => url()->current() . '#write-review',
                        ])
                    </div>
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

    .scroll-review-section {
        scroll-margin-top: 6rem;
    }
</style>
@endpush
@endsection
