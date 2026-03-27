@extends('layouts.app')

@section('title', $hotel->name ?? 'Danh sách phòng')

@section('content')

{{-- ============================
     HERO SECTION
     ============================ --}}
<section class="lh-hero mb-0">
    <div class="lh-hero-bg"></div>
    <div class="container lh-hero-inner">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <p class="lh-hero-eyebrow">Đà Nẵng · Light Hotel</p>
                <h1 class="lh-hero-title">
                    Đặt phòng thẳng,<br>
                    <em>nhận ưu đãi tốt nhất.</em>
                </h1>
                <p class="lh-hero-sub">Không qua trung gian · Giá tốt nhất · Hủy miễn phí</p>
            </div>
            <div class="col-lg-4 d-none d-lg-flex justify-content-end">
                <div class="lh-rating-chip">
                    <div class="lh-rating-score">{{ number_format($hotel->rating_avg ?? 4.8, 1) }}</div>
                    <div class="lh-rating-label"><span class="text-warning">★★★★★</span><br><small>Tuyệt vời</small></div>
                </div>
            </div>
        </div>

        {{-- ============================
             SEARCH BAR (Booking.com style)
             ============================ --}}
        <form method="GET" action="{{ route('rooms.search') }}" id="search-form" novalidate class="mt-5" onsubmit="return validateSearchForm(this)">
            <input type="hidden" name="search" value="1">
            <div class="bk-search-bar">
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
        </form>
    </div>
</section>

{{-- ============================
     CONTENT
     ============================ --}}
<div class="container py-5" id="rooms-section">

    {{-- ============================
         QUICK FILTER BAR (moved down)
         ============================ --}}
    <section class="bg-white border rounded p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('rooms.search') }}" id="quick-filter-form">
            <input type="hidden" name="search" value="1">
            <input type="hidden" name="check_in" value="{{ request('check_in', date('Y-m-d')) }}">
            <input type="hidden" name="check_out" value="{{ request('check_out', date('Y-m-d', strtotime('+1 day'))) }}">
            <input type="hidden" name="adults" value="{{ request('adults', 1) }}">
            <input type="hidden" name="children" value="{{ request('children', 0) }}">
            <input type="hidden" name="rooms" value="{{ request('rooms', 1) }}">
            @if(request('child_ages'))
                @foreach(request('child_ages') as $age)
                    <input type="hidden" name="child_ages[]" value="{{ $age }}">
                @endforeach
            @endif

            <div class="row g-2 align-items-end">
                {{-- Room Type Filter --}}
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Loại phòng</label>
                    <select name="room_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tất cả loại phòng</option>
                        @foreach($allRoomTypes as $rt)
                            <option value="{{ $rt->id }}" {{ request('room_type') == $rt->id ? 'selected' : '' }}>
                                {{ $rt->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Price Range Filter --}}
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Khoảng giá</label>
                    <select name="price_range" class="form-select form-select-sm" onchange="applyPriceRange(this)">
                        <option value="">Tất cả mức giá</option>
                        <option value="0-500000" {{ request('min_price') == 0 && request('max_price') == 500000 ? 'selected' : '' }}>Dưới 500.000đ</option>
                        <option value="500000-1000000" {{ request('min_price') == 500000 && request('max_price') == 1000000 ? 'selected' : '' }}>500.000đ - 1.000.000đ</option>
                        <option value="1000000-2000000" {{ request('min_price') == 1000000 && request('max_price') == 2000000 ? 'selected' : '' }}>1.000.000đ - 2.000.000đ</option>
                        <option value="2000000-" {{ request('min_price') == 2000000 && !request('max_price') ? 'selected' : '' }}>Trên 2.000.000đ</option>
                    </select>
                    <input type="hidden" name="min_price" id="min_price_input" value="{{ request('min_price') }}">
                    <input type="hidden" name="max_price" id="max_price_input" value="{{ request('max_price') }}">
                </div>

                {{-- Sort By --}}
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small text-muted mb-1">Sắp xếp theo</label>
                    <select name="sort_by" class="form-select form-select-sm">
                        <option value="price_asc" {{ request('sort_by', 'price_asc') == 'price_asc' ? 'selected' : '' }}>Giá thấp → cao</option>
                        <option value="price_desc" {{ request('sort_by') == 'price_desc' ? 'selected' : '' }}>Giá cao → thấp</option>
                        <option value="name_asc" {{ request('sort_by') == 'name_asc' ? 'selected' : '' }}>Tên A → Z</option>
                    </select>
                </div>

                {{-- Amenities Quick Filter --}}
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Tiện nghi</label>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown">
                            <span id="amenities-text">Chọn tiện nghi</span>
                            <i class="bi bi-chevron-down small"></i>
                        </button>
                        <div class="dropdown-menu p-3 shadow" style="min-width: 250px;" onclick="event.stopPropagation()">
                            @foreach($amenities->take(6) as $amenity)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]"
                                           value="{{ $amenity->id }}" id="am_{{ $amenity->id }}"
                                           {{ in_array($amenity->id, (array)request('amenities')) ? 'checked' : '' }}
                                           onchange="updateAmenitiesText()">
                                    <label class="form-check-label small" for="am_{{ $amenity->id }}">
                                        {{ $amenity->name }}
                                    </label>
                                </div>
                            @endforeach
                            <div class="mt-2 pt-2 border-top">
                                <button type="button" class="btn btn-primary btn-sm w-100" onclick="applyAmenitiesFilter()">Áp dụng</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reset Button --}}
                <div class="col-lg-1 col-md-12">
                    <a href="{{ route('home') }}" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-x-lg"></i> Xóa
                    </a>
                </div>

                {{-- Apply Button --}}
                <div class="col-lg-1 col-md-12">
                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="applyQuickFilters()">
                        Áp dụng
                    </button>
                </div>
            </div>
        </form>
    </section>

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
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-0 fw-bold">
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
                                        <img src="{{ $type->rooms->flatMap->images->first()->image_url ?? $type->image ?? $placeholderSvg }}"
                                             class="lh-result-img w-100 h-100" style="object-fit: cover;"
                                             alt="{{ $type->name }}">
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
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <p class="lh-section-eyebrow mb-1">Bộ sưu tập phòng</p>
                <h2 class="h4 fw-bold mb-0">Chọn không gian lý tưởng cho bạn</h2>
            </div>
            <div class="text-muted">{{ $roomTypesList->total() }} loại phòng hiện có</div>
        </div>

        @php
            $placeholderSvg = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22250%22 viewBox=%220 0 400 250%22%3E%3Crect fill=%22%231e293b%22 width=%22400%22 height=%22250%22/%3E%3Ctext fill=%22%2394a3b8%22 font-size=%2218%22 x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22%3ELight Hotel%3C/text%3E%3C/svg%3E";
        @endphp

        <div class="d-flex flex-column gap-4">
            @forelse($roomTypesList as $type)
                <div class="lh-result-card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                    <div class="row g-0 h-100">
                        <div class="col-md-4 col-sm-5">
                            <div class="lh-result-img-wrap position-relative h-100" style="height: 250px;">
                                @php
                                    $firstRoom = $type->rooms->first();
                                    $imageUrl = $firstRoom ? ($firstRoom->getDisplayImageUrls()[0] ?? $placeholderSvg) : ($type->image ? App\Models\Room::resolveImageUrl($type->image) : $placeholderSvg);
                                @endphp
                                <img src="{{ $imageUrl }}"
                                     class="w-100 h-100" style="object-fit: cover;"
                                     alt="{{ $type->name }}">
                                <span class="position-absolute top-0 start-0 m-3 badge bg-primary">Phổ biến</span>
                            </div>
                        </div>
                        <div class="col-md-8 col-sm-7 d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h3 class="h4 fw-bold mb-1">{{ $type->name }}</h3>
                                    <div class="text-muted small">
                                        <i class="bi bi-aspect-ratio me-1"></i>{{ $type->area ?? 30 }} m² · 
                                        <i class="bi bi-people me-1"></i>{{ $type->adult_capacity }} Người lớn, {{ $type->child_capacity }} Trẻ em
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">Giá chỉ từ</div>
                                    <div class="h4 fw-bold text-primary mb-0">{{ number_format($type->price, 0, ',', '.') }} VNĐ</div>
                                    <div class="text-muted small">/ đêm</div>
                                </div>
                            </div>

                            <div class="lh-room-card-amenities mb-3 border-0 p-0">
                                <span class="badge bg-light text-dark border-0 rounded-pill px-3 py-2"><i class="bi bi-wifi me-1"></i> Wi-Fi miễn phí tốc độ cao</span>
                                <span class="badge bg-light text-dark border-0 rounded-pill px-3 py-2"><i class="bi bi-snow me-1"></i> Ban công view biển</span>
                                <span class="badge bg-light text-dark border-0 rounded-pill px-3 py-2"><i class="bi bi-wind me-1"></i> Máy sấy tóc cao cấp</span>
                            </div>

                            <p class="text-muted mb-4 flex-grow-1">
                                {{ Str::limit($type->description, 180) }}
                                <br><a href="#" class="text-primary small text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#policyModal{{ $type->id }}">Tiện nghi và chính sách</a>
                            </p>

                            <div class="d-flex justify-content-end align-items-center gap-3">
                                <a href="{{ route('rooms.search', ['room_type' => $type->id, 'check_in' => date('Y-m-d'), 'check_out' => date('Y-m-d', strtotime('+1 day'))]) }}" class="btn btn-warning fw-bold px-5 py-2 rounded-pill shadow-sm">
                                    Chọn phòng
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Include Modals --}}
                @include('rooms.partials.modals', ['type' => $type])
            @empty
                <p class="text-center text-muted py-5">Hiện chưa có loại phòng nào.</p>
            @endforelse
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $roomTypesList->links('pagination::bootstrap-5') }}
        </div>

    @endif
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

function applyQuickFilters() {
    // Get all form data from search form
    var searchForm = document.getElementById('search-form');
    var formData = new FormData(searchForm);

    // Get quick filter form data
    var quickFilterForm = document.getElementById('quick-filter-form');
    var quickFormData = new FormData(quickFilterForm);

    // Get checked amenities
    var checkedAmenities = [];
    document.querySelectorAll('input[name="amenities[]"]:checked').forEach(function(cb) {
        checkedAmenities.push(cb.value);
    });

    // Get price range from dropdown
    var priceRangeSelect = quickFilterForm.querySelector('select[name="price_range"]');
    var priceRange = priceRangeSelect ? priceRangeSelect.value : '';

    // Build URL with all parameters
    var url = new URL(window.location);

    // Clear all filter parameters first
    url.searchParams.delete('room_type');
    url.searchParams.delete('min_price');
    url.searchParams.delete('max_price');
    url.searchParams.delete('sort_by');
    url.searchParams.delete('amenities[]');
    url.searchParams.set('search', '1');

    // Add search form parameters
    for (var pair of formData.entries()) {
        if (pair[0] !== 'search') {
            url.searchParams.set(pair[0], pair[1]);
        }
    }

    // Add quick filter parameters
    for (var pair of quickFormData.entries()) {
        if (pair[0] !== 'search' && pair[0] !== 'child_ages[]' && pair[0] !== 'price_range') {
            url.searchParams.set(pair[0], pair[1]);
        }
    }

    // Add price range from dropdown
    if (priceRange) {
        var parts = priceRange.split('-');
        url.searchParams.set('min_price', parts[0] || '');
        url.searchParams.set('max_price', parts[1] || '');
    }

    // Add amenities
    checkedAmenities.forEach(function(amenity) {
        url.searchParams.append('amenities[]', amenity);
    });

    // Redirect
    window.location.href = url.toString();
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

function applyAmenitiesFilter() {
    // Get all form data from search form
    var searchForm = document.getElementById('search-form');
    var formData = new FormData(searchForm);

    // Get checked amenities
    var checkedAmenities = [];
    document.querySelectorAll('input[name="amenities[]"]:checked').forEach(function(cb) {
        checkedAmenities.push(cb.value);
    });

    // Build URL with all parameters
    var url = new URL(window.location);
    url.searchParams.delete('amenities[]');
    url.searchParams.set('search', '1');

    // Add search form parameters
    for (var pair of formData.entries()) {
        if (pair[0] !== 'search') {
            url.searchParams.set(pair[0], pair[1]);
        }
    }

    // Add amenities
    checkedAmenities.forEach(function(amenity) {
        url.searchParams.append('amenities[]', amenity);
    });

    // Redirect
    window.location.href = url.toString();
}

function updateQuickFilter(checkbox) {
    // Get all form data from search form
    var searchForm = document.getElementById('search-form');
    var formData = new FormData(searchForm);

    // Get checked amenities
    var checkedAmenities = [];
    document.querySelectorAll('input[name="amenities[]"]:checked').forEach(function(cb) {
        checkedAmenities.push(cb.value);
    });

    // Build URL with all parameters
    var url = new URL(window.location);
    url.searchParams.delete('amenities[]');
    url.searchParams.set('search', '1');

    // Add search form parameters
    for (var pair of formData.entries()) {
        if (pair[0] !== 'search') {
            url.searchParams.set(pair[0], pair[1]);
        }
    }

    // Add amenities
    checkedAmenities.forEach(function(amenity) {
        url.searchParams.append('amenities[]', amenity);
    });

    // Redirect
    window.location.href = url.toString();
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
    var children = parseInt(document.getElementById('guest-children').value);
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
