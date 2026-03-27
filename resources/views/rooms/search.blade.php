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

    .search-summary-bar {
        background: #fff;
        border-radius: 12px;
        padding: 20px 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 40px;
        margin-bottom: 30px;
        border: 1px solid var(--border-color);
    }
    .search-info-item {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .search-info-item i {
        font-size: 1.5rem;
        color: var(--secondary-blue);
        background: #eff6ff;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }
    .search-info-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .search-info-val {
        font-weight: 700;
        color: var(--text-dark);
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
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-bottom: 5px;
    }
    .price-val {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--secondary-blue);
    }
    .price-unit {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 20px;
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
        border-radius: 16px;
        padding: 0;
        box-shadow: 0 4px 25px rgba(0,0,0,0.08);
        border: 1px solid var(--border-color);
        position: sticky;
        top: 20px;
        overflow: hidden;
    }
    .summary-header {
        background: #1a202c;
        color: #fff;
        padding: 20px 25px;
        font-weight: 700;
        font-size: 1.1rem;
    }
    .summary-body { padding: 25px; }
    .summary-group-title { font-weight: 700; margin-bottom: 15px; color: #1a202c; }
    .summary-item { display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 10px; }
    .summary-total { margin-top: 25px; padding-top: 20px; border-top: 2px solid #f7fafc; }
    .summary-total-val { font-weight: 800; font-size: 1.5rem; color: var(--secondary-blue); }
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
        padding: 15px;
        margin-top: 10px;
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
    {{-- Search Info Bar --}}
    <form action="{{ route('rooms.search') }}" method="GET" class="search-summary-bar">
        <div class="search-info-item">
            <i class="bi bi-geo-alt"></i>
            <div>
                <div class="search-info-label">Bạn muốn nghỉ dưỡng ở đâu?</div>
                <div class="search-info-val">{{ $hotel->name ?? 'Light Hotel' }}</div>
            </div>
        </div>
        <div class="search-info-item">
            <i class="bi bi-calendar-check"></i>
            <div>
                <div class="search-info-label">Ngày nhận phòng</div>
                <input type="date" name="check_in" id="search_check_in" 
                       class="form-control form-control-sm border-0 fw-bold p-0" 
                       value="{{ $check_in }}" min="{{ date('Y-m-d') }}">
            </div>
        </div>
        <div class="search-info-item">
            <i class="bi bi-calendar-x"></i>
            <div>
                <div class="search-info-label">Ngày trả phòng</div>
                <input type="date" name="check_out" id="search_check_out" 
                       class="form-control form-control-sm border-0 fw-bold p-0" 
                       value="{{ $check_out }}" min="{{ date('Y-m-d', strtotime($check_in . ' +1 day')) }}">
            </div>
        </div>
        <div class="search-info-item">
            <i class="bi bi-door-open"></i>
            <div>
                <div class="search-info-label">Thời gian nghỉ</div>
                <div class="search-info-val text-primary" id="search_nights_display">{{ $nights }} đêm</div>
            </div>
        </div>
        <div class="ms-auto d-flex gap-2">
            <button type="submit" class="btn btn-warning btn-sm rounded-pill fw-bold px-4">Cập nhật</button>
            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Quay lại</a>
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
                                $placeholder = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80';
                                if (str_contains(strtolower($type->name), 'deluxe')) $placeholder = 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=400&q=80';
                                if (str_contains(strtolower($type->name), 'suite')) $placeholder = 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=400&q=80';
                                
                                $imageUrl = $type->image ? asset('storage/' . $type->image) : 
                                           ($type->available_rooms->isNotEmpty() && $type->available_rooms->first()->getDisplayImageUrls() ? 
                                            $type->available_rooms->first()->getDisplayImageUrls()[0] : $placeholder);
                            @endphp
                            <img src="{{ $imageUrl }}" alt="{{ $type->name }}" onerror="this.src='{{ $placeholder }}'">
                        </div>
                        <div class="room-type-info">
                            <h3 class="room-type-name">{{ $type->name }}</h3>
                            <div class="room-type-features">
                                <span><i class="bi bi-aspect-ratio"></i> {{ $type->available_rooms->first()->area ?? '26' }} m²</span>
                                <span><i class="bi bi-bed"></i> {{ $type->beds ?? '1 giường King' }}</span>
                            </div>
                            <div class="room-type-amenities">
                                @if($type->available_rooms->isNotEmpty())
                                    @php $amenities = $type->available_rooms->first()->amenities; @endphp
                                    @foreach($amenities->take(4) as $amenity)
                                        <span class="badge badge-amenity">{{ $amenity->name }}</span>
                                    @endforeach
                                    @if($amenities->count() > 4)
                                        <span class="text-muted small">+{{ $amenities->count() - 4 }}</span>
                                    @endif
                                @endif
                            </div>
                            <a href="#" class="text-primary small fw-bold" data-bs-toggle="modal" data-bs-target="#policyModal{{ $type->id }}">Tiện nghi và chính sách</a>
                        </div>
                        <div class="room-type-action">
                            @if($type->available_rooms->isNotEmpty())
                                <div class="price-label">Giá chỉ từ</div>
                                <div class="price-val">{{ number_format($type->available_rooms->first()->base_price, 0, ',', '.') }} VNĐ</div>
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
                                            <li><i class="bi bi-check-lg"></i> Đã bao gồm ăn sáng</li>
                                            <li><i class="bi bi-check-lg"></i> Không hoàn trả phí khi hủy phòng</li>
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
                                            <span class="ms-1 small">
                                                {{ $type->adult_capacity ?? 2 }} Người lớn
                                                @if($type->child_capacity > 0)
                                                    , {{ $type->child_capacity }} Trẻ em
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($type->available_rooms->isNotEmpty())
                                            <div class="text-muted text-decoration-line-through small">{{ number_format($type->available_rooms->first()->base_price * 1.2, 0, ',', '.') }} VNĐ / đêm</div>
                                            <div class="fw-bold fs-5 text-primary">{{ number_format($type->available_rooms->first()->base_price, 0, ',', '.') }} VNĐ / đêm</div>
                                        @endif
                                    </td>
                                    <td>
                                        <select class="room-qty-select" 
                                                data-type-id="{{ $type->id }}" 
                                                data-type-name="{{ $type->name }}"
                                                data-price="{{ $type->available_rooms->isNotEmpty() ? $type->available_rooms->first()->base_price : 0 }}"
                                                data-max-guests="{{ $type->available_rooms->first()->max_guests ?? 2 }}"
                                                data-max-adults="{{ $type->adult_capacity ?? 2 }}"
                                                data-max-children="{{ $type->child_capacity ?? 0 }}"
                                                data-adult-price="{{ $type->adult_price ?? 0 }}"
                                                data-room-ids="{{ json_encode($type->available_rooms->pluck('id')->toArray()) }}">
                                            <option value="0">0 Phòng</option>
                                            @for($i = 1; $i <= min($type->available_rooms->count(), 5); $i++)
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
                    <p class="text-muted mb-0">Vui lòng chọn ngày khác hoặc thay đổi tiêu chí tìm kiếm của bạn.</p>
                </div>
            @endforelse

            <div class="d-flex justify-content-center mt-4">
                {{ $roomTypes->links() }}
            </div>
        </div>

        {{-- Sidebar Summary --}}
        <div class="col-lg-4">
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
                        <div id="roomInputsContainer"></div>

                        <div class="summary-group">
                            <div class="summary-group-title">{{ $hotel->name ?? 'Light Hotel' }}</div>
                            <div class="summary-item mb-2">
                                <label class="small text-muted mb-1">Ngày nhận phòng</label>
                                <input type="date" name="check_in" id="sidebar_check_in" class="form-control form-control-sm fw-bold border-0 bg-light" 
                                       value="{{ $check_in }}" min="{{ date('Y-m-d') }}">
                            </div>
                            <div class="summary-item mb-2">
                                <label class="small text-muted mb-1">Ngày trả phòng</label>
                                <input type="date" name="check_out" id="sidebar_check_out" class="form-control form-control-sm fw-bold border-0 bg-light" 
                                       value="{{ $check_out }}" min="{{ date('Y-m-d', strtotime($check_in . ' +1 day')) }}">
                            </div>
                            <div class="summary-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Thời gian nghỉ:</span>
                                    <strong class="text-primary" id="sidebar_nights_display">{{ $nights }} đêm</strong>
                                </div>
                            </div>
                            <button type="button" id="btnUpdateDates" class="btn btn-sm btn-outline-primary w-100 rounded-pill mb-3 fw-bold">
                                <i class="bi bi-arrow-repeat me-1"></i> Cập nhật ngày & giá
                            </button>
                        </div>

                        <div class="summary-group">
                            <div class="summary-group-title">Thông tin phòng</div>
                            <div id="selectedRoomsList" class="mb-3">
                                <p class="text-muted italic small">Chưa có phòng nào được chọn</p>
                            </div>
                        </div>

                        <div class="summary-group">
                            <div class="summary-group-title">Thông tin khách hàng</div>
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Họ tên *</label>
                                <input type="text" name="full_name" class="form-control form-control-sm" 
                                       value="{{ auth()->check() ? auth()->user()->full_name : old('full_name') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Email *</label>
                                <input type="email" name="email" class="form-control form-control-sm" 
                                       value="{{ auth()->check() ? auth()->user()->email : old('email') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted mb-1">Số điện thoại *</label>
                                <input type="text" name="phone" class="form-control form-control-sm" 
                                       value="{{ auth()->check() ? auth()->user()->phone : old('phone') }}" required>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.toggle-selection');
    const qtySelectors = document.querySelectorAll('.room-qty-select');
    const selectedRoomsList = document.getElementById('selectedRoomsList');
    const totalDisplay = document.getElementById('totalDisplay');
    const btnBookNow = document.getElementById('btnBookNow');
    const roomInputsContainer = document.getElementById('roomInputsContainer');
    let nights = {{ $nights }};
    let currentDiscountPercent = 0;

    // Date Sync & Logic (Sync between Top bar and Sidebar)
    const sidebarCi = document.getElementById('sidebar_check_in');
    const sidebarCo = document.getElementById('sidebar_check_out');
    const sidebarNightsDisp = document.getElementById('sidebar_nights_display');
    const btnUpdateDates = document.getElementById('btnUpdateDates');

    function syncDateInputs(sourceId, targetId) {
        const source = document.getElementById(sourceId);
        const target = document.getElementById(targetId);
        if (source && target) {
            source.addEventListener('change', () => {
                target.value = source.value;
                // Trigger change on target to keep it consistent
                const event = new Event('change');
                target.dispatchEvent(event);
            });
        }
    }

    // Top to Sidebar sync
    syncDateInputs('search_check_in', 'sidebar_check_in');
    syncDateInputs('search_check_out', 'sidebar_check_out');
    // Sidebar to Top sync
    syncDateInputs('sidebar_check_in', 'search_check_in');
    syncDateInputs('sidebar_check_out', 'search_check_out');

    if (sidebarCi && sidebarCo) {
        sidebarCi.addEventListener('change', function() {
            sidebarCo.min = this.value;
            // Additional update display
            updateSidebarNights();
        });
        sidebarCo.addEventListener('change', updateSidebarNights);
    }

    function updateSidebarNights() {
        let ci = new Date(sidebarCi.value);
        let co = new Date(sidebarCo.value);
        let diff = Math.ceil((co - ci) / (1000 * 60 * 60 * 24));
        if (diff > 0) {
            if(sidebarNightsDisp) sidebarNightsDisp.textContent = diff + ' đêm';
            // update the global nights variable for pricing logic
            nights = diff; 
            updateSummary();
        }
    }

    if (btnUpdateDates) {
        btnUpdateDates.addEventListener('click', function() {
            // Submit the top bar search form to refresh availability
            const topForm = document.querySelector('.search-summary-bar');
            if (topForm) topForm.submit();
        });
    }

    const checkoutForm = document.getElementById('checkoutForm');
    const inputName = document.querySelector('input[name="full_name"]');
    const inputEmail = document.querySelector('input[name="email"]');
    const inputPhone = document.querySelector('input[name="phone"]');

    if (inputName) inputName.addEventListener('input', saveSelection);
    if (inputEmail) inputEmail.addEventListener('input', saveSelection);
    if (inputPhone) inputPhone.addEventListener('input', saveSelection);

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function() {
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
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ code: code })
        })
        .then(response => response.json())
        .then(data => {
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

    function updateSummary() {
        let subtotal = 0;
        let htmlSnippet = '';
        let hiddenInputs = '';
        let globalLimitExceeded = false;

        // Clear previous error messages
        document.querySelectorAll('.limit-error').forEach(el => el.remove());

        saveSelection();

        qtySelectors.forEach(select => {
            const qty = parseInt(select.value);
            const typeId = select.getAttribute('data-type-id');
            const typeName = select.getAttribute('data-type-name');
            const basePrice = parseFloat(select.getAttribute('data-price'));
            const maxAdults = parseInt(select.getAttribute('data-max-adults'));
            const maxChildren = parseInt(select.getAttribute('data-max-children'));
            const roomIds = JSON.parse(select.getAttribute('data-room-ids'));
            
            const guestRow = document.getElementById(`guestRow${typeId}`);
            const guestContainer = document.getElementById(`guestSelectors${typeId}`);
            let typeLimitExceeded = false;

            if (qty > 0) {
                guestRow.classList.remove('d-none');
                
                // Keep sync with UI: check if we need to add/remove containers
                let currentContainers = guestContainer.querySelectorAll('.guest-selector-item').length;
                if (currentContainers !== qty) {
                    let containerHtml = '';
                    for (let i = 1; i <= qty; i++) {
                        containerHtml += `
                            <div class="col-md-6 guest-selector-item">
                                <div class="guest-selector-card">
                                    <div class="guest-selector-title">
                                        <span>Phòng ${i}</span>
                                        <span class="text-muted small">Tiêu chuẩn: ${maxAdults} Người lớn${maxChildren > 0 ? `, ${maxChildren} Trẻ em` : ''}</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <label style="font-size: 0.65rem; color: #718096;">Người lớn</label>
                                            <input type="number" class="form-control form-control-sm guest-count adults-count" 
                                                   data-type-id="${typeId}" value="1" min="1" max="10">
                                        </div>
                                        <div class="col-4">
                                            <label style="font-size: 0.65rem; color: #718096;">Trẻ 0-5</label>
                                            <input type="number" class="form-control form-control-sm guest-count child-05-count" 
                                                   data-type-id="${typeId}" value="0" min="0" max="10">
                                        </div>
                                        <div class="col-4">
                                            <label style="font-size: 0.65rem; color: #718096;">Trẻ 6-11</label>
                                            <input type="number" class="form-control form-control-sm guest-count child-611-count" 
                                                   data-type-id="${typeId}" value="0" min="0" max="10">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    guestContainer.innerHTML = containerHtml;
                    
                    // Re-bind events to new inputs
                    guestContainer.querySelectorAll('.guest-count').forEach(s => {
                        s.addEventListener('input', updateSummary);
                    });
                }

                // Calculate price for these rooms
                const adultsArr = guestContainer.querySelectorAll('.adults-count');
                const child05Arr = guestContainer.querySelectorAll('.child-05-count');
                const child611Arr = guestContainer.querySelectorAll('.child-611-count');

                for (let i = 0; i < qty; i++) {
                    const adults = parseInt(adultsArr[i].value || 0);
                    const c05 = parseInt(child05Arr[i].value || 0);
                    const c611 = parseInt(child611Arr[i].value || 0);

                    // Logic mới theo yêu cầu:
                    const extraAdults = Math.max(0, adults - maxAdults);
                    
                    // Tổng số trẻ em để kiểm tra giới hạn +2
                    const totalChildren = c05 + c611;
                    const extraChildrenLimit = Math.max(0, totalChildren - maxChildren);

                    // Trẻ em 6-11 tính phí khi vượt giới hạn của phòng
                    const chargeableChildren = Math.max(0, c611 - maxChildren);

                    // Kiểm tra giới hạn +2
                    if (extraAdults > 2 || extraChildrenLimit > 2) {
                        typeLimitExceeded = true;
                    }

                    const extraAdultFee = extraAdults * (0.4 * basePrice);
                    // Phụ thu trẻ em: mỗi trẻ em (6-11) vượt hạn x 50% base_price
                    const childFee = chargeableChildren * (0.5 * basePrice);
                    
                    const roomPricePerNight = basePrice + extraAdultFee + childFee;
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
                                ${extraAdultFee > 0 ? `<div class="text-danger">Phụ thu người lớn: +${new Intl.NumberFormat('vi-VN').format(extraAdultFee)}đ</div>` : ''}
                                ${childFee > 0 ? `<div class="text-danger">Phụ thu trẻ em: +${new Intl.NumberFormat('vi-VN').format(childFee)}đ</div>` : ''}
                            </div>
                            <div class="d-flex justify-content-between small" style="font-size: 0.75rem;">
                                <span>${nights} đêm x ${new Intl.NumberFormat('vi-VN').format(roomPricePerNight)}đ</span>
                                <strong>${new Intl.NumberFormat('vi-VN').format(roomSubtotal)}đ</strong>
                            </div>
                        </div>
                    `;

                    hiddenInputs += `
                        <input type="hidden" name="room_ids[]" value="${roomIds[i]}">
                        <input type="hidden" name="adults[]" value="${adults}">
                        <input type="hidden" name="children_0_5[]" value="${c05}">
                        <input type="hidden" name="children_6_11[]" value="${c611}">
                    `;
                }
            } else {
                guestRow.classList.add('d-none');
                guestContainer.innerHTML = '';
            }

            if (typeLimitExceeded) {
                globalLimitExceeded = true;
                guestContainer.insertAdjacentHTML('afterbegin', `
                    <div class="col-12 limit-error">
                        <div class="alert alert-danger py-2 small mb-2">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Số lượng người vượt quá giới hạn của phòng, vui lòng đặt thêm phòng.
                        </div>
                    </div>
                `);
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

        if (htmlSnippet === '' || globalLimitExceeded) {
            if (htmlSnippet === '') {
                selectedRoomsList.innerHTML = '<p class="text-muted italic small">Chưa có phòng nào được chọn</p>';
            } else {
                selectedRoomsList.innerHTML = htmlSnippet;
            }
            btnBookNow.disabled = true;
            btnBookNow.classList.remove('active');
        } else {
            selectedRoomsList.innerHTML = htmlSnippet;
            btnBookNow.disabled = false;
            btnBookNow.classList.add('active');
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

        updateSummary();
    }

    // Call restore on load
    setTimeout(restoreSelection, 100);
});
</script>
@endsection
