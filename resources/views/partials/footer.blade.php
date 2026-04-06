<footer class="site-footer">
    <div class="footer-accent"></div>
    <div class="footer-main">
        <div class="container">
            <div class="row g-5 py-5">
                <div class="col-lg-4">
                    <div class="footer-brand d-flex align-items-center gap-3 mb-4">
                        <span class="footer-logo">
                            <img src="{{ asset('Thiết kế chưa có tên.png') }}" alt="Light Hotel logo">
                        </span>
                        <span class="footer-brand-name">{{ $hotelInfo?->name ?? 'Light Hotel' }}</span>
                    </div>
                    <p class="footer-desc mb-4">
                        {{ Str::limit($hotelInfo?->description ?? 'Trải nghiệm nghỉ dưỡng đẳng cấp với dịch vụ chuyên nghiệp và tiện nghi hiện đại.', 140) }}
                    </p>
                    <div class="footer-tagline">Đặt phòng trực tuyến &ndash; Nhanh chóng, an toàn</div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <h6 class="footer-heading">Điều hướng</h6>
                    <ul class="footer-links list-unstyled mb-0">
                        <li><a href="{{ route('home') }}">Trang chủ</a></li>
                        <li><a href="{{ route('home') }}#rooms-section">Phòng</a></li>
                        <li><a href="{{ route('pages.contact') }}">Liên hệ</a></li>
                        <li><a href="{{ route('pages.help') }}">Trợ giúp</a></li>
                        <li><a href="{{ route('pages.policy') }}">Chính sách</a></li>
                        <li><a href="{{ route('login') }}">Đăng nhập</a></li>
                        <li><a href="{{ route('register') }}">Đăng ký</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <h6 class="footer-heading">Liên hệ</h6>
                    <ul class="footer-contact list-unstyled mb-0">
                        @if($hotelInfo?->address)
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-geo-alt footer-icon"></i>
                            <span>{{ $hotelInfo->address }}</span>
                        </li>
                        @endif
                        @if($hotelInfo?->phone)
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-telephone footer-icon"></i>
                            <a href="tel:{{ $hotelInfo->phone }}">{{ $hotelInfo->phone }}</a>
                        </li>
                        @endif
                        @if($hotelInfo?->email)
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope footer-icon"></i>
                            <a href="mailto:{{ $hotelInfo->email }}">{{ $hotelInfo->email }}</a>
                        </li>
                        @endif
                        @if(!$hotelInfo?->address && !$hotelInfo?->phone && !$hotelInfo?->email)
                        <li class="footer-contact-empty">Chưa cập nhật thông tin liên hệ</li>
                        @endif
                    </ul>
                </div>
                <div class="col-12 col-lg-4">
                    <h6 class="footer-heading">Bản đồ</h6>
                    @php
                        $lat = $hotelInfo?->latitude ?? 10.762622;
                        $lng = $hotelInfo?->longitude ?? 106.660172;
                        $mapUrl = "https://www.google.com/maps?q={$lat},{$lng}&hl=vi&z=16&output=embed";
                    @endphp
                    <div class="footer-map rounded overflow-hidden">
                        <iframe
                            src="{{ $mapUrl }}"
                            width="100%"
                            height="180"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Bản đồ Light Hotel">
                        </iframe>
                    </div>
                    <a href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}" target="_blank" rel="noopener" class="footer-map-link small mt-2 d-inline-block">
                        Xem trên Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 py-4">
                <div class="footer-copy">
                    &copy; {{ date('Y') }} {{ $hotelInfo?->name ?? 'Light Hotel' }}. Bảo lưu mọi quyền.
                </div>
                <div class="d-flex gap-4">
                    <a href="{{ route('pages.policy') }}#privacy" class="footer-legal">Chính sách bảo mật</a>
                    <a href="{{ route('pages.policy') }}#terms" class="footer-legal">Điều khoản sử dụng</a>
                </div>
            </div>
        </div>
    </div>
</footer>
