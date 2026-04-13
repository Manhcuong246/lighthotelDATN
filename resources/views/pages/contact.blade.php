@extends('layouts.app')

@section('title', 'Liên hệ — ' . ($hotelInfo?->name ?? 'Light Hotel'))

@section('content')
<div class="lh-breakout lh-page-hero lh-page-hero--photo mb-4">
    <div class="container lh-page-hero-inner">
        <nav class="lh-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span class="text-white opacity-50 mx-2">/</span>
            <span class="active">Liên hệ</span>
        </nav>
        <h1>Liên hệ</h1>
        <p class="lh-page-lead mt-2">Kênh hỗ trợ chính thức của {{ $hotelInfo?->name ?? 'Light Hotel' }} — đặt phòng, dịch vụ lưu trú và yêu cầu đặc biệt.</p>
    </div>
</div>

<div class="lh-page-body">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="lh-contact-banner-img mb-4">
                    <img src="https://images.pexels.com/photos/2869215/pexels-photo-2869215.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1600" alt="Lễ tân {{ $hotelInfo?->name ?? 'Light Hotel' }}" loading="lazy" width="1200" height="500">
                </div>
            </div>
            <div class="col-lg-5">
                <div class="lh-glass-card p-4 h-100 d-flex flex-column justify-content-center">
                    <p class="lh-section-eyebrow mb-2">Phản hồi nhanh</p>
                    <h2 class="h4 fw-bold text-dark mb-3">Chúng tôi luôn sẵn sàng hỗ trợ</h2>
                    <p class="text-secondary mb-0">Gửi email hoặc gọi trực tiếp trong giờ làm việc. Đối với yêu cầu khẩn (nhận phòng trong ngày), vui lòng gọi điện để lễ tân xử lý ngay.</p>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-stretch">
            <div class="col-lg-7">
                <div class="lh-glass-card p-4 p-lg-5 h-100">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-1">Thông tin liên hệ</h2>
                            <p class="small text-secondary mb-0">Địa chỉ, điện thoại và email chính thức</p>
                        </div>
                        @if($hotelInfo?->phone)
                            <a href="tel:{{ $hotelInfo->phone }}" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-telephone-outbound me-2"></i>Gọi ngay
                            </a>
                        @endif
                    </div>

                    <div class="row g-3">
                        @if($hotelInfo?->address)
                        <div class="col-12">
                            <div class="lh-contact-tile">
                                <div class="lh-contact-icon"><i class="bi bi-geo-alt"></i></div>
                                <div>
                                    <div class="text-uppercase small fw-bold text-secondary mb-1" style="letter-spacing:0.06em;">Địa chỉ</div>
                                    <div class="text-dark">{{ $hotelInfo->address }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($hotelInfo?->phone)
                        <div class="col-md-6">
                            <div class="lh-contact-tile">
                                <div class="lh-contact-icon"><i class="bi bi-telephone"></i></div>
                                <div>
                                    <div class="text-uppercase small fw-bold text-secondary mb-1" style="letter-spacing:0.06em;">Điện thoại</div>
                                    <a href="tel:{{ $hotelInfo->phone }}">{{ $hotelInfo->phone }}</a>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($hotelInfo?->email)
                        <div class="col-md-6">
                            <div class="lh-contact-tile">
                                <div class="lh-contact-icon"><i class="bi bi-envelope"></i></div>
                                <div>
                                    <div class="text-uppercase small fw-bold text-secondary mb-1" style="letter-spacing:0.06em;">Email</div>
                                    <a href="mailto:{{ $hotelInfo->email }}">{{ $hotelInfo->email }}</a>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="lh-contact-hours mt-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-clock-history text-primary fs-5"></i>
                            <span class="fw-bold text-dark">Giờ làm việc lễ tân</span>
                        </div>
                        <div class="row g-2 small">
                            <div class="col-sm-6 text-secondary">Thứ Hai — Chủ Nhật</div>
                            <div class="col-sm-6 fw-semibold text-dark text-sm-end">06:00 — 23:00</div>
                            <div class="col-12 text-secondary mt-1">Hotline có thể hỗ trợ ngoài giờ cho trường hợp khẩn, tùy tình trạng phòng.</div>
                        </div>
                    </div>

                    @if(!$hotelInfo?->address && !$hotelInfo?->phone && !$hotelInfo?->email)
                    <p class="mb-0 text-secondary mt-4">Thông tin chi tiết sẽ được cập nhật sớm. Bạn có thể xem <a href="{{ route('pages.help') }}">Trợ giúp</a> hoặc <a href="{{ route('home') }}">quay lại trang chủ</a>.</p>
                    @else
                    <hr class="my-4 opacity-25">
                    <p class="small text-secondary mb-4 mb-lg-0">
                        Cần hướng dẫn đặt phòng? Xem <a href="{{ route('pages.help') }}" class="fw-semibold text-decoration-none">Trợ giúp</a> hoặc <a href="{{ route('pages.policy') }}" class="fw-semibold text-decoration-none">Chính sách</a>.
                    </p>
                    @endif

                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <a href="{{ route('pages.help') }}" class="lh-contact-quick-link w-100 h-100">
                                <i class="bi bi-question-circle"></i>
                                <span>Trợ giúp đặt phòng</span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('home') }}#rooms-section" class="lh-contact-quick-link w-100 h-100">
                                <i class="bi bi-building"></i>
                                <span>Xem loại phòng</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="lh-glass-card overflow-hidden h-100 d-flex flex-column">
                    @php
                        $lat = $hotelInfo?->latitude ?? 16.0544;
                        $lng = $hotelInfo?->longitude ?? 108.2022;
                        $mapEmbed = "https://www.google.com/maps?q={$lat},{$lng}&hl=vi&z=16&output=embed";
                        $mapLink = "https://www.google.com/maps?q={$lat},{$lng}";
                    @endphp
                    <div class="ratio ratio-4x3 flex-grow-1" style="min-height: 220px;">
                        <iframe
                            src="{{ $mapEmbed }}"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Bản đồ {{ $hotelInfo?->name ?? 'Light Hotel' }}">
                        </iframe>
                    </div>
                    <div class="p-4 bg-light border-top">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-pin-map text-primary fs-5"></i>
                            <span class="fw-bold text-dark">Vị trí</span>
                        </div>
                        <p class="small text-secondary mb-3">{{ $hotelInfo?->address ?? 'Đà Nẵng, Việt Nam' }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ $mapLink }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm rounded-pill px-4">
                                Mở Google Maps <i class="bi bi-box-arrow-up-right ms-1"></i>
                            </a>
                            @if($hotelInfo?->email)
                            <a href="mailto:{{ $hotelInfo->email }}?subject=Yêu cầu từ website" class="btn btn-outline-primary btn-sm rounded-pill px-3">Gửi email</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
