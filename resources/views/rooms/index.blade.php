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
            <div class="section-title">Loại phòng & giá</div>
            <h2 class="h4 mt-1 mb-0">Chọn loại phòng phù hợp cho kỳ nghỉ của bạn</h2>
        </div>
        <div class="text-muted small d-none d-md-block">
            {{ $roomTypes->total() }} loại phòng hiện có
        </div>
    </div>

    <div class="row g-4">
        @forelse($roomTypes as $type)
            <div class="col-md-4">
                <div class="card card-room h-100">
                    @if($type->image)
                        <img src="{{ asset('storage/' . $type->image) }}" class="card-img-top card-room-img" alt="{{ $type->name }}">
                    @else
                        <img src="https://images.pexels.com/photos/1571458/pexels-photo-1571458.jpeg?auto=compress&cs=tinysrgb&w=1200"
                             class="card-img-top card-room-img" alt="{{ $type->name }}">
                    @endif
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">{{ $type->name }}</h5>
                            <span class="badge rounded-pill badge-soft">
                                Loại phòng
                            </span>
                        </div>
                        <p class="card-text mb-2 text-muted small">
                            {{ $type->beds ?? 1 }} giường • {{ $type->baths ?? 1 }} phòng tắm • Tối đa {{ $type->capacity }} khách
                        </p>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="small text-muted">Giá từ</div>
                                <div class="fw-bold text-primary">
                                    {{ number_format($type->price, 0, ',', '.') }} VNĐ
                                    <span class="text-muted small">/ đêm</span>
                                </div>
                            </div>
                            @if($type->available_rooms_count > 0)
                                <span class="badge bg-success">Còn {{ $type->available_rooms_count }} phòng</span>
                            @else
                                <span class="badge bg-danger">Hết phòng</span>
                            @endif
                        </div>
                        <p class="card-text text-muted small flex-grow-1">
                            {{ Str::limit($type->description ?? 'Phòng tiêu chuẩn với đầy đủ tiện nghi hiện đại, phù hợp cho kỳ nghỉ của bạn.', 80) }}
                        </p>
                        <div class="d-grid gap-2 mt-auto">
                            @if($type->available_rooms_count > 0)
                                <a href="{{ route('roomtypes.show', $type) }}" class="btn btn-primary">
                                    <i class="bi bi-calendar-check"></i> Đặt ngay
                                </a>
                                <a href="{{ route('roomtypes.show', $type) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-eye"></i> Xem chi tiết
                                </a>
                            @else
                                <button class="btn btn-secondary" disabled>Hết phòng</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-muted py-5">Hiện chưa có loại phòng nào.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $roomTypes->links() }}
    </div>
@endsection


