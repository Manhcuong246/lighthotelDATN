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
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-3">Đặt phòng</h4>
                    <form method="POST" action="{{ route('bookings.store', $room) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="full_name" class="form-control"
                                   value="{{ old('full_name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control"
                                   value="{{ old('phone') }}">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày nhận phòng</label>
                                <input type="date" name="check_in" class="form-control"
                                       value="{{ old('check_in') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày trả phòng</label>
                                <input type="date" name="check_out" class="form-control"
                                       value="{{ old('check_out') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số lượng khách</label>
                            <input type="number" name="guests" class="form-control"
                                   min="1" max="{{ $room->max_guests }}"
                                   value="{{ old('guests', 1) }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Xác nhận đặt phòng
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


