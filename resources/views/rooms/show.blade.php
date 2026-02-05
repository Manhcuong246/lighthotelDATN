@extends('layouts.app')

@section('title', $room->name)

@section('content')
    <div class="row">
        <div class="col-md-7">
            <div id="roomCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @forelse($room->images as $index => $image)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <img src="{{ $image->image_url }}" class="d-block w-100" alt="{{ $room->name }}">
                        </div>
                    @empty
                        <div class="carousel-item active">
                            <img src="https://via.placeholder.com/800x500?text=Light+Hotel" class="d-block w-100"
                                 alt="{{ $room->name }}">
                        </div>
                    @endforelse
                </div>
                @if($room->images->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                @endif
            </div>

            <h2 class="fw-bold">{{ $room->name }}</h2>
            <p class="text-muted">Loại phòng: {{ $room->type ?? 'Đang cập nhật' }}</p>
            <p>
                <strong>{{ number_format($room->base_price, 0, ',', '.') }} VNĐ</strong> / đêm
            </p>
            <p class="text-muted">
                {{ $room->beds }} giường • {{ $room->baths }} phòng tắm • Tối đa {{ $room->max_guests }} khách
                @if($room->area)
                    • {{ $room->area }} m²
                @endif
            </p>

            <h5 class="mt-4">Mô tả</h5>
            <p>{{ $room->description ?? 'Đang cập nhật.' }}</p>

            <h5 class="mt-4">Tiện ích</h5>
            <ul class="list-inline">
                @forelse($room->amenities as $amenity)
                    <li class="list-inline-item badge bg-secondary me-1 mb-1">
                        {{ $amenity->name }}
                    </li>
                @empty
                    <li>Chưa có thông tin tiện ích.</li>
                @endforelse
            </ul>

            <h5 class="mt-4">Đánh giá</h5>
            @forelse($room->reviews as $review)
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $review->user->full_name ?? 'Khách ẩn danh' }}</strong>
                        <span class="text-warning">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $review->rating)
                                    ★
                                @else
                                    ☆
                                @endif
                            @endfor
                        </span>
                    </div>
                    <p class="mb-1">{{ $review->comment }}</p>
                    @if($review->reply)
                        <p class="mb-0 text-muted small">
                            <strong>Phản hồi:</strong> {{ $review->reply }}
                        </p>
                    @endif
                </div>
            @empty
                <p>Chưa có đánh giá nào cho phòng này.</p>
            @endforelse
        </div>

        <div class="col-md-5">
            @auth
                @if(auth()->user()->canAccessAdmin())
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title mb-3">Quản lý phòng</h4>
                            <p class="text-muted small mb-3">Bạn đang đăng nhập với vai trò quản trị. Chỉ xem thông tin phòng, không đặt phòng tại đây.</p>
                            <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-primary w-100">Sửa phòng</a>
                            <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary w-100 mt-2">Quay lại danh sách phòng</a>
                        </div>
                    </div>
                @else
                    @include('rooms._booking-form')
                @endif
            @else
                @include('rooms._booking-form')
            @endauth
        </div>
    </div>
@endsection


