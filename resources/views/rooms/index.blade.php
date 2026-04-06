@extends('layouts.app')

@section('title', $hotel?->name ?? 'Danh sách phòng')

@section('content')

{{-- ============================
     HERO SECTION
     ============================ --}}
<section class="lh-hero mb-0">
    <div class="lh-hero-mesh" aria-hidden="true"></div>
    <div class="container lh-hero-inner">
        <div class="row align-items-start align-items-lg-center g-4 lh-hero-top-row">
            <div class="col-12 col-lg-8 lh-hero-copy">
                <p class="lh-hero-eyebrow">Đà Nẵng · {{ $hotel?->name ?? 'Light Hotel' }}</p>
                <h1 class="lh-hero-title">
                    Đặt phòng thẳng,<br>
                    <em>nhận ưu đãi tốt nhất.</em>
                </h1>
                <p class="lh-hero-sub">Không qua trung gian · Giá tốt nhất · Hủy linh hoạt theo chính sách</p>
                <div class="lh-hero-feature-row">
                    <span class="lh-hero-pill"><i class="bi bi-wifi"></i> Wi‑Fi tốc độ cao</span>
                    <span class="lh-hero-pill"><i class="bi bi-shield-check"></i> Thanh toán an toàn</span>
                    <span class="lh-hero-pill"><i class="bi bi-headset"></i> Hỗ trợ 24/7</span>
                </div>
            </div>
            <div class="col-12 col-lg-4 d-flex justify-content-lg-end lh-hero-rating-wrap">
                <div class="lh-rating-chip mt-3 mt-lg-0">
                    <div class="lh-rating-score">{{ number_format($hotel?->rating_avg ?? 4.8, 1) }}</div>
                    <div class="lh-rating-label"><span class="text-warning">★★★★★</span><br><small>Tuyệt vời</small></div>
                </div>
            </div>
        </div>

        {{-- ============================
             SEARCH BAR (Booking.com style)
             ============================ --}}
        <form method="GET" action="{{ route('rooms.search') }}" id="search-form" novalidate class="mt-5" onsubmit="return validateSearchForm(this)">
            <input type="hidden" name="search" value="1">
            <input type="hidden" name="adults" value="{{ request('adults', 1) }}">
            <input type="hidden" name="children" value="{{ request('children', 0) }}">
            @if(request('child_ages'))
                @foreach(request('child_ages') as $age)
                    <input type="hidden" name="child_ages[]" value="{{ $age }}">
                @endforeach
            @endif

            <div class="bk-search-stack">
                <div class="bk-search-bar bk-search-bar--in-stack">
                    {{-- Destination --}}
                    <div class="bk-seg bk-seg-dest">
                        <i class="bi bi-building bk-seg-icon"></i>
                        <div class="bk-seg-content">
                            <div class="bk-seg-label">Điểm đến</div>
                            <input type="text" class="bk-input" value="Light Hotel Đà Nẵng" readonly style="cursor: default;">
                        </div>
                    </div>
                    <div class="bk-sep"></div>
                    {{-- Dates --}}
                    <div class="bk-seg bk-seg-dates">
                        <i class="bi bi-calendar-event bk-seg-icon"></i>
                        <div class="bk-seg-content">
                            <div class="bk-seg-label">Nhận phòng - Trả phòng</div>
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" name="check_in" id="check_in_input" class="bk-date-input"
                                       value="{{ request('check_in', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}">
                                <span class="text-muted">→</span>
                                <input type="date" name="check_out" id="check_out_input" class="bk-date-input"
                                       value="{{ request('check_out', date('Y-m-d', strtotime('+1 day'))) }}"
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                            </div>
                        </div>
                    </div>
                    <div class="bk-sep"></div>
                    {{-- Số phòng --}}
                    <div class="bk-seg">
                        <i class="bi bi-door-open bk-seg-icon"></i>
                        <div class="bk-seg-content">
                            <div class="bk-seg-label">Số phòng</div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="guest-btn" onclick="changeRooms(-1)" id="rooms-minus-btn">−</button>
                                <input type="number" name="rooms" id="rooms-input"
                                       value="{{ request('rooms', 1) }}" min="1" max="20"
                                       class="guest-count-input" readonly
                                       style="width: 40px; text-align: center; border: none; background: transparent; font-weight: 600; font-size: 1rem;">
                                <button type="button" class="guest-btn" onclick="changeRooms(1)" id="rooms-plus-btn">+</button>
                            </div>
                        </div>
                    </div>
                    {{-- Submit --}}
                    <button type="submit" class="bk-search-btn">
                        Tìm
                    </button>
                </div>

                {{-- Bộ lọc (cùng form — chỉ gửi bằng nút Tìm) --}}
                <div class="bk-filter-row">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label small text-muted mb-1">Loại phòng</label>
                            <select name="room_type" class="form-select form-select-sm">
                                <option value="">Tất cả loại phòng</option>
                                @foreach($allRoomTypes as $rt)
                                    <option value="{{ $rt->id }}" {{ request('room_type') == $rt->id ? 'selected' : '' }}>
                                        {{ $rt->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label small text-muted mb-1">Khoảng giá</label>
                            <select id="price_range_select" class="form-select form-select-sm" onchange="applyPriceRange(this)">
                                <option value="">Tất cả mức giá</option>
                                <option value="0-500000" {{ request('min_price') == 0 && request('max_price') == 500000 ? 'selected' : '' }}>Dưới 500.000đ</option>
                                <option value="500000-1000000" {{ request('min_price') == 500000 && request('max_price') == 1000000 ? 'selected' : '' }}>500.000đ - 1.000.000đ</option>
                                <option value="1000000-2000000" {{ request('min_price') == 1000000 && request('max_price') == 2000000 ? 'selected' : '' }}>1.000.000đ - 2.000.000đ</option>
                                <option value="2000000-" {{ request('min_price') == 2000000 && !request('max_price') ? 'selected' : '' }}>Trên 2.000.000đ</option>
                            </select>
                            <input type="hidden" name="min_price" id="min_price_input" value="{{ request('min_price') }}">
                            <input type="hidden" name="max_price" id="max_price_input" value="{{ request('max_price') }}">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label small text-muted mb-1">Sắp xếp theo</label>
                            <select name="sort_by" class="form-select form-select-sm">
                                <option value="price_asc" {{ request('sort_by', 'price_asc') == 'price_asc' ? 'selected' : '' }}>Giá thấp → cao</option>
                                <option value="price_desc" {{ request('sort_by') == 'price_desc' ? 'selected' : '' }}>Giá cao → thấp</option>
                                <option value="name_asc" {{ request('sort_by') == 'name_asc' ? 'selected' : '' }}>Tên A → Z</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label small text-muted mb-1">Tiện nghi</label>
                            <div class="dropdown">
                                <button class="btn btn-sm bk-amenities-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="amenities-filter-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-haspopup="true" aria-controls="amenities-filter-menu">
                                    <span id="amenities-text">Chọn tiện nghi</span>
                                    <i class="bi bi-chevron-down small"></i>
                                </button>
                                <div class="dropdown-menu bk-amenities-menu p-3 shadow" id="amenities-filter-menu" role="menu" aria-labelledby="amenities-filter-toggle" onclick="event.stopPropagation()">
                                    @forelse($amenities as $amenity)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="amenities[]"
                                                   value="{{ $amenity->id }}" id="am_{{ $amenity->id }}"
                                                   {{ in_array($amenity->id, (array)request('amenities')) ? 'checked' : '' }}
                                                   onchange="updateAmenitiesText()">
                                            <label class="form-check-label small" for="am_{{ $amenity->id }}">
                                                {{ $amenity->name }}
                                            </label>
                                        </div>
                                    @empty
                                        <p class="small text-muted mb-0">Chưa có danh mục tiện nghi trong hệ thống.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-1 col-md-12">
                            <label class="form-label small text-muted mb-1 d-none d-lg-block">&nbsp;</label>
                            <a href="{{ route('home') }}" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-x-lg"></i> Xóa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

{{-- ============================
     CONTENT
     ============================ --}}
<div class="lh-home-content-wrap">
@php
    $hName = $hotel?->name ?? 'Light Hotel';
@endphp
<div class="container lh-home-landing-container pt-4 pb-1" id="home-landing">
    <div class="lh-stat-strip">
        <div class="row g-2 g-md-0 align-items-center">
            <div class="col-6 col-md-3 lh-stat-item">
                <div class="lh-stat-value">{{ number_format($hotel?->rating_avg ?? 4.8, 1) }}<span class="text-warning fs-5">★</span></div>
                <div class="lh-stat-label">Điểm đánh giá</div>
            </div>
            <div class="col-6 col-md-3 lh-stat-item">
                <div class="lh-stat-value">{{ $roomTypesList->total() > 0 ? $roomTypesList->total() . '+' : '—' }}</div>
                <div class="lh-stat-label">Lựa chọn phòng</div>
            </div>
            <div class="col-6 col-md-3 lh-stat-item">
                <div class="lh-stat-value">24/7</div>
                <div class="lh-stat-label">Lễ tân &amp; hỗ trợ</div>
            </div>
            <div class="col-6 col-md-3 lh-stat-item">
                <div class="lh-stat-value">ĐN</div>
                <div class="lh-stat-label">Trung tâm Đà Nẵng</div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-center lh-split-section" id="experience">
        <div class="col-lg-6 order-lg-2">
            <div class="lh-split-img">
                <img src="https://images.pexels.com/photos/189296/pexels-photo-189296.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1200" alt="Không gian tiếp đón khách sạn" loading="lazy" width="800" height="600">
            </div>
        </div>
        <div class="col-lg-6 order-lg-1 lh-split-copy">
            <p class="lh-eyebrow">Trải nghiệm</p>
            <h2 class="h3 mb-0">Nghỉ dưỡng hiện đại, phục vụ chuẩn khách sạn</h2>
            <p class="mt-3 mb-0">{{ $hName }} kết hợp thiết kế tối giản, ánh sáng tự nhiên và dịch vụ tận tâm — lý tưởng cho công tác, gia đình hay kỳ nghỉ ngắn ngày tại Đà Nẵng.</p>
            <ul class="lh-check-list mt-3">
                <li><i class="bi bi-check-circle-fill"></i> Đặt phòng trực tiếp trên website, minh bạch giá và điều kiện.</li>
                <li><i class="bi bi-check-circle-fill"></i> Wi‑Fi tốc độ cao, không gian làm việc và thư giãn riêng.</li>
                <li><i class="bi bi-check-circle-fill"></i> Gần biển và trục du lịch — thuận tiện di chuyển.</li>
            </ul>
            <a href="#rooms-section" class="btn btn-primary rounded-pill mt-3">Xem các loại phòng</a>
        </div>
    </div>

    <div class="lh-section-block" id="services">
        <div class="lh-section-block-title">
            <p class="lh-eyebrow">Tiện ích</p>
            <h2 class="h3">Mọi thứ bạn cần cho kỳ lưu trú</h2>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <article class="lh-service-card">
                    <div class="lh-service-card-img-wrap">
                        <img src="https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=800" alt="" loading="lazy" width="400" height="160" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22160%22%3E%3Crect fill=%22%23e2e8f0%22 width=%22100%25%22 height=%22100%25%22/%3E%3C/svg%3E'">
                    </div>
                    <div class="lh-service-card-body">
                        <h3>Hồ bơi &amp; thư giãn</h3>
                        <p>Không gian xanh và khu vực hồ bơi — nơi lý tưởng để nạp lại năng lượng sau ngày dài.</p>
                    </div>
                </article>
            </div>
            <div class="col-md-6 col-xl-3">
                <article class="lh-service-card">
                    <div class="lh-service-card-img-wrap">
                        <img src="https://images.pexels.com/photos/696218/pexels-photo-696218.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=800" alt="" loading="lazy" width="400" height="160" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22160%22%3E%3Crect fill=%22%23e2e8f0%22 width=%22100%25%22 height=%22100%25%22/%3E%3C/svg%3E'">
                    </div>
                    <div class="lh-service-card-body">
                        <h3>Ẩm thực &amp; đồ uống</h3>
                        <p>Menu đa dạng, từ bữa sáng nhẹ đến bữa tối — phù hợp khẩu vị địa phương và quốc tế.</p>
                    </div>
                </article>
            </div>
            <div class="col-md-6 col-xl-3">
                <article class="lh-service-card">
                    <div class="lh-service-card-img-wrap">
                        <img src="https://images.pexels.com/photos/6620854/pexels-photo-6620854.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=800" alt="" loading="lazy" width="400" height="160" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22160%22%3E%3Crect fill=%22%23e2e8f0%22 width=%22100%25%22 height=%22100%25%22/%3E%3C/svg%3E'">
                    </div>
                    <div class="lh-service-card-body">
                        <h3>Spa &amp; chăm sóc</h3>
                        <p>Gói massage và liệu trình thư giãn có thể đặt thêm tại lễ tân.</p>
                    </div>
                </article>
            </div>
            <div class="col-md-6 col-xl-3">
                <article class="lh-service-card">
                    <div class="lh-service-card-img-wrap">
                        <img src="https://images.pexels.com/photos/271643/pexels-photo-271643.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=800" alt="" loading="lazy" width="400" height="160" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22160%22%3E%3Crect fill=%22%23e2e8f0%22 width=%22100%25%22 height=%22100%25%22/%3E%3C/svg%3E'">
                    </div>
                    <div class="lh-service-card-body">
                        <h3>Đưa đón &amp; hành lý</h3>
                        <p>Hỗ trợ gọi xe, chỉ đường và bảo quản hành lý theo nhu cầu của bạn.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <div class="lh-section-block">
        <div class="lh-section-block-title">
            <p class="lh-eyebrow">Vì sao đặt trực tiếp</p>
            <h2 class="h3">Ưu đãi và quyền lợi khi đặt với chúng tôi</h2>
        </div>
        <div class="row g-3">
            <div class="col-md-4 d-flex">
                <div class="lh-why-card w-100">
                    <div class="lh-why-icon"><i class="bi bi-tag"></i></div>
                    <h3>Giá và gói linh hoạt</h3>
                    <p>Xem rõ điều kiện hủy, phụ thu và thanh toán ngay trên từng bước đặt phòng.</p>
                </div>
            </div>
            <div class="col-md-4 d-flex">
                <div class="lh-why-card w-100">
                    <div class="lh-why-icon"><i class="bi bi-shield-lock"></i></div>
                    <h3>Thanh toán bảo mật</h3>
                    <p>Kênh thanh toán được mã hóa; thông tin cá nhân chỉ phục vụ xác nhận đơn.</p>
                </div>
            </div>
            <div class="col-md-4 d-flex">
                <div class="lh-why-card w-100">
                    <div class="lh-why-icon"><i class="bi bi-headset"></i></div>
                    <h3>Hỗ trợ rõ ràng</h3>
                    <p>Đội ngũ lễ tân sẵn sàng tư vấn loại phòng, lịch trình và dịch vụ đi kèm.</p>
                </div>
            </div>
        </div>
    </div>

    <section class="lh-section-block" id="gallery" aria-label="Ảnh các loại phòng">
        <h2 class="visually-hidden">Hình ảnh phòng</h2>
        <div class="row g-2 g-md-3">
            @foreach($allRoomTypes as $gType)
                @php
                    $gImg = null;
                    foreach ($gType->rooms as $gr) {
                        $gUrls = $gr->getDisplayImageUrls();
                        if (! empty($gUrls)) {
                            $gImg = $gUrls[0];
                            break;
                        }
                    }
                    $gImg = $gImg ?? $gType->image_url;
                    $gLabel = htmlspecialchars(\Illuminate\Support\Str::limit($gType->name, 14), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $gFallback = 'data:image/svg+xml,'.rawurlencode(
                        '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400">'
                        .'<rect fill="#e2e8f0" width="100%" height="100%"/>'
                        .'<text fill="#475569" font-family="system-ui,sans-serif" font-size="14" x="50%" y="50%" text-anchor="middle" dominant-baseline="middle">'
                        .$gLabel.'</text></svg>'
                    );
                    $gImg = $gImg ?? $gFallback;
                @endphp
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ route('rooms.search', ['room_type' => $gType->id, 'check_in' => date('Y-m-d'), 'check_out' => date('Y-m-d', strtotime('+1 day'))]) }}" class="lh-room-photo-tile">
                        <div class="lh-room-visual-media">
                            <img src="{{ $gImg }}" alt="{{ $gType->name }}" loading="lazy" decoding="async" width="400" height="400" onerror="this.onerror=null;this.src={!! json_encode($gFallback) !!}">
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    <div class="lh-section-block">
        <div class="lh-section-block-title">
            <p class="lh-eyebrow">Khách hàng nói gì</p>
            <h2 class="h3">Phản hồi từ lưu trú gần đây</h2>
        </div>
        <div class="row g-3 align-items-stretch">
            <div class="col-md-4 d-flex">
                <div class="lh-testimonial-card w-100">
                    <p class="lh-testimonial-quote">“Phòng sạch, nhân viên lễ tân nhiệt tình. Quy trình đặt online nhanh, nhận phòng gọn.”</p>
                    <div class="lh-testimonial-meta">
                        <img class="lh-testimonial-avatar" src="https://images.pexels.com/photos/774909/pexels-photo-774909.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=200" alt="" loading="lazy" width="44" height="44">
                        <div>
                            <div class="lh-testimonial-name">Minh Anh</div>
                            <div class="lh-testimonial-role">Du lịch kết hợp công tác</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 d-flex">
                <div class="lh-testimonial-card w-100">
                    <p class="lh-testimonial-quote">“Gia đình 4 người hài lòng với không gian và vị trí. Sẽ quay lại dịp hè.”</p>
                    <div class="lh-testimonial-meta">
                        <img class="lh-testimonial-avatar" src="https://images.pexels.com/photos/1222271/pexels-photo-1222271.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=200" alt="" loading="lazy" width="44" height="44">
                        <div>
                            <div class="lh-testimonial-name">Tuấn Đức</div>
                            <div class="lh-testimonial-role">Nghỉ cùng gia đình</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 d-flex">
                <div class="lh-testimonial-card w-100">
                    <p class="lh-testimonial-quote">“Giá hiển thị đúng như khi thanh toán. Hỗ trợ đổi ngày linh hoạt qua hotline.”</p>
                    <div class="lh-testimonial-meta">
                        <img class="lh-testimonial-avatar" src="https://images.pexels.com/photos/91227/pexels-photo-91227.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=200" alt="" loading="lazy" width="44" height="44">
                        <div>
                            <div class="lh-testimonial-name">Sarah L.</div>
                            <div class="lh-testimonial-role">Khách quốc tế</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="lh-cta-band">
        <div class="lh-cta-band-inner row align-items-center g-3">
            <div class="col-lg-8">
                <h3 class="h4 mb-2">Sẵn sàng chọn phòng?</h3>
                <p class="mb-0">Chọn ngày trong thanh tìm kiếm phía trên, lọc theo loại phòng và tiện nghi — bấm vào ảnh phòng bên dưới để đặt nhanh.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="#rooms-section" class="btn btn-light rounded-pill px-4">Xem danh sách phòng</a>
            </div>
        </div>
    </div>
</div>
<div class="container py-5" id="rooms-section">

    @if(request('search'))

        <div class="row g-4">
            {{-- ========= SIDEBAR FILTERS ========= --}}
            <div class="col-lg-3">

                {{-- Quick nav back --}}
                <div class="mb-3">
                    <a href="{{ route('home') }}" class="text-muted small text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>Trang chủ
                    </a>
                </div>

                <div class="lh-filter-card">
                    <div class="lh-filter-header">
                        <i class="bi bi-sliders me-2"></i>Lọc tìm kiếm
                    </div>

                    <form method="GET" action="{{ route('home') }}" id="filter-form">
                        <input type="hidden" name="search" value="1">
                        <input type="hidden" name="check_in"  value="{{ request('check_in') }}">
                        <input type="hidden" name="check_out" value="{{ request('check_out') }}">
                        <input type="hidden" name="adults"    value="{{ request('adults', 1) }}">
                        <input type="hidden" name="children"  value="{{ request('children', 0) }}">
                        <input type="hidden" name="rooms"     value="{{ request('rooms', 1) }}">

                        {{-- Room type --}}
                        @if(isset($roomTypes) && count($roomTypes))
                        <div class="lh-filter-group">
                            <div class="lh-filter-group-title">Loại phòng</div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="room_type" value="" id="rt_all"
                                       {{ !request('room_type') ? 'checked' : '' }}>
                                <label class="form-check-label" for="rt_all">Tất cả loại phòng</label>
                            </div>
                            @foreach($allRoomTypes as $rt)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="room_type"
                                       value="{{ $rt->id }}" id="rt_{{ $rt->id }}"
                                       {{ request('room_type') == $rt->id ? 'checked' : '' }}>
                                <label class="form-check-label" for="rt_{{ $rt->id }}">{{ $rt->name }}</label>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Price range --}}
                        <div class="lh-filter-group">
                            <div class="lh-filter-group-title">Khoảng giá (VNĐ/đêm)</div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted">Từ</label>
                                    <input type="number" name="min_price" class="form-control form-control-sm"
                                           placeholder="0" value="{{ request('min_price') }}" min="0" step="100000">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">Đến</label>
                                    <input type="number" name="max_price" class="form-control form-control-sm"
                                           placeholder="∞" value="{{ request('max_price') }}" min="0" step="100000">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                                <i class="bi bi-funnel me-1"></i>Áp dụng bộ lọc
                            </button>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle me-1"></i>Xóa tất cả
                            </a>
                        </div>
                    </form>
                </div>

                {{-- Trust badges --}}
                <div class="lh-trust-card mt-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-shield-check text-success fs-5"></i>
                        <span class="small fw-semibold">Đặt phòng an toàn</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-arrow-counterclockwise text-primary fs-5"></i>
                        <span class="small fw-semibold">Hủy miễn phí</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-credit-card text-warning fs-5"></i>
                        <span class="small fw-semibold">Thanh toán linh hoạt</span>
                    </div>
                </div>

                {{-- CTA Button --}}
                <div class="text-center mt-4">
                    <a href="{{ route('home') }}#booking-form" class="btn btn-primary btn-lg px-4 py-3 rounded-pill">
                        <i class="bi bi-calendar-plus me-2"></i>
                        Đặt phòng ngay
                    </a>
                </div>
            </div>

            {{-- ========= RESULTS ========= --}}
            <div class="col-lg-9">
                <div class="lh-section-header">
                    <div>
                        <h2 class="h5 mb-1 fw-bold text-dark">
                            {{ $roomTypesList->total() }} loại phòng hiện có
                            @if(request('check_in') && request('check_out'))
                                <span class="text-muted fw-normal fs-6">·
                                    {{ \Carbon\Carbon::parse(request('check_in'))->format('d/m') }} –
                                    {{ \Carbon\Carbon::parse(request('check_out'))->format('d/m/Y') }}
                                </span>
                            @endif
                        </h2>
                        <p class="text-muted small mb-0">Sắp xếp theo: Giá thấp nhất</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @if(request('room_type'))
                            <span class="badge lh-badge-active">
                                Loại phòng đã chọn <a href="{{ route('home', array_merge(request()->except('room_type'), ['search'=>1])) }}" class="ms-1 text-white text-decoration-none">×</a>
                            </span>
                        @endif
                        @if(request('min_price') || request('max_price'))
                            <span class="badge lh-badge-active">
                                Lọc giá <a href="{{ route('home', array_merge(request()->except(['min_price','max_price']), ['search'=>1])) }}" class="ms-1 text-white text-decoration-none">×</a>
                            </span>
                        @endif
                    </div>
                </div>

                @php
                    $placeholderSvg = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22250%22 viewBox=%220 0 400 250%22%3E%3Crect fill=%22%231e293b%22 width=%22400%22 height=%22250%22/%3E%3Ctext fill=%22%2394a3b8%22 font-size=%2218%22 x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22%3ELight Hotel%3C/text%3E%3C/svg%3E";
                @endphp

                <div class="d-flex flex-column gap-3">
                    @forelse($roomTypesList as $type)
                        <div class="lh-result-card">
                            <div class="row g-0 h-100">
                                <div class="col-md-4 col-sm-5">
                                    <div class="lh-result-img-wrap" style="height: 200px;">
                                        @php
                                            $searchListImg = null;
                                            foreach ($type->rooms as $listRoom) {
                                                foreach ($listRoom->images as $lim) {
                                                    if ($lim->image_url) {
                                                        $searchListImg = \App\Models\Room::resolveImageUrl($lim->image_url);
                                                        break 2;
                                                    }
                                                }
                                            }
                                            $searchListImg = $searchListImg ?? $type->image_url;
                                            $searchListLabel = htmlspecialchars(\Illuminate\Support\Str::limit($type->name, 20), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                                            $searchListFallback = 'data:image/svg+xml,'.rawurlencode(
                                                '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="260">'
                                                .'<rect fill="#e2e8f0" width="100%" height="100%"/>'
                                                .'<text fill="#475569" font-family="system-ui,sans-serif" font-size="14" x="50%" y="50%" text-anchor="middle" dominant-baseline="middle">'
                                                .$searchListLabel.'</text></svg>'
                                            );
                                            $searchListImg = $searchListImg ?? $searchListFallback;
                                        @endphp
                                        <img src="{{ $searchListImg }}"
                                             class="lh-result-img w-100 h-100" style="object-fit: cover;"
                                             alt="{{ $type->name }}"
                                             onerror="this.onerror=null;this.src={!! json_encode($searchListFallback) !!}">
                                    </div>
                                </div>
                                <div class="col-md-8 col-sm-7 d-flex flex-column p-3">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="fw-bold mb-1">{{ $type->name }}</h5>
                                        <div class="text-primary fw-bold">{{ number_format($type->price, 0, ',', '.') }} VNĐ</div>
                                    </div>
                                    <p class="text-muted small mb-2">{{ Str::limit($type->description, 100) }}</p>
                                    <div class="d-flex gap-2 mb-3">
                                        <span class="badge bg-light text-dark border"><i class="bi bi-people me-1"></i>{{ $type->adult_capacity }} NL, {{ $type->child_capacity }} TE</span>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-aspect-ratio me-1"></i>{{ $type->area ?? 30 }} m²</span>
                                    </div>
                                    <div class="mt-auto d-flex justify-content-end">
                                        <a href="{{ route('rooms.search', ['room_type' => $type->id, 'check_in' => date('Y-m-d'), 'check_out' => date('Y-m-d', strtotime('+1 day'))]) }}" class="btn btn-warning btn-sm fw-bold px-4 rounded-pill">
                                            Chọn phòng
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">Chưa có phòng nào phù hợp.</div>
                    @endforelse
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    {{ $roomTypesList->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>

    @else

        {{-- ============================
             HOME: Room Grid
             ============================ --}}
        <div class="lh-section-header">
            <div>
                <p class="lh-section-eyebrow mb-1">Bộ sưu tập phòng</p>
                <h2 class="h4 fw-bold mb-0 text-dark">Chọn không gian lý tưởng cho bạn</h2>
            </div>
            <div class="badge rounded-pill px-3 py-2 bg-white text-primary border border-primary border-opacity-25 shadow-sm fw-semibold">
                {{ $roomTypesList->total() }} loại phòng
            </div>
        </div>

        @php
            $placeholderSvg = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22 viewBox=%220 0 400 300%22%3E%3Crect fill=%22%23e2e8f0%22 width=%22400%22 height=%22300%22/%3E%3C/svg%3E";
        @endphp

        <div class="row g-3 align-items-stretch">
            @forelse($roomTypesList as $type)
                @php
                    $imageUrl = null;
                    foreach ($type->rooms as $hr) {
                        $hUrls = $hr->getDisplayImageUrls();
                        if (! empty($hUrls)) {
                            $imageUrl = $hUrls[0];
                            break;
                        }
                    }
                    $imageUrl = $imageUrl ?? $type->image_url;
                    $tileLabel = htmlspecialchars(\Illuminate\Support\Str::limit($type->name, 18), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $tileFallback = 'data:image/svg+xml,'.rawurlencode(
                        '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">'
                        .'<rect fill="#e2e8f0" width="100%" height="100%"/>'
                        .'<text fill="#475569" font-family="system-ui,sans-serif" font-size="14" x="50%" y="50%" text-anchor="middle" dominant-baseline="middle">'
                        .$tileLabel.'</text></svg>'
                    );
                    $imageUrl = $imageUrl ?? $tileFallback;
                @endphp
                <div class="col-6 col-lg-4 col-xl-3 d-flex">
                    <a href="{{ route('rooms.search', ['room_type' => $type->id, 'check_in' => date('Y-m-d'), 'check_out' => date('Y-m-d', strtotime('+1 day'))]) }}" class="lh-room-visual-tile w-100">
                        <div class="lh-room-visual-media">
                            <img src="{{ $imageUrl }}"
                                 alt="{{ $type->name }}"
                                 loading="lazy"
                                 decoding="async"
                                 onerror="this.onerror=null;this.src={!! json_encode($tileFallback) !!}">
                        </div>
                    </a>
                </div>
            @empty
                <p class="text-center text-muted py-5 col-12 mb-0">Hiện chưa có loại phòng nào.</p>
            @endforelse
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $roomTypesList->links('pagination::bootstrap-5') }}
        </div>

    @endif
</div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // check_in → update check_out min
    var ci = document.getElementById('check_in_input');
    var co = document.getElementById('check_out_input');
    if (ci && co) {
        ci.addEventListener('change', function() {
            co.min = ci.value;
            if (co.value && co.value <= ci.value) {
                var d = new Date(ci.value + 'T00:00:00');
                d.setDate(d.getDate() + 1);
                co.value = d.toISOString().split('T')[0];
            }
        });
    }

    // Khởi tạo nút +/- phòng
    updateRoomsBtnState();
    updateAmenitiesText();
});

function changeRooms(delta) {
    var input = document.getElementById('rooms-input');
    if (!input) return;
    var val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > 20) val = 20;
    input.value = val;
    updateRoomsBtnState();
}

function updateRoomsBtnState() {
    var input = document.getElementById('rooms-input');
    if (!input) return;
    var val = parseInt(input.value);
    var minusBtn = document.getElementById('rooms-minus-btn');
    var plusBtn  = document.getElementById('rooms-plus-btn');
    if (minusBtn) minusBtn.disabled = (val <= 1);
    if (plusBtn)  plusBtn.disabled  = (val >= 20);
}

function updateGuestCount(type, delta) {
    var input = document.getElementById('guest-' + type);
    if (!input) return;
    var min = parseInt(input.min);
    var max = parseInt(input.max);
    var val = parseInt(input.value) + delta;
    if (val < min) val = min;
    if (val > max) val = max;
    input.value = val;
    document.getElementById(type + '-minus').disabled = (val <= min);
    document.getElementById(type + '-plus').disabled  = (val >= max);

    // Update child age dropdowns if children count changed
    if (type === 'children') {
        updateChildAgeDropdowns(val);
    }

    updateGuestSummary();
}

function updateChildAgeDropdowns(count) {
    var container = document.getElementById('child-ages-container');
    var list = document.getElementById('child-ages-list');

    if (count === 0) {
        container.style.display = 'none';
        list.innerHTML = '';
        return;
    }

    container.style.display = 'block';
    var currentDropdowns = list.querySelectorAll('.child-age-row').length;

    // Add new dropdowns if needed
    for (var i = currentDropdowns; i < count; i++) {
        var row = document.createElement('div');
        row.className = 'child-age-row';
        row.style.cssText = 'width: 100%;';
        row.innerHTML =
            '<select name="child_ages[]" class="form-select form-select-sm w-100" style="border: 1px solid #dc3545; padding-top: 4px; padding-bottom: 4px; font-size: 0.8rem;" onchange="updateGuestSummary()">' +
            '<option value="">Độ tuổi của trẻ ' + (i + 1) + '</option>' +
            '<option value="0">Dưới 1 tuổi</option>' +
            '<option value="1">1 tuổi</option>' +
            '<option value="2">2 tuổi</option>' +
            '<option value="3">3 tuổi</option>' +
            '<option value="4">4 tuổi</option>' +
            '<option value="5">5 tuổi</option>' +
            '<option value="6">6 tuổi</option>' +
            '<option value="7">7 tuổi</option>' +
            '<option value="8">8 tuổi</option>' +
            '<option value="9">9 tuổi</option>' +
            '<option value="10">10 tuổi</option>' +
            '<option value="11">11 tuổi</option>' +
            '<option value="12">12 tuổi</option>' +
            '<option value="13">13 tuổi</option>' +
            '<option value="14">14 tuổi</option>' +
            '<option value="15">15 tuổi</option>' +
            '<option value="16">16 tuổi</option>' +
            '<option value="17">17 tuổi</option>' +
            '</select>';
        list.appendChild(row);
    }

    // Remove extra dropdowns if needed
    while (list.querySelectorAll('.child-age-row').length > count) {
        list.removeChild(list.lastChild);
    }
}

function updateGuestSummary() {
    var adults   = document.getElementById('guest-adults').value;
    var children = document.getElementById('guest-children').value;
    var rooms    = document.getElementById('guest-rooms').value;
    var lbl = document.getElementById('guest-summary-label');
    if (lbl) lbl.textContent = adults + ' người lớn · ' + children + ' trẻ em · ' + rooms + ' phòng';
}

function resetGuests() {
    document.getElementById('guest-adults').value = 1;
    document.getElementById('guest-children').value = 0;
    document.getElementById('guest-rooms').value = 1;
    updateChildAgeDropdowns(0);
    updateGuestCount('adults', 0);
    updateGuestCount('children', 0);
    updateGuestCount('rooms', 0);
    updateGuestSummary();
}

function updateAmenitiesText() {
    var checkedAmenities = document.querySelectorAll('input[name="amenities[]"]:checked');
    var textElement = document.getElementById('amenities-text');

    if (checkedAmenities.length === 0) {
        textElement.textContent = 'Chọn tiện nghi';
    } else if (checkedAmenities.length === 1) {
        var label = document.querySelector('label[for="' + checkedAmenities[0].id + '"]').textContent;
        textElement.textContent = label;
    } else {
        textElement.textContent = checkedAmenities.length + ' tiện nghi đã chọn';
    }
}

function applyPriceRange(select) {
    var value = select.value;
    var minPriceInput = document.getElementById('min_price_input');
    var maxPriceInput = document.getElementById('max_price_input');

    // Clear current values
    minPriceInput.value = '';
    maxPriceInput.value = '';

    // Set new values based on selection
    if (value) {
        var parts = value.split('-');
        minPriceInput.value = parts[0] || '';
        maxPriceInput.value = parts[1] || '';
    }
}

function validateSearchForm(form) {
    var guestChildren = document.getElementById('guest-children');
    if (!guestChildren) return true;
    var children = parseInt(guestChildren.value, 10);
    if (children > 0) {
        var childAgeSelects = form.querySelectorAll('select[name="child_ages[]"]');
        for (var i = 0; i < childAgeSelects.length; i++) {
            if (!childAgeSelects[i].value) {
                alert('Vui lòng chọn độ tuổi cho tất cả trẻ em');
                childAgeSelects[i].focus();
                return false;
            }
        }
    }
    return true;
}
</script>
@endpush

@endsection
