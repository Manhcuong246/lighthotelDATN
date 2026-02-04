@extends('layouts.app')

@section('title', $hotel->name ?? 'Danh sách phòng')

@section('content')
    <section class="hero-section mb-5">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content row g-4 align-items-center">
            <div class="col-lg-7">
                <div class="hero-badge mb-3">
                    <span>Luxury stay</span>
                    <span class="text-primary-emphasis bg-light rounded-pill px-2 py-1">Light Hotel</span>
                </div>
                <h1 class="hero-title">
                    Trải nghiệm kỳ nghỉ sang trọng<br>
                    ngay giữa lòng thành phố.
                </h1>
                <p class="hero-subtitle mb-4">
                    {{ $hotel->description ?? 'Light Hotel mang đến không gian nghỉ dưỡng chuẩn 4★ với tầm nhìn toàn cảnh thành phố, dịch vụ 24/7 và thiết kế hiện đại.' }}
                </p>
                <div class="d-flex flex-wrap align-items-center gap-3 hero-tags mb-4">
                    <span>Nhận phòng 24/7</span>
                    <span>Hủy miễn phí</span>
                    <span>Thanh toán tại khách sạn</span>
                </div>
                <a href="#rooms-section" class="btn btn-light btn-lg px-4 me-2">
                    Xem phòng trống
                </a>
                <button type="button" class="btn btn-outline-light btn-lg px-4 d-none d-sm-inline-flex">
                    Liên hệ lễ tân
                </button>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="bg-white bg-opacity-10 rounded-4 p-3">
                    <div class="bg-white bg-opacity-90 rounded-4 p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="text-muted small">Đánh giá trung bình</div>
                                <div class="h3 mb-0">
                                    {{ number_format($hotel->rating_avg ?? 4.8, 1) }}
                                    <span class="text-warning ms-1">★</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Khách đã nghỉ</div>
                                <div class="fw-semibold">+1.200</div>
                            </div>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 92%;"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Dịch vụ</span>
                            <span>Vị trí</span>
                            <span>Vệ sinh</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="rooms-section" class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <div class="section-title">Phòng & giá</div>
            <h2 class="h4 mt-1 mb-0">Chọn không gian phù hợp cho kỳ nghỉ của bạn</h2>
        </div>
        <div class="text-muted small d-none d-md-block">
            {{ $rooms->total() }} phòng hiện có
        </div>
    </div>

    <div class="row g-4">
        @forelse($rooms as $room)
            <div class="col-md-4">
                <div class="card card-room h-100">
                    @php
                        $image = $room->images->first();
                    @endphp
                    @if($image)
                        <img src="{{ $image->image_url }}" class="card-img-top card-room-img" alt="{{ $room->name }}">
                    @else
                        <img src="https://images.pexels.com/photos/1571458/pexels-photo-1571458.jpeg?auto=compress&cs=tinysrgb&w=1200"
                             class="card-img-top card-room-img" alt="{{ $room->name }}">
                    @endif
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">{{ $room->name }}</h5>
                            <span class="badge rounded-pill badge-soft">
                                {{ $room->type ?? 'Tiêu chuẩn' }}
                            </span>
                        </div>
                        <p class="card-text mb-2 text-muted small">
                            {{ $room->beds }} giường • {{ $room->baths }} phòng tắm • Tối đa {{ $room->max_guests }} khách
                        </p>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="small text-muted">Giá từ</div>
                                <div class="fw-bold text-primary">
                                    {{ number_format($room->base_price, 0, ',', '.') }} VNĐ
                                    <span class="text-muted small">/ đêm</span>
                                </div>
                            </div>
                            <div class="text-end small text-muted">
                                <div>Miễn phí hủy</div>
                                <div>Thanh toán tại khách sạn</div>
                            </div>
                        </div>
                        @auth
                            @if(auth()->user()->canAccessAdmin())
                                <div class="d-flex gap-2 mt-auto">
                                    <a href="{{ route('rooms.show', $room) }}" class="btn btn-outline-primary flex-grow-1">Xem chi tiết</a>
                                    <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-primary">Sửa</a>
                                </div>
                            @else
                                <a href="{{ route('rooms.show', $room) }}" class="btn btn-primary mt-auto w-100">Xem chi tiết &amp; đặt phòng</a>
                            @endif
                        @else
                            <a href="{{ route('rooms.show', $room) }}" class="btn btn-primary mt-auto w-100">Xem chi tiết &amp; đặt phòng</a>
                        @endauth
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-muted py-5">Hiện chưa có phòng nào.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $rooms->links() }}
    </div>
@endsection


