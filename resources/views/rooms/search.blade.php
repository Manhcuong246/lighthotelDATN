@extends('layouts.app')

@section('title', 'Chọn phòng - ' . ($hotel->name ?? 'Light Hotel'))

@push('styles')
<style>
    :root {
        --primary-gold: #febb02;
        --secondary-blue: #0071c2;
        --text-dark: #1a202c;
        --text-muted: #718096;
        --border-color: #edf2f7;
    }

    /* Override: trên trang search (nền sáng) */
    .booking-container .bk-search-stack {
        border: 3px solid #febb02 !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
        background: #fff !important;
        overflow: visible !important;
        position: relative;
        z-index: 50;
    }
    .booking-container .bk-search-bar.bk-search-bar--in-stack {
        border-radius: 9px 9px 0 0 !important;
        overflow: hidden !important;
    }
    .booking-container .bk-filter-row {
        overflow: visible !important;
        border-radius: 0 0 9px 9px;
        position: relative;
    }
    .booking-container .bk-filter-row .col-lg-3,
    .booking-container .bk-filter-row .col-md-6,
    .booking-container .bk-filter-row .dropdown {
        overflow: visible !important;
    }
    .booking-container .bk-search-btn {
        background: #0071c2 !important;
        border-radius: 0 9px 0 0 !important;
    }
    .booking-container .bk-search-btn:hover {
        background: #005fa3 !important;
    }

    /* Amenities popup — fixed overlay */
    .amenities-popup {
        position: fixed;
        z-index: 9999;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.18);
        padding: 16px 20px;
        min-width: 240px;
        max-width: 340px;
        max-height: 320px;
        overflow-y: auto;
    }

    /* Premium Guest Info Cards */
    .guest-input-card {
        background: #ffffff;
        border: 1px solid #edf2f7;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .guest-input-card:hover {
        border-color: #cbd5e0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .input-group-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #718096;
        font-weight: 700;
        margin-bottom: 6px;
        display: block;
    }
    .input-icon-wrapper {
        position: relative;
        transition: all 0.2s;
    }
    .input-icon-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #a0aec0;
        font-size: 0.9rem;
    }
    .input-with-icon {
        padding-left: 38px !important;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        height: 40px !important;
        font-size: 0.85rem !important;
        transition: all 0.2s !important;
    }
    .input-with-icon:focus {
        border-color: var(--secondary-blue) !important;
        box-shadow: 0 0 0 3px rgba(0, 113, 194, 0.1) !important;
        background: #fff !important;
    }
    .guest-info-section-title {
        color: var(--secondary-blue);
        font-weight: 700;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #ebf4ff;
    }

    .room-type-card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 25px rgba(0,0,0,0.06);
        margin-bottom: 25px;
        border: 1px solid var(--border-color);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .room-type-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.1);
    }
    .room-type-main {
        display: flex;
        min-height: 250px;
    }
    .room-type-img {
        width: 320px;
        height: 260px;
        position: relative;
        overflow: hidden;
        flex-shrink: 0;
    }
    .room-type-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .room-type-card:hover .room-type-img img {
        transform: scale(1.1);
    }
    .room-type-info {
        flex: 1;
        padding: 25px;
        display: flex;
        flex-direction: column;
    }
    .room-type-name {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 12px;
    }
    .room-type-features {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
        color: #4a5568;
    }
    .room-type-features span {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }
    .room-type-features i { color: var(--secondary-blue); }
    .room-type-amenities {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: auto;
    }
    .badge-amenity {
        background: #f7fafc;
        color: #4a5568;
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        font-size: 0.75rem;
    }
    .room-type-action {
        width: 220px;
        padding: 25px;
        border-left: 1px solid var(--border-color);
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    .price-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-bottom: 20px;
    }
    .price-val {
        font-size: 2rem;
        font-weight: 800;
        color: var(--secondary-blue);
        line-height: 1;
    }
    .price-unit {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin: 10px 0 30px;
    }
    .btn-select-room {
        background: var(--primary-gold);
        color: #1a202c;
        font-weight: 700;
        padding: 12px 25px;
        border-radius: 12px;
        border: none;
        width: 100%;
        transition: all 0.2s;
    }
    .btn-select-room:hover {
        background: #f59e0b;
        transform: scale(1.03);
    }

    /* Selection Table Area */
    .room-selection-area {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease-out;
        background: #fff;
    }
    .room-selection-area.show {
        max-height: 1000px;
        padding: 0;
    }
    .selection-table {
        width: 100%;
        border-collapse: collapse;
    }
    .selection-table th {
        background: #f8fafc;
        padding: 12px 25px;
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--text-muted);
        font-weight: 600;
        border-bottom: 1px solid var(--border-color);
        text-align: left;
    }
    .selection-row td {
        padding: 20px 25px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: top;
    }
    .rate-title { font-weight: 700; color: #d32f2f; margin-bottom: 5px; }
    .rate-benefit { font-size: 0.85rem; color: #16a34a; list-style: none; padding: 0; margin: 0; }
    .rate-benefit li { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }

    .room-qty-select {
        width: 130px;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        font-weight: 600;
        color: #4a5568;
        background-color: #fff;
    }

    /* Sidebar Summary */
    .booking-summary-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: none;
        position: sticky;
        top: 24px;
        z-index: 100;
        overflow: hidden;
    }
    .summary-header {
        background: #1e293b;
        color: #fff;
        padding: 18px 25px;
        font-weight: 700;
        font-size: 1.25rem;
    }
    .summary-body { padding: 25px; }
    .summary-total { margin-top: 25px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
    .summary-total-val { font-weight: 800; font-size: 1.75rem; color: var(--secondary-blue); }
    .btn-book-now {
        background: #2563eb;
        color: #fff;
        font-weight: 700;
        padding: 15px;
        border-radius: 12px;
        border: none;
        width: 100%;
        margin-top: 20px;
        transition: all 0.2s;
    }
    .btn-book-now:hover:not(:disabled) { background: #1e40af; transform: translateY(-2px); }
    .btn-book-now:disabled { background: #cbd5e0; cursor: not-allowed; }

    /* Guest Input Premium Styling */
    .guest-input-group {
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0 !important;
    }
    .guest-input-group:hover {
        border-color: #3b82f6 !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08) !important;
    }
    .input-icon-wrapper {
        position: relative;
    }
    .input-icon-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.9rem;
    }
    .input-with-icon {
        padding-left: 35px !important;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
    }
    .input-with-icon:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }

    /* Modal Styles */
    .modal-header-custom { display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; border-bottom: 1px solid #eee; }
    .modal-title-custom { font-weight: 700; font-size: 1.1rem; text-transform: uppercase; }

    /* Room Detail Modal Styles */
    .room-detail-modal .modal-content { border-radius: 20px; overflow: hidden; background: #f8fafc; }
    .room-detail-header-info { padding: 20px 0; }
    .room-price-big { font-size: 1.5rem; font-weight: 800; color: #1a202c; }
    .room-rating-stars { color: #f59e0b; margin: 0 10px; }
    .room-spec-item { display: inline-flex; align-items: center; gap: 8px; margin-right: 20px; color: #64748b; font-size: 0.9rem; }
    .room-spec-item i { font-size: 1.1rem; color: #3b82f6; }

    .detail-card { background: #fff; border-radius: 15px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 20px; border: 1px solid #edf2f7; }
    .detail-section-title { font-weight: 700; font-size: 1.1rem; margin-bottom: 15px; color: #1a202c; display: flex; align-items: center; gap: 10px; }
    .detail-section-title::before { content: ""; width: 4px; height: 20px; background: #3b82f6; border-radius: 2px; }

    /* Carousel Sizing */
    .room-detail-carousel { border-radius: 15px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.15); }
    .room-detail-carousel .carousel-item img { height: 400px; object-fit: cover; }

    /* Reviews */
    .review-card { background: #fff; border-radius: 15px; padding: 20px; border: 1px solid #edf2f7; margin-bottom: 15px; position: relative; }
    .review-user-avatar { width: 45px; height: 45px; border-radius: 50%; background: #3b82f6; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; }
    .review-user-name { font-weight: 700; color: #1a202c; margin-bottom: 2px; }
    .review-date { font-size: 0.8rem; color: #94a3b8; position: absolute; top: 20px; right: 20px; }
    .review-comment { color: #4a5568; font-size: 0.95rem; line-height: 1.6; margin-top: 10px; }
    .review-reply { background: #f8fafc; border-left: 4px solid #cbd5e0; padding: 15px; margin-top: 15px; border-radius: 0 10px 10px 0; font-size: 0.9rem; }
    .review-reply-label { font-weight: 700; color: #64748b; margin-bottom: 5px; font-size: 0.8rem; text-transform: uppercase; }
    .policy-section { margin-bottom: 20px; }
    .policy-title { font-weight: 700; margin-bottom: 8px; }

    /* Guest Selector CSS */
    .guest-selector-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px;
        margin-top: 8px;
        height: 100%;
    }
    .guest-selector-title {
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 10px;
        color: var(--secondary-blue);
        display: flex;
        justify-content: space-between;
    }

    .btn-remove-room {
        color: #ef4444;
        cursor: pointer;
        font-size: 1.1rem;
        transition: transform 0.2s;
    }
    .btn-remove-room:hover { transform: scale(1.2); }

    @media (max-width: 991px) {
        .room-type-main { flex-direction: column; }
        .room-type-img { width: 100%; height: 200px; }
        .room-type-action { width: 100%; border-left: none; border-top: 1px solid #f0f0f0; align-items: center; }
    }
</style>
@endpush

@section('content')
<div class="booking-container py-4">
    {{-- Search Bar — y hệt trang chủ --}}
    <form action="{{ route('rooms.search') }}" method="GET" id="searchTopBar" class="mb-4" novalidate>
        <div class="bk-search-stack">
            <div class="bk-search-bar bk-search-bar--in-stack">
                {{-- Điểm đến --}}
                <div class="bk-seg bk-seg-dest">
                    <i class="bi bi-building bk-seg-icon"></i>
                    <div class="bk-seg-content">
                        <div class="bk-seg-label">Điểm đến</div>
                        <input type="text" class="bk-input" value="{{ $hotel->name ?? 'Light Hotel' }}" readonly style="cursor: default;">
                    </div>
                </div>
                <div class="bk-sep"></div>
                {{-- Nhận phòng - Trả phòng --}}
                <div class="bk-seg bk-seg-dates">
                    <i class="bi bi-calendar-event bk-seg-icon"></i>
                    <div class="bk-seg-content">
                        <div class="bk-seg-label">Nhận phòng - Trả phòng</div>
                        <div class="d-flex align-items-center gap-1 bk-date-range-inputs flex-wrap flex-sm-nowrap">
                            <input type="date" name="check_in" id="search_check_in" class="bk-date-input"
                                   value="{{ $check_in }}" min="{{ date('Y-m-d') }}">
                            <span class="text-muted">→</span>
                            <input type="date" name="check_out" id="search_check_out" class="bk-date-input"
                                   value="{{ $check_out }}" min="{{ date('Y-m-d', strtotime($check_in . ' +1 day')) }}">
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
                {{-- Nút Tìm --}}
                <button type="submit" class="bk-search-btn">Tìm</button>
            </div>

            {{-- Bộ lọc --}}
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
                        <label class="form-label small text-muted mb-1">Dịch vụ đi kèm</label>
                        <div class="position-relative" id="includedServicesWrapper">
                            <button class="btn btn-sm bk-amenities-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="included-services-filter-toggle" onclick="toggleIncludedServicesPopup(event)">
                                <span id="included-services-text">Chọn dịch vụ</span>
                                <i class="bi bi-chevron-down small"></i>
                            </button>
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
        {{-- Hidden included_services checkboxes (synced by JS) --}}
        <div style="display:none;" id="includedServicesHiddenInForm">
            @foreach($catalogServices as $svc)
                <input type="checkbox" name="included_services[]" value="{{ $svc->id }}" id="isearch_h_{{ $svc->id }}"
                       {{ in_array($svc->id, (array) request('included_services')) ? 'checked' : '' }}>
            @endforeach
        </div>
    </form>

    <div class="row">
        <div class="col-lg-8">
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert" style="border-left: 4px solid #dc3545 !important;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                        <ul class="mb-0 list-unstyled">
                            @foreach($errors->all() as $error)
                                <li class="fw-bold">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @forelse($roomTypes as $type)
                <div class="room-type-card">
                    <div class="room-type-main">
                        <div class="room-type-img">
                            @php
                                $phLabel = \Illuminate\Support\Str::limit($type->name, 28);
                                $svgFallback = 'data:image/svg+xml,'.rawurlencode(
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="260">'
                                    .'<rect fill="#e2e8f0" width="100%" height="100%"/>'
                                    .'<text fill="#475569" font-family="system-ui,sans-serif" font-size="13" x="50%" y="50%" text-anchor="middle" dominant-baseline="middle">'
                                    .htmlspecialchars($phLabel, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                                    .'</text></svg>'
                                );

                                $imageUrl = null;
                                if ($type->available_rooms->isNotEmpty()) {
                                    foreach ($type->available_rooms as $ar) {
                                        $urls = $ar->getDisplayImageUrls();
                                        if (! empty($urls)) {
                                            $imageUrl = $urls[0];
                                            break;
                                        }
                                    }
                                }
                                $imageUrl = $imageUrl ?? $type->image_url;
                                $imageUrl = $imageUrl ?? $svgFallback;
                            @endphp
                            <img src="{{ $imageUrl }}" alt="{{ $type->name }}" onerror='this.onerror=null;this.src={!! json_encode($svgFallback) !!}'>
                        </div>
                        <div class="room-type-info">
                            <h3 class="room-type-name">{{ $type->name }}</h3>
                            <div class="room-type-features mb-3">
                                <div class="room-spec-item">
                                    <i class="bi bi-aspect-ratio"></i>
                                    <span>{{ $type->available_rooms->first()->area ?? '28.5' }} m²</span>
                                </div>
                                <div class="room-spec-item">
                                    <i class="bi bi-person"></i>
                                    <span>{{ $type->adult_capacity ?? 2 }}</span>
                                </div>
                            </div>
                            <div class="room-type-amenities mb-3">
                                @foreach($type->services->take(3) as $svc)
                                    <span class="badge badge-amenity">{{ $svc->name }}</span>
                                @endforeach
                            </div>
                            <a href="#" class="text-primary small fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#policyModal{{ $type->id }}">Dịch vụ kèm &amp; chính sách</a>
                        </div>
                        <div class="room-type-action">
                            @if($type->available_rooms->isNotEmpty())
                                <div class="price-label">Giá chỉ từ</div>
                                <div class="price-val">{{ number_format($type->available_rooms->first()->catalogueBasePrice(), 0, ',', '.') }} VNĐ</div>
                                <div class="price-unit">/ đêm</div>
                            @endif
                            <button class="btn btn-select-room toggle-selection" data-target="selection{{ $type->id }}">Chọn phòng</button>
                        </div>
                    </div>

                    {{-- Selection Area --}}
                    <div class="room-selection-area" id="selection{{ $type->id }}">
                        <table class="selection-table">
                            <thead>
                                <tr>
                                    <th>Thông tin phòng</th>
                                    <th>Số lượng khách</th>
                                    <th>Giá phòng / đêm</th>
                                    <th style="width: 120px;">Số lượng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="selection-row">
                                    <td>
                                        <div class="rate-title">Free Cancellation 2026</div>
                                        <ul class="rate-benefit">
                                            <li><i class="bi bi-check-lg"></i> Bao gồm ăn sáng</li>
                                            <li>
                                                <i class="bi bi-check-lg"></i> Tối đa {{ (int) ($type->capacity ?? 6) }} khách/phòng
                                            </li>
                                            <li><i class="bi bi-check-lg"></i> Không hoàn phí khi hủy</li>
                                        </ul>
                                        @php $firstRoom = $type->available_rooms->first(); @endphp
                                        @if($firstRoom)
                                            <a href="#" class="text-primary small fw-bold mt-2 d-inline-block" data-bs-toggle="modal" data-bs-target="#roomDetailModal{{ $firstRoom->id }}">Xem chi tiết phòng ></a>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1 text-muted">
                                            @for($i = 0; $i < ($type->adult_capacity ?? 2); $i++)
                                                <i class="bi bi-person-fill fs-5"></i>
                                            @endfor
                                            @for($i = 0; $i < ($type->child_capacity ?? 0); $i++)
                                                <i class="bi bi-person-fill fs-6" style="opacity: 0.6;"></i>
                                            @endfor
                                            <i class="bi bi-info-circle text-primary ms-1" style="cursor:help;" data-bs-toggle="tooltip" data-bs-placement="top"
                                               title="TC {{ (int) ($type->standard_capacity ?? $type->capacity) }} · Tối đa {{ (int) ($type->capacity ?? 6) }} · Vượt TC tính phụ thu."></i>
                                        </div>
                                    </td>
                                    <td>
                                        @if($type->available_rooms->isNotEmpty())
                                            <div class="text-muted text-decoration-line-through small">{{ number_format($type->available_rooms->first()->catalogueBasePrice() * 1.2, 0, ',', '.') }} VNĐ / đêm</div>
                                            <div class="fw-bold fs-5 text-primary">{{ number_format($type->available_rooms->first()->catalogueBasePrice(), 0, ',', '.') }} VNĐ / đêm</div>
                                        @endif
                                    </td>
                                    <td>
                                        <select class="room-qty-select"
                                                data-type-id="{{ $type->id }}"
                                                data-type-name="{{ $type->name }}"
                                                data-price="{{ $type->available_rooms->isNotEmpty() ? $type->available_rooms->first()->catalogueBasePrice() : 0 }}"
                                                data-max-guests="{{ (int) ($type->capacity ?? ($type->available_rooms->isNotEmpty() ? $type->available_rooms->first()->catalogueMaxGuests() : 6)) }}"
                                                data-standard-guests="{{ (int) ($type->standard_capacity ?? $type->capacity) }}"
                                                data-adult-surcharge-rate="{{ \App\Support\RoomOccupancyPricing::adultSurchargeRate($type) }}"
                                                data-child-surcharge-rate="{{ \App\Support\RoomOccupancyPricing::childSurchargeRate($type) }}"
                                                data-bookable-slots="{{ (int) ($type->bookable_slot_count ?? $type->available_rooms->count()) }}">
                                            <option value="0">0 Phòng</option>
                                            @for($i = 1; $i <= min((int) ($type->bookable_slot_count ?? $type->available_rooms->count()), 5); $i++)
                                                <option value="{{ $i }}">{{ $i }} Phòng</option>
                                            @endfor
                                        </select>
                                    </td>
                                </tr>
                                <tr id="guestRow{{ $type->id }}" class="d-none">
                                    <td colspan="4" class="p-3 bg-light">
                                        <div id="guestSelectors{{ $type->id }}" class="row g-3">
                                            {{-- JS will populate this --}}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Modals for each RoomType --}}
                @include('rooms.partials.modals', ['type' => $type])
            @empty
                <div class="alert alert-info py-5 text-center rounded-4 shadow-sm">
                    <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                    <h4 class="fw-bold">Rất tiếc, đã hết phòng!</h4>
                    <p class="text-muted mb-0">Vui lòng đổi ngày hoặc tiêu chí tìm kiếm.</p>
                </div>
            @endforelse

            <div class="d-flex justify-content-center mt-4">
                @if(!is_array($roomTypes) && method_exists($roomTypes, 'links'))
                    {{ $roomTypes->links() }}
                @endif
            </div>
        </div>

        {{-- Sidebar Summary --}}
        <div class="col-lg-4" style="align-self: flex-start;">
            <div class="booking-summary-card">
                <div class="summary-header">Thông tin đặt phòng</div>
                <div class="summary-body">
                    <form action="{{ route('bookings.store') }}" method="POST" id="checkoutForm">
                        @csrf
                        @if($errors->any())
                            <div class="alert alert-danger py-2 small mb-3">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <input type="hidden" name="payment_method" value="vnpay">
                        <input type="hidden" name="check_in" value="{{ $check_in }}">
                        <input type="hidden" name="check_out" value="{{ $check_out }}">
                        <div id="roomInputsContainer"></div>

                        <div class="summary-group">
                            <div class="summary-group-title">Thông tin phòng</div>
                            <div id="selectedRoomsList" class="mb-3">
                                <p class="text-muted italic small">Chưa có phòng nào được chọn</p>
                            </div>
                        </div>

                        <div class="summary-group">
                            <div class="summary-group-title">Thông tin khách hàng</div>
                            @php $cust = auth()->user(); @endphp
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Họ tên *</label>
                                <input type="text" name="full_name" class="form-control form-control-sm"
                                       value="{{ old('full_name', $cust?->full_name) }}" {{ $cust ? 'readonly' : '' }} required>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Email *</label>
                                <input type="email" name="email" class="form-control form-control-sm"
                                       value="{{ old('email', $cust?->email) }}" {{ $cust ? 'readonly' : '' }} required>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Số điện thoại *</label>
                                <input type="text" name="phone" class="form-control form-control-sm"
                                       value="{{ old('phone', $cust?->phone) }}" {{ $cust ? 'readonly' : '' }} required>
                            </div>

                            <!-- Thông tin người đại diện - chỉ 1 form duy nhất -->
                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="bi bi-person-badge text-primary"></i>
                                    <span class="fw-bold">Người đại diện</span>
                                    <span class="badge bg-primary">Bắt buộc</span>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted mb-1">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control form-control-sm"
                                           value="{{ old('name') }}" placeholder="Nhập họ tên người đại diện" required>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted mb-1">CCCD/CMND <span class="text-danger">*</span></label>
                                    <input type="text" name="cccd" class="form-control form-control-sm"
                                           value="{{ old('cccd') }}" placeholder="Nhập số CCCD" maxlength="12" required>
                                    <div class="form-text text-muted" style="font-size: 0.7rem;">Nhập đúng 12 số CCCD</div>
                                </div>
                            </div>
                        </div>

                        <div class="summary-group">
                            <div class="summary-group-title">Mã giảm giá</div>
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" id="couponCode" name="coupon_code" class="form-control" placeholder="Nhập mã...">
                                <button class="btn btn-outline-primary" type="button" id="btnApplyCoupon">Áp dụng</button>
                            </div>
                            <div id="couponMessage" class="small mt-1"></div>
                        </div>

                        <div class="summary-total">
                            <div id="discountRow" class="d-none justify-content-between align-items-center mb-1 small text-success">
                                <span class="fw-bold">Giảm giá (<span id="discountPercent">0</span>%)</span>
                                <span id="discountAmount">0 VNĐ</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Tổng cộng</span>
                                <span class="summary-total-val" id="totalDisplay">0 VNĐ</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-book-now" id="btnBookNow" disabled>ĐẶT NGAY</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Popup lọc dịch vụ đi kèm — nằm ngoài mọi container để không bị clip --}}
<div id="includedServicesPopup" class="amenities-popup" style="display:none;">
    <div class="amenities-popup-content">
        @forelse($catalogServices as $svc)
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox"
                       value="{{ $svc->id }}" id="isearch_pop_{{ $svc->id }}"
                       {{ in_array($svc->id, (array) request('included_services')) ? 'checked' : '' }}
                       onchange="syncIncludedServiceCheckbox(this)">
                <label class="form-check-label small" for="isearch_pop_{{ $svc->id }}">
                    {{ $svc->name }}
                </label>
            </div>
        @empty
            <p class="small text-muted mb-0">Chưa có dịch vụ trong danh mục.</p>
        @endforelse
    </div>
</div>
@push('scripts')
<script>
function changeRooms(delta) {
    var input = document.getElementById('rooms-input');
    if (!input) return;
    var val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > 20) val = 20;
    input.value = val;
    var minusBtn = document.getElementById('rooms-minus-btn');
    var plusBtn  = document.getElementById('rooms-plus-btn');
    if (minusBtn) minusBtn.disabled = (val <= 1);
    if (plusBtn) plusBtn.disabled = (val >= 20);
}

function applyPriceRange(select) {
    var value = select.value;
    var minPriceInput = document.getElementById('min_price_input');
    var maxPriceInput = document.getElementById('max_price_input');
    minPriceInput.value = '';
    maxPriceInput.value = '';
    if (value) {
        var parts = value.split('-');
        minPriceInput.value = parts[0] || '';
        maxPriceInput.value = parts[1] || '';
    }
}

function toggleIncludedServicesPopup(e) {
    e.stopPropagation();
    var popup = document.getElementById('includedServicesPopup');
    if (popup.style.display === 'none' || !popup.style.display) {
        var btn = document.getElementById('included-services-filter-toggle');
        var rect = btn.getBoundingClientRect();
        popup.style.top = (rect.bottom + 4) + 'px';
        popup.style.left = rect.left + 'px';
        popup.style.display = 'block';
    } else {
        popup.style.display = 'none';
    }
}

function syncIncludedServiceCheckbox(cb) {
    var val = cb.value;
    var hidden = document.querySelector('#includedServicesHiddenInForm input[value="' + val + '"]');
    if (hidden) hidden.checked = cb.checked;
    updateIncludedServicesFilterText();
}

function updateIncludedServicesFilterText() {
    var checked = document.querySelectorAll('#includedServicesPopup input[type="checkbox"]:checked');
    var textElement = document.getElementById('included-services-text');
    if (!textElement) return;
    if (checked.length === 0) {
        textElement.textContent = 'Chọn dịch vụ';
    } else if (checked.length === 1) {
        var lbl = document.querySelector('label[for="' + checked[0].id + '"]');
        textElement.textContent = lbl ? lbl.textContent.trim() : '1 dịch vụ';
    } else {
        textElement.textContent = checked.length + ' dịch vụ đã chọn';
    }
}

document.addEventListener('click', function(e) {
    var popup = document.getElementById('includedServicesPopup');
    var toggle = document.getElementById('included-services-filter-toggle');
    if (popup && popup.style.display !== 'none') {
        if (!popup.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
            popup.style.display = 'none';
        }
    }
});

const __BP = @json(config('booking.pricing'));
/**
 * Đồng bộ với App\Support\RoomOccupancyPricing
 *
 * Capacity (max): chỉ adults + c611. Trẻ 0–5 không tính sức chứa / không chiếm chỗ tiêu chuẩn.
 * Phụ phí = % giá phòng/đêm khi NL + trẻ 6–11 vượt standardCap.
 */
function bookingPriceBreakdown(base, adults, c05, c611, adultRate, childRate, stdCap, maxCap) {
    const _stdCap = Number(stdCap) || 2;
    const _maxCap = Number(maxCap) || 3;
    const maxC05Free = Number(__BP.max_children_05) || 2;
    const aRate = (adultRate != null) ? Number(adultRate) : (Number(__BP.default_adult_surcharge_rate) || 0.25);
    const cRate = (childRate != null) ? Number(childRate) : (Number(__BP.default_child_surcharge_rate) || 0.125);

    const billableSlots = Math.max(0, _stdCap);
    const extraAdults = Math.max(0, adults - billableSlots);
    const remainingSlots = Math.max(0, billableSlots - adults);
    const extraChildren = Math.max(0, c611 - remainingSlots);

    const adultFee = extraAdults * aRate * base;
    const childFee = extraChildren * cRate * base;
    const surcharge = adultFee + childFee;
    const perNight = base + surcharge;

    const total = adults + c611 + c05;

    return { perNight, surcharge, adultFee, childFee, extraAdults, extraChildren, effective: total, stdCap: _stdCap, maxCap: _maxCap, maxC05: maxC05Free, allowed: true };
}

document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.toggle-selection');
    const qtySelectors = document.querySelectorAll('.room-qty-select');
    const selectedRoomsList = document.getElementById('selectedRoomsList');
    const totalDisplay = document.getElementById('totalDisplay');
    const btnBookNow = document.getElementById('btnBookNow');
    const roomInputsContainer = document.getElementById('roomInputsContainer');
    let nights = {{ $nights }};
    let currentDiscountPercent = 0;
    let isInitialLoad = true; // Flag để không hiển thị cảnh báo khi trang vừa load

    // Top bar date change → auto-update nights & re-calculate pricing
    const topCi = document.getElementById('search_check_in');
    const topCo = document.getElementById('search_check_out');

    function updateNightsFromTopBar() {
        if (!topCi || !topCo || !topCi.value || !topCo.value) return;
        const diff = Math.ceil((new Date(topCo.value) - new Date(topCi.value)) / 86400000);
        if (diff > 0) {
            nights = diff;
            updateSummary();
        }
    }

    if (topCi) topCi.addEventListener('change', function() {
        if (topCo) topCo.min = this.value;
        updateNightsFromTopBar();
    });
    if (topCo) topCo.addEventListener('change', updateNightsFromTopBar);

    const checkoutForm = document.getElementById('checkoutForm');
    const inputName = document.querySelector('input[name="full_name"]');
    const inputEmail = document.querySelector('input[name="email"]');
    const inputPhone = document.querySelector('input[name="phone"]');

    if (inputName) inputName.addEventListener('input', saveSelection);
    if (inputEmail) inputEmail.addEventListener('input', saveSelection);
    if (inputPhone) inputPhone.addEventListener('input', saveSelection);

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            sessionStorage.removeItem('hotel_booking_selection');
        });
    }

    // Toggle expansion logic
    toggles.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const target = document.getElementById(targetId);
            target.classList.toggle('show');
            this.textContent = target.classList.contains('show') ? 'Đóng' : 'Chọn phòng';
        });
    });

    // Coupon Logic
    const btnApplyCoupon = document.getElementById('btnApplyCoupon');
    const couponInput = document.getElementById('couponCode');
    const couponMessage = document.getElementById('couponMessage');
    const discountRow = document.getElementById('discountRow');
    const discountPercentSpan = document.getElementById('discountPercent');
    const discountAmountSpan = document.getElementById('discountAmount');

    btnApplyCoupon.addEventListener('click', function() {
        const code = couponInput.value.trim();
        if (!code) return;

        fetch('{{ route('coupons.verify') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ code: code })
        })
        .then(async response => {
            let data = {};
            try {
                data = await response.json();
            } catch (e) { /* ignore */ }
            if (!response.ok) {
                currentDiscountPercent = 0;
                const msg = (data.errors && data.errors.code && data.errors.code[0]) || data.message || 'Không thể kiểm tra mã giảm giá.';
                couponMessage.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i> ${msg}</span>`;
                updateSummary();
                return;
            }
            if (data.success) {
                currentDiscountPercent = data.discount_percent;
                couponMessage.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill"></i> ${data.message}</span>`;
                updateSummary();
            } else {
                currentDiscountPercent = 0;
                couponMessage.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i> ${data.message}</span>`;
                updateSummary();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            couponMessage.innerHTML = '<span class="text-danger">Đã có lỗi xảy ra, vui lòng thử lại.</span>';
        });
    });

    // Update selection logic
    qtySelectors.forEach(select => {
        select.addEventListener('change', updateSummary);
    });

    function updateSummary(isInitialLoadParam = false) {
        let subtotal = 0;
        let htmlSnippet = '';
        let hiddenInputs = '';
        let capacityWarnings = ''; // Lưu cảnh báo sức chứa riêng
        let hasAdultValidationError = false;

        saveSelection();

        let globalGuestIdx = 0;
        qtySelectors.forEach(select => {
            const qty = parseInt(select.value);
            const typeId = select.getAttribute('data-type-id');
            const typeName = select.getAttribute('data-type-name');
            const basePrice = parseFloat(select.getAttribute('data-price'));
            const adultSurchargeRate = parseFloat(select.getAttribute('data-adult-surcharge-rate')) || null;
            const childSurchargeRate = parseFloat(select.getAttribute('data-child-surcharge-rate')) || null;
            const bookableSlots = parseInt(select.getAttribute('data-bookable-slots') || '0', 10);

            const guestRow = document.getElementById(`guestRow${typeId}`);
            const guestContainer = document.getElementById(`guestSelectors${typeId}`);

            if (qty > 0) {
                guestRow.classList.remove('d-none');
                // Chỉ hiển thị thông tin số lượng khách, không render form nhập chi tiết
                let currentContainers = guestContainer.querySelectorAll('.guest-selector-item').length;
                if (currentContainers !== qty) {
                    let containerHtml = '';
                    for (let i = 1; i <= qty; i++) {
                        const roomIdx = i - 1;
                        containerHtml += `
                            <div class="col-lg-4 col-md-6 mb-3 guest-selector-item" data-room-index="${roomIdx}" data-type-id="${typeId}">
                                <div class="guest-selector-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-primary">Phòng ${i}</span>
                                        <span class="small text-muted">${typeName}</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <label style="font-size: 0.65rem; color: #718096;">Người lớn</label>
                                            <input type="number" class="form-control form-control-sm guest-count adults-count"
                                                   name="adults[${roomIdx}]" data-type-id="${typeId}" data-room-index="${roomIdx}" value="1" min="1" max="6" required>
                                        </div>
                                        <div class="col-4">
                                            <label style="font-size: 0.65rem; color: #718096;" title="Tối đa 2 em / phòng (miễn phụ thu)">Trẻ 0–5t</label>
                                            <select class="form-select form-select-sm guest-count child-05-count"
                                                   name="children_0_5[${roomIdx}]" data-type-id="${typeId}" data-room-index="${roomIdx}">
                                                <option value="0">0</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label style="font-size: 0.65rem; color: #718096;" title="50% giá phòng">Trẻ 6–11t</label>
                                            <input type="number" class="form-control form-control-sm guest-count child-611-count"
                                                   name="children_6_11[${roomIdx}]" data-type-id="${typeId}" data-room-index="${roomIdx}" value="0" min="0" max="5">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        globalGuestIdx++;
                    }
                    guestContainer.innerHTML = containerHtml;
                }

                // Cập nhật lại danh sách input sau khi đã được vẽ lại
                const adultsArrNew = guestContainer.querySelectorAll('.adults-count');
                const child05ArrNew = guestContainer.querySelectorAll('.child-05-count');
                const child611ArrNew = guestContainer.querySelectorAll('.child-611-count');

                for (let i = 0; i < qty; i++) {
                    if (i >= bookableSlots) {
                        continue;
                    }

                    // Bắt buộc mỗi phòng có >= 1 người lớn (không tự động sửa thành 1).
                    const adultsInput = adultsArrNew[i] || null;
                    const rawAdults = adultsInput ? parseInt(adultsInput.value, 10) : NaN;
                    if (!Number.isFinite(rawAdults) || rawAdults < 1) {
                        if (adultsInput) adultsInput.classList.add('is-invalid');
                        hasAdultValidationError = true;
                        capacityWarnings += `<div>• ${typeName} (P.${i + 1}): mỗi phòng phải có ít nhất 1 người lớn.</div>`;
                        continue;
                    }
                    if (adultsInput) adultsInput.classList.remove('is-invalid');
                    const adults = rawAdults;
                    const c05 = (child05ArrNew[i]) ? parseInt(child05ArrNew[i].value || 0) : 0;
                    const c611 = (child611ArrNew[i]) ? parseInt(child611ArrNew[i].value || 0) : 0;

                    const stdGuests = parseInt(select.getAttribute('data-standard-guests') || '3');
                    const maxGuests = parseInt(select.getAttribute('data-max-guests') || '6');
                    const br = bookingPriceBreakdown(basePrice, adults, c05, c611, adultSurchargeRate, childSurchargeRate, stdGuests, maxGuests);
                    const roomPricePerNight = br.perNight;
                    const extraAdultFee = br.adultFee;
                    const childFee = br.childFee;
                    const roomSubtotal = roomPricePerNight * nights;
                    subtotal += roomSubtotal;

                    htmlSnippet += `
                        <div class="mb-2 p-2 border-bottom" style="position: relative;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold" style="font-size: 0.85rem;">${typeName} (P.${i+1})</span>
                                <i class="bi bi-trash3 text-danger cursor-pointer" onclick="removeRoomSelection('${typeId}', ${qty})" title="Hủy phòng này"></i>
                            </div>
                            <div class="small text-muted mb-1" style="font-size: 0.7rem;">
                                <div>Cơ bản: ${new Intl.NumberFormat('vi-VN').format(basePrice)}đ</div>
                                ${extraAdultFee > 0 ? `<div class="text-primary">Phụ thu NL thêm (${br.extraAdults} người): +${new Intl.NumberFormat('vi-VN').format(extraAdultFee)}đ/đêm</div>` : ''}
                                ${childFee > 0 ? `<div class="text-primary">Phụ thu trẻ 6–11 thêm (${br.extraChildren} em): +${new Intl.NumberFormat('vi-VN').format(childFee)}đ/đêm</div>` : ''}
                            </div>
                            <div class="d-flex justify-content-between small" style="font-size: 0.75rem;">
                                <span>${nights} đêm x ${new Intl.NumberFormat('vi-VN').format(roomPricePerNight)}đ</span>
                                <strong>${new Intl.NumberFormat('vi-VN').format(roomSubtotal)}đ</strong>
                            </div>
                        </div>
                    `;

                    hiddenInputs += `
                        <input type="hidden" name="room_type_ids[]" value="${typeId}">
                        <input type="hidden" name="adults[]" value="${adults}">
                        <input type="hidden" name="children_0_5[]" value="${c05}">
                        <input type="hidden" name="children_6_11[]" value="${c611}">
                    `;
                }
            } else {
                guestRow.classList.add('d-none');
                guestContainer.innerHTML = '';
            }

        });

        const discountAmount = subtotal * (currentDiscountPercent / 100);
        const total = subtotal - discountAmount;

        if (currentDiscountPercent > 0 && subtotal > 0) {
            discountRow.classList.remove('d-none');
            discountRow.classList.add('d-flex');
            discountPercentSpan.textContent = currentDiscountPercent;
            discountAmountSpan.textContent = '-' + new Intl.NumberFormat('vi-VN').format(discountAmount) + ' VNĐ';
        } else {
            discountRow.classList.remove('d-flex');
            discountRow.classList.add('d-none');
        }

        const warningHtml = capacityWarnings
            ? `<div class="alert alert-danger py-2 small mb-2">${capacityWarnings}</div>`
            : '';

        if (htmlSnippet === '') {
            selectedRoomsList.innerHTML = warningHtml || '<p class="text-muted italic small">Chưa có phòng nào được chọn</p>';
            btnBookNow.disabled = true;
            btnBookNow.classList.remove('active');
        } else {
            selectedRoomsList.innerHTML = warningHtml + htmlSnippet;
            btnBookNow.disabled = hasAdultValidationError;
            if (hasAdultValidationError) {
                btnBookNow.classList.remove('active');
            } else {
                btnBookNow.classList.add('active');
            }
        }

        totalDisplay.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' VNĐ';
        roomInputsContainer.innerHTML = hiddenInputs;
    }

    window.removeRoomSelection = function(typeId, currentQty) {
        const select = document.querySelector(`.room-qty-select[data-type-id="${typeId}"]`);
        if (select) {
            select.value = Math.max(0, currentQty - 1);
            updateSummary();
        }
    };

    function saveSelection() {
        const selection = {
            room_types: {},
            coupon: couponInput.value.trim(),
            personal: {
                full_name: inputName ? inputName.value : '',
                email: inputEmail ? inputEmail.value : '',
                phone: inputPhone ? inputPhone.value : ''
            }
        };

        qtySelectors.forEach(select => {
            const qty = parseInt(select.value);
            const typeId = select.getAttribute('data-type-id');
            if (qty > 0) {
                const guestContainer = document.getElementById(`guestSelectors${typeId}`);
                const guests = [];
                const adultsArr = guestContainer.querySelectorAll('.adults-count');
                const child05Arr = guestContainer.querySelectorAll('.child-05-count');
                const child611Arr = guestContainer.querySelectorAll('.child-611-count');

                for (let i = 0; i < qty; i++) {
                    if (adultsArr[i]) {
                        guests.push({
                            adults: adultsArr[i].value,
                            c05: child05Arr[i].value,
                            c611: child611Arr[i].value
                        });
                    }
                }
                selection.room_types[typeId] = { qty, guests };
            }
        });

        sessionStorage.setItem('hotel_booking_selection', JSON.stringify(selection));
    }

    function restoreSelection() {
        const saved = sessionStorage.getItem('hotel_booking_selection');
        if (!saved) return;

        const selection = JSON.parse(saved);

        // Restore Personal Info
        if (selection.personal) {
            if (inputName && selection.personal.full_name) inputName.value = selection.personal.full_name;
            if (inputEmail && selection.personal.email) inputEmail.value = selection.personal.email;
            if (inputPhone && selection.personal.phone) inputPhone.value = selection.personal.phone;
        }

        // Restore Coupon
        if (selection.coupon) {
            couponInput.value = selection.coupon;
            btnApplyCoupon.click(); // Trigger verify
        }

        // Restore Rooms
        Object.keys(selection.room_types).forEach(typeId => {
            const data = selection.room_types[typeId];
            const select = document.querySelector(`.room-qty-select[data-type-id="${typeId}"]`);
            if (select) {
                // Ensure we don't exceed current availability
                const maxAvailable = select.options.length - 1;
                select.value = Math.min(data.qty, maxAvailable);

                if (select.value > 0) {
                    // Force update to create guest inputs
                    updateSummary();

                    // Now fill guest inputs
                    const guestContainer = document.getElementById(`guestSelectors${typeId}`);
                    const adultsArr = guestContainer.querySelectorAll('.adults-count');
                    const child05Arr = guestContainer.querySelectorAll('.child-05-count');
                    const child611Arr = guestContainer.querySelectorAll('.child-611-count');

                    data.guests.forEach((g, i) => {
                        if (adultsArr[i]) {
                            adultsArr[i].value = g.adults;
                            child05Arr[i].value = g.c05;
                            child611Arr[i].value = g.c611;
                        }
                    });
                }
            }
        });

        updateSummary(true); // true = isInitialLoad, không hiển thị cảnh báo capacity
        isInitialLoad = false; // Sau khi restore xong, cho phép hiển thị cảnh báo
    }

    // Call restore on load
    setTimeout(restoreSelection, 100);

    // Init dịch vụ filter label on load
    updateIncludedServicesFilterText();

    // Init Bootstrap tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
    // 16/04: Sử dụng Event Delegation để gắn sự kiện cho các ô chọn số lượng khách
    // Cách này giúp sự kiện không bị mất khi HTML bị vẽ lại (updateSummary)
    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('guest-count')) {
            const input = e.target;

            // 1. Cập nhật tiền và tóm tắt ngay lập tức
            updateSummary();

            // 2. Nếu là ô người lớn (adults-count), cập nhật số form khách hàng
            if (window.guestFormManager && input.classList.contains('adults-count')) {
                const roomItem = input.closest('.guest-selector-item');
                if (roomItem) {
                    window.guestFormManager.renderGuestFormsByAdultCount(roomItem);
                }
            }
        }
    });

    // Thêm cả change event cho chắc chắn trên một số trình duyệt
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('guest-count')) {
            // Chỉ chạy nếu dataset chưa được input event xử lý (tránh chạy 2 lần quá gần nhau)
            updateSummary();
            // Nếu là ô người lớn, cập nhật số form khách hàng
            if (window.guestFormManager && e.target.classList.contains('adults-count')) {
                const roomItem = e.target.closest('.guest-selector-item');
                if (roomItem) window.guestFormManager.renderGuestFormsByAdultCount(roomItem);
            }
        }
    });
});
</script>
@endpush
@endsection
