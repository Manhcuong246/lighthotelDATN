@extends('layouts.admin')

@section('title', 'Tạo đơn đặt phòng')

@section('content')
<div class="container-fluid admin-page px-2 px-lg-3">
    <!-- Header -->
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary rounded-2">
                <span class="me-1">←</span> Quay lại
            </a>
            <h1 class="h4 fw-bold mb-0">➕ Tạo đơn đặt phòng cho khách</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-3" role="alert">
            <h6 class="alert-heading fw-bold mb-2">❌ Có lỗi xảy ra!</h6>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $bankConfigJson = json_encode([
            'bankId' => $hotelInfo->bank_id ?? '',
            'accountNo' => $hotelInfo->bank_account ?? '',
            'accountName' => $hotelInfo->bank_account_name ?? '',
            'template' => 'print'
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    @endphp
    <div id="bank-config" data-config="{{ $bankConfigJson }}" hidden></div>
    
    <form action="{{ route('admin.bookings.store') }}" method="POST" class="needs-validation" novalidate>
        @csrf

        <!-- Thông tin khách hàng -->
        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                <h5 class="mb-0 fw-bold">👤 Thông tin khách hàng</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-sm-4">
                        <label for="full_name" class="form-label small fw-bold text-muted mb-1">Họ tên *</label>
                        <input type="text" class="form-control form-control-sm rounded-2 @error('full_name') is-invalid @enderror"
                               id="full_name" name="full_name" value="{{ old('full_name') }}" required placeholder="Nguyễn Văn A" />
                        @error('full_name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-4">
                        <label for="email" class="form-label small fw-bold text-muted mb-1">Email *</label>
                        <input type="email" class="form-control form-control-sm rounded-2 @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}" required placeholder="email@example.com" />
                        @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-4">
                        <label for="phone" class="form-label small fw-bold text-muted mb-1">Số điện thoại</label>
                        <input type="text" class="form-control form-control-sm rounded-2 @error('phone') is-invalid @enderror"
                               id="phone" name="phone" value="{{ old('phone') }}" placeholder="0901234567" />
                        @error('phone')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin đặt phòng -->
        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                <h5 class="mb-0 fw-bold">🏨 Thông tin đặt phòng</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-sm-4">
                        <label for="room_id" class="form-label small fw-bold text-muted mb-1">Số phòng *</label>
                        <select class="form-select form-select-sm rounded-2 @error('room_id') is-invalid @enderror"
                                id="room_id" name="room_id" required>
                            <option value="">-- Chọn số phòng --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}"
                                        data-price="{{ $room->base_price }}"
                                        data-max-guests="{{ $room->max_guests }}"
                                        {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                    Phòng {{ $room->room_number }} - {{ $room->roomType->name ?? 'Không xác định' }} - {{ number_format($room->base_price, 0, ',', '.') }}đ/đêm (Tối đa {{ $room->max_guests }} khách)
                                </option>
                            @endforeach
                        </select>
                        @error('room_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label for="check_in" class="form-label small fw-bold text-muted mb-1">Check-in *</label>
                        <input type="date" class="form-control form-control-sm rounded-2 @error('check_in') is-invalid @enderror"
                               id="check_in" name="check_in" value="{{ old('check_in') }}" required min="{{ date('Y-m-d') }}" />
                        @error('check_in')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label for="check_out" class="form-label small fw-bold text-muted mb-1">Check-out *</label>
                        <input type="date" class="form-control form-control-sm rounded-2 @error('check_out') is-invalid @enderror"
                               id="check_out" name="check_out" value="{{ old('check_out') }}" required />
                        @error('check_out')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label for="guests" class="form-label small fw-bold text-muted mb-1">Số khách *</label>
                        <input type="number" class="form-control form-control-sm rounded-2 @error('guests') is-invalid @enderror"
                               id="guests" name="guests" min="1" value="{{ old('guests', 1) }}" required />
                        @error('guests')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label for="status" class="form-label small fw-bold text-muted mb-1">Trạng thái *</label>
                        <select class="form-select form-select-sm rounded-2 @error('status') is-invalid @enderror"
                                id="status" name="status" required>
                            <option value="confirmed" selected>✓ Đã xác nhận</option>
                        </select>
                        @error('status')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>

                <!-- Preview tính giá -->
                <div class="mt-3 pt-3 border-top" id="price-preview" style="display: none;">
                    <div class="row g-2 align-items-center">
                        <div class="col-sm-3">
                            <small class="text-muted">Số đêm:</small>
                            <strong id="nights-count" class="text-primary">0</strong>
                        </div>
                        <div class="col-sm-3">
                            <small class="text-muted">Giá cơ bản/đêm:</small>
                            <strong id="base-price" class="text-dark">0đ</strong>
                        </div>
                        <div class="col-sm-3">
                            <small class="text-muted">Tổng tiền dự kiến:</small>
                            <strong id="total-price" class="text-success fs-5">0đ</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin thanh toán -->
        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                <h5 class="mb-0 fw-bold">💳 Thông tin thanh toán</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-sm-4">
                        <label for="payment_method" class="form-label small fw-bold text-muted mb-1">Phương thức thanh toán *</label>
                        <select class="form-select form-select-sm rounded-2 @error('payment_method') is-invalid @enderror"
                                id="payment_method" name="payment_method" required>
                            <option value="">-- Chọn phương thức --</option>
                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>💵 Tiền mặt</option>
                            <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>🏦 Chuyển khoản</option>
                            <option value="credit_card" {{ old('payment_method') == 'credit_card' ? 'selected' : '' }}>💳 Thẻ tín dụng</option>
                            <option value="momo" {{ old('payment_method') == 'momo' ? 'selected' : '' }}>📱 MoMo</option>
                            <option value="zalopay" {{ old('payment_method') == 'zalopay' ? 'selected' : '' }}>📲 ZaloPay</option>
                        </select>
                        @error('payment_method')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-4">
                        <label for="payment_status" class="form-label small fw-bold text-muted mb-1">Trạng thái thanh toán *</label>
                        <select class="form-select form-select-sm rounded-2 @error('payment_status') is-invalid @enderror"
                                id="payment_status" name="payment_status" required>
                            <option value="pending" {{ old('payment_status') == 'pending' ? 'selected' : '' }}>⏳ Chưa thanh toán</option>
                            <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>✓ Đã thanh toán</option>
                            <option value="partial" {{ old('payment_status') == 'partial' ? 'selected' : '' }}>💰 Đặt cọc</option>
                        </select>
                        @error('payment_status')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-4">
                        <label for="amount_paid" class="form-label small fw-bold text-muted mb-1">Số tiền đã thanh toán</label>
                        <input type="number" class="form-control form-control-sm rounded-2 @error('amount_paid') is-invalid @enderror"
                               id="amount_paid" name="amount_paid" value="{{ old('amount_paid', 0) }}" min="0" placeholder="0" />
                        <small class="text-muted">Để trống nếu chưa thanh toán</small>
                        @error('amount_paid')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-sm-12">
                        <label for="payment_note" class="form-label small fw-bold text-muted mb-1">Ghi chú thanh toán</label>
                        <textarea class="form-control form-control-sm rounded-2 @error('payment_note') is-invalid @enderror"
                                  id="payment_note" name="payment_note" rows="2" placeholder="VD: Khách đặt cọc 50%, thanh toán nốt khi nhận phòng...">{{ old('payment_note') }}</textarea>
                        @error('payment_note')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="mt-3 pt-3 border-top" id="qr-section" style="display: none;">
                    @if($hotelInfo && $hotelInfo->bank_id && $hotelInfo->bank_account)
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-2">📱 Quét mã QR để thanh toán</label>
                            <div id="qr-container" class="bg-white p-3 rounded-2 border text-center">
                                <img id="qr-image" src="" alt="QR Code" class="img-fluid" style="max-width: 250px;">
                                <div class="mt-2">
                                    <small class="text-muted d-block">Số tiền: <strong id="qr-amount" class="text-success">0đ</strong></small>
                                    <small class="text-muted d-block">Nội dung: <strong id="qr-content">Thanh toan don phong</strong></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-2">🏦 Thông tin chuyển khoản</label>
                            <div class="bg-light p-3 rounded-2">
                                <p class="mb-1"><strong>Ngân hàng:</strong> <span id="bank-name">{{ $hotelInfo->bank_id ?? 'Chưa cấu hình' }}</span></p>
                                <p class="mb-1"><strong>Số tài khoản:</strong> <span id="account-number">{{ $hotelInfo->bank_account ?? 'Chưa cấu hình' }}</span></p>
                                <p class="mb-1"><strong>Chủ tài khoản:</strong> <span id="account-name">{{ $hotelInfo->bank_account_name ?? 'Chưa cấu hình' }}</span></p>
                                <p class="mb-0"><strong>Số tiền:</strong> <span id="bank-amount" class="text-success fw-bold">0đ</span></p>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="generateQR()">
                                    <i class="bi bi-arrow-clockwise"></i> Tạo lại mã QR
                                </button>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Chưa cấu hình thông tin ngân hàng. Vui lòng cấu hình trong phần Cài đặt.
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary rounded-2 fw-bold px-4">
                💾 Tạo đơn đặt phòng
            </button>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary rounded-2 fw-bold px-4">
                ❌ Hủy
            </a>
        </div>
    </form>
</div>

<style>
    .bg-gradient {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .rounded-3 {
        border-radius: 12px !important;
    }

    .rounded-2 {
        border-radius: 8px !important;
    }

    .form-control, .form-select, .form-control-sm, .form-select-sm {
        border: 1px solid #dee2e6;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus, .form-select:focus, .form-control-sm:focus, .form-select-sm:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    .card {
        transition: box-shadow 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    }

    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }
</style>

<script>
const roomSelect = document.getElementById('room_id');
const checkInInput = document.getElementById('check_in');
const checkOutInput = document.getElementById('check_out');
const guestsInput = document.getElementById('guests');
const pricePreview = document.getElementById('price-preview');

const paymentMethodSelect = document.getElementById('payment_method');
const paymentStatusSelect = document.getElementById('payment_status');
const amountPaidInput = document.getElementById('amount_paid');
const qrSection = document.getElementById('qr-section');

// Bank config passed via data attribute to avoid linter false positives
const bankConfigEl = document.getElementById('bank-config');
const bankConfig = bankConfigEl ? JSON.parse(bankConfigEl.dataset.config) : {};

/* =========================
PRICE PREVIEW
========================= */

function updatePricePreview() {

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];

    const checkIn = checkInInput.value;
    const checkOut = checkOutInput.value;

    if (!selectedOption.value || !checkIn || !checkOut) {
        pricePreview.style.display = 'none';
        return;
    }

    const basePrice = parseFloat(selectedOption.dataset.price) || 0;
    const maxGuests = parseInt(selectedOption.getAttribute('data-max-guests')) || 1;

    guestsInput.max = maxGuests;

    if (parseInt(guestsInput.value) > maxGuests) {
        guestsInput.value = maxGuests;
    }

    const startDate = new Date(checkIn);
    const endDate = new Date(checkOut);

    const nights = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

    if (nights <= 0) {
        pricePreview.style.display = 'none';
        return;
    }

    const total = basePrice * nights;

    document.getElementById('nights-count').textContent = nights;
    document.getElementById('base-price').textContent =
        new Intl.NumberFormat('vi-VN').format(basePrice) + 'đ';

    document.getElementById('total-price').textContent =
        new Intl.NumberFormat('vi-VN').format(total) + 'đ';

    pricePreview.style.display = 'block';
}


/* =========================
GET TOTAL PRICE
========================= */

function getTotalPrice() {

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];

    const checkIn = checkInInput.value;
    const checkOut = checkOutInput.value;

    if (!selectedOption.value || !checkIn || !checkOut) return 0;

    const basePrice = parseFloat(selectedOption.dataset.price) || 0;

    const startDate = new Date(checkIn);
    const endDate = new Date(checkOut);

    const nights = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

    if (nights <= 0) return 0;

    return basePrice * nights;
}


/* =========================
GENERATE QR
========================= */

function generateQR() {

    if (!roomSelect.value) {
        qrSection.style.display = 'none';
        return;
    }

    const method = paymentMethodSelect.value;
    const paidAmount = parseFloat(amountPaidInput.value) || 0;

    const totalPrice = getTotalPrice();

    const displayAmount = paidAmount > 0 ? paidAmount : totalPrice;

    if (method !== 'bank_transfer' || displayAmount <= 0) {
        qrSection.style.display = 'none';
        return;
    }

    if (!bankConfig.bankId || !bankConfig.accountNo) {
        qrSection.style.display = 'block';
        return;
    }

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];

    const roomNumber = selectedOption.text.split('-')[0].trim();

    const description = roomNumber + '-' + Date.now();

    const qrUrl =
        `https://img.vietqr.io/image/${bankConfig.bankId}-${bankConfig.accountNo}-${bankConfig.template}.png` +
        `?amount=${displayAmount}` +
        `&addInfo=${encodeURIComponent(description)}` +
        `&accountName=${encodeURIComponent(bankConfig.accountName)}`;

    document.getElementById('qr-image').src = qrUrl;

    document.getElementById('qr-amount').textContent =
        new Intl.NumberFormat('vi-VN').format(displayAmount) + 'đ';

    document.getElementById('qr-content').textContent = description;

    document.getElementById('bank-amount').textContent =
        new Intl.NumberFormat('vi-VN').format(displayAmount) + 'đ';

    qrSection.style.display = 'block';
}


/* =========================
EVENTS
========================= */

roomSelect.addEventListener('change', function () {
    updatePricePreview();
    setTimeout(generateQR, 100);
});

checkInInput.addEventListener('change', function () {

    checkOutInput.min = this.value;

    if (checkOutInput.value && checkOutInput.value <= this.value) {
        checkOutInput.value = '';
    }

    updatePricePreview();

    setTimeout(generateQR, 100);
});

checkOutInput.addEventListener('change', function () {

    updatePricePreview();

    setTimeout(generateQR, 100);
});

paymentMethodSelect.addEventListener('change', generateQR);

paymentStatusSelect.addEventListener('change', generateQR);

amountPaidInput.addEventListener('input', generateQR);


/* =========================
BOOTSTRAP VALIDATION
========================= */

(function () {

    'use strict';

    window.addEventListener('load', function () {

        const forms = document.querySelectorAll('.needs-validation');

        Array.prototype.slice.call(forms).forEach(function (form) {

            form.addEventListener('submit', function (event) {

                if (!form.checkValidity()) {

                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');

            }, false);

        });

    }, false);

})();
</script>
@endsection
