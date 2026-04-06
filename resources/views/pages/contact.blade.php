@extends('layouts.app')

@section('title', 'Liên hệ — ' . ($hotelInfo?->name ?? 'Light Hotel'))

@section('content')
<div class="lh-breakout lh-page-hero mb-4">
    <div class="container lh-page-hero-inner">
        <nav class="lh-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span class="text-white opacity-50 mx-2">/</span>
            <span class="active">Liên hệ</span>
        </nav>
        <h1>Liên hệ</h1>
        <p class="lh-page-lead mt-2">Chúng tôi luôn sẵn sàng hỗ trợ bạn về đặt phòng, dịch vụ lưu trú và mọi thắc mắc.</p>
    </div>
</div>

<div class="lh-page-body">
    <div class="row g-4 align-items-stretch">
        <div class="col-lg-7">
            <div class="lh-glass-card p-4 p-lg-5 h-100">
                <h2 class="h5 fw-bold text-dark mb-4">Thông tin liên hệ</h2>
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

                @if(!$hotelInfo?->address && !$hotelInfo?->phone && !$hotelInfo?->email)
                <p class="mb-0 text-secondary">Thông tin chi tiết sẽ được cập nhật sớm. Bạn có thể xem <a href="{{ route('pages.help') }}">Trợ giúp</a> hoặc <a href="{{ route('home') }}">quay lại trang chủ</a>.</p>
                @else
                <hr class="my-4 opacity-25">
                <p class="small text-secondary mb-0">
                    Cần hướng dẫn đặt phòng? Xem <a href="{{ route('pages.help') }}" class="fw-semibold text-decoration-none">Trợ giúp</a> hoặc <a href="{{ route('pages.policy') }}" class="fw-semibold text-decoration-none">Chính sách</a>.
                </p>
                @endif
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
                    <a href="{{ $mapLink }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm rounded-pill px-4">
                        Mở Google Maps <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
