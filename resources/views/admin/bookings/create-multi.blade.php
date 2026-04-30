@extends('layouts.admin')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Tạo đặt phòng nhiều phòng</h1>
            <div class="text-muted small">Chọn ngày, chọn số lượng phòng theo loại, nhập thông tin khách và phương thức thanh toán.</div>
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-sm btn-admin-icon rounded-2" title="Quay lại">
            <i class="bi bi-arrow-left"></i>
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    <!-- Step 1: Chọn Ngày -->
    <div class="card shadow-sm border-0 rounded-3 mb-4" id="step1">
        <div class="card-header bg-white border-0 rounded-top-3">
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="h6 mb-0 fw-bold">Bước 1: Chọn ngày</h2>
                <span class="badge bg-light text-muted border">Bắt buộc</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Ngày nhận phòng *</label>
                    <input type="date" class="form-control" id="check_in" min="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Ngày trả phòng *</label>
                    <input type="date" class="form-control" id="check_out">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-2" onclick="checkAvailability()">
                        <i class="bi bi-search"></i>
                        <span>Tìm phòng trống</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Chọn Phòng và Thông Tin -->
    <form id="bookingForm" action="{{ route('admin.bookings.store-multi') }}" method="POST">
        @csrf
        <input type="hidden" name="debug_form_version" value="v3-direct-submit">
        <input type="hidden" name="check_in" id="form_check_in">
        <input type="hidden" name="check_out" id="form_check_out">
        <input type="hidden" name="room_id" id="form_room_id">
        <input type="hidden" name="adults" id="form_adults" value="1">
        <input type="hidden" name="children" id="form_children" value="0">

        <!-- Danh sách phòng trống -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white border-0 rounded-top-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="h6 mb-0 fw-bold">Bước 2: Chọn phòng</h2>
                    <span class="badge bg-light text-muted border">Theo loại phòng</span>
                </div>
            </div>
            <div class="card-body p-3">
                <div id="availableRooms" class="d-flex flex-column">
                    <!-- Rooms will be loaded here via AJAX -->
                </div>
            </div>
        </div>

        <!-- Thông tin khách hàng -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white border-0 rounded-top-3">
                <h2 class="h6 mb-0 fw-bold">Thông tin khách hàng</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Họ tên *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin chi tiết khách hàng -->
        <div id="guestFormsContainer" style="display: none;"></div>

        <!-- Mã giảm giá và thanh toán -->
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-white border-0 rounded-top-3">
                        <h2 class="h6 mb-0 fw-bold">Mã giảm giá</h2>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" name="coupon_code" id="coupon_code" class="form-control" placeholder="Nhập mã giảm giá">
                            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2" onclick="applyCoupon()">
                                <i class="bi bi-ticket-perforated"></i>
                                <span>Áp dụng</span>
                            </button>
                        </div>
                        <div id="couponMessage" class="mt-2 small"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-white border-0 rounded-top-3">
                        <h2 class="h6 mb-0 fw-bold">Thanh toán</h2>
                    </div>
                    <div class="card-body">
                        <!-- Payment Method Radio -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phương thức thanh toán *</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" checked onchange="togglePaymentMethod()">
                                    <label class="form-check-label" for="payment_cash">
                                        Tiền mặt
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_vnpay" value="vnpay" onchange="togglePaymentMethod()">
                                    <label class="form-check-label" for="payment_vnpay">
                                        VNPay (email SMTP + link có chữ ký)
                                    </label>
                                </div>
                            </div>
                            <p class="small text-muted mb-0 mt-2">Chuyển khoản không dùng khi tạo đơn hộ; nếu khách CK sau, cập nhật tại chi tiết đơn.</p>
                        </div>

                        <!-- Cash Payment Status (show when cash selected) -->
                        <div id="cashPaymentStatus" class="mb-3">
                            <label class="form-label small fw-bold">Trạng thái thanh toán *</label>
                            <select name="payment_status" id="cash_status" class="form-select" onchange="toggleCashAmount()">
                                <option value="pending">⏳ Chưa thanh toán</option>
                                <option value="paid">✅ Đã thanh toán</option>
                            </select>
                        </div>

                        <!-- Cash Amount Paid (show when cash paid selected) -->
                        <div id="cashAmountDiv" class="mb-3" style="display: none;">
                            <label class="form-label small fw-bold">Số tiền đã thu *</label>
                            <input type="number" name="amount_paid" id="cash_amount" class="form-control" min="0" value="0">
                            <small class="text-muted">Nhập số tiền đã thu từ khách</small>
                        </div>

                        <div id="vnpayInfo" class="alert alert-primary mb-0 small" style="display: none;">
                            <div class="fw-bold mb-1">Thanh toán VNPay</div>
                            <p class="small mb-0">Sau khi tạo đơn, hệ thống gửi email cho khách (nếu đã cấu hình SMTP) kèm link có chữ ký. Thời hạn ~{{ (int) config('vnpay.transaction_expire_minutes', 15) }} phút trên VNPay tính từ lúc khách <strong>bấm link</strong> trong email. Trang hướng dẫn admin cũng hiển thị cùng link để sao chép.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin chi tiết khách hàng (đã bỏ, chỉ nhập người đại diện) -->
        <!-- Đã chuyển sang chỉ nhập thông tin người đại diện ở trên -->

        <!-- Tổng tiền và xác nhận -->
        <div class="card shadow-sm border-0 rounded-3 mb-4 bg-light">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tổng tiền phòng:</span>
                            <strong id="subtotalAmount">0đ</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-success" id="discountRow" style="display: none !important;">
                            <span>Giảm giá:</span>
                            <strong id="discountAmount">-0đ</strong>
                        </div>
                        <div class="d-flex justify-content-between fs-5 fw-bold text-primary">
                            <span>Tổng cộng:</span>
                            <span id="totalAmount">0đ</span>
                        </div>
                        <input type="hidden" name="total_price" id="total_price_input">
                        <input type="hidden" name="discount_amount" id="discount_amount_input" value="0">
                    </div>



                    <div class="col-md-4 text-end">
                        <button type="submit" class="btn btn-success btn-lg px-4 d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-check2-circle"></i>
                            <span>Tạo đặt phòng</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .room-card { transition: all 0.2s ease; border: 2px solid #dee2e6; }
    .room-card:hover { border-color: #0d6efd; transform: translateY(-1px); }
    .room-card.selected { border-color: #198754; background: #f8fff9; }
</style>

<script>
const __BP = @json(config('booking.pricing'));
/** Đồng bộ App\Support\RoomOccupancyPricing */
function bookingPriceBreakdown(base, adults, c05, c611, adultRate, childRate, stdCap, maxCap) {
    const _stdCap = Number(stdCap ?? __BP.standard_capacity) || 3;
    const _maxCap = Number(maxCap ?? __BP.max_capacity) || 6;
    const maxC05 = Number(__BP.max_children_05) || 3;
    const aRate = (adultRate != null) ? Number(adultRate) : (Number(__BP.default_adult_surcharge_rate) || 0.25);
    const cRate = (childRate != null) ? Number(childRate) : (Number(__BP.default_child_surcharge_rate) || 0.125);
    const total = adults + c611 + c05;
    const billableSlots = Math.max(0, _stdCap - c05);
    const extraAdults = Math.max(0, adults - billableSlots);
    const remainingSlots = Math.max(0, billableSlots - adults);
    const extraChildren = Math.max(0, c611 - remainingSlots);
    const adultFee = extraAdults * aRate * base;
    const childFee = extraChildren * cRate * base;
    const surcharge = adultFee + childFee;
    const perNight = base + surcharge;
    // Không giới hạn số khách - chỉ tính phụ thu khi vượt tiêu chuẩn
    return { perNight, surcharge, adultFee, childFee, extraAdults, extraChildren, effective: total, stdCap: _stdCap, maxCap: _maxCap, maxC05, allowed: true };
}

let availableRoomsData = [];
let selectedRooms = {};
let nights = 0;
let guestData = {}; // Source of truth for guest name/cccd inputs

function sanitizeRoomTypeKey(name) {
    return name.toString().toLowerCase().trim()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function setGuestValue(roomTypeKey, index, field, value) {
    console.log('setGuestValue called:', { roomTypeKey, index, field, value });
    if (!guestData[roomTypeKey]) {
        guestData[roomTypeKey] = [];
    }

    if (!guestData[roomTypeKey][index]) {
        guestData[roomTypeKey][index] = {};
    }

    guestData[roomTypeKey][index][field] = value;
    console.log('guestData updated:', guestData);
}

// Set min checkout date when checkin changes
document.getElementById('check_in').addEventListener('change', function() {
    document.getElementById('check_out').min = this.value;
});

function checkAvailability() {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;

    if (!checkIn || !checkOut) {
        alert('Vui lòng chọn ngày nhận và trả phòng.');
        return;
    }

    if (checkOut <= checkIn) {
        alert('Ngày trả phòng phải sau ngày nhận phòng.');
        return;
    }

    // Calculate nights
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

    // Set form values
    document.getElementById('form_check_in').value = checkIn;
    document.getElementById('form_check_out').value = checkOut;

    // Show form
    document.getElementById('bookingForm').style.display = 'block';

    // Load available rooms
    fetch(`{{ route('admin.bookings.check-availability') }}?check_in=${checkIn}&check_out=${checkOut}`)
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            availableRoomsData = data.rooms;
            renderAvailableRooms(data.rooms);
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert('Lỗi khi tải danh sách phòng: ' + err.message);
        });
}

function renderAvailableRooms(rooms) {
    const container = document.getElementById('availableRooms');
    if (rooms.length === 0) {
        container.innerHTML = '<div class="col-12"><div class="alert alert-warning">Không có phòng trống trong khoảng thời gian này</div></div>';
        return;
    }

    const placeholderSvg = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22250%22 viewBox=%220 0 400 250%22%3E%3Crect fill=%22%231e293b%22 width=%22400%22 height=%22250%22/%3E%3Ctext fill=%22%2394a3b8%22 font-size=%2218%22 x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22%3ELight Hotel%3C/text%3E%3C/svg%3E";

    container.innerHTML = rooms.map(roomType => {
        const roomImage = roomType.image || placeholderSvg;
        return `
        <div class="col-12 mb-3" id="roomWrapper_${roomType.room_type_id}">
            <!-- Main Room Card -->
            <div class="room-card card border-0 shadow-sm overflow-hidden" id="roomCard_${roomType.room_type_id}">
                <div class="row g-0">
                    <!-- Room Image -->
                    <div class="col-md-2 col-sm-3">
                        <div class="position-relative h-100 w-100">
                            <img src="${roomImage}"
                                 class="w-100 d-block rounded-start" style="object-fit: cover; aspect-ratio: 4/3; min-height: 140px; height: 100%;"
                                 alt="${roomType.name}">
                            <span class="position-absolute top-0 start-0 m-1 badge bg-success small" style="font-size: 0.7rem; z-index: 1;">
                                Còn ${roomType.available_count}
                            </span>
                        </div>
                    </div>
                    <!-- Room Details -->
                    <div class="col-md-7 col-sm-6 p-3">
                        <h6 class="fw-bold mb-1">${roomType.name}</h6>
                        <div class="text-muted small mb-1">
                            <i class="bi bi-aspect-ratio me-1"></i>${roomType.area || 30} m² ·
                            <i class="bi bi-people me-1"></i>Tối đa ${roomType.max_occupancy ?? 6} người
                        </div>
                        <p class="text-muted small mb-0" style="font-size: 0.85rem;">${roomType.description ? roomType.description.substring(0, 80) + '...' : 'Phòng tiêu chuẩn với đầy đủ tiện nghi'}</p>
                    </div>
                    <!-- Price & Quantity -->
                    <div class="col-md-3 col-sm-3 p-2 bg-light border-start d-flex flex-column justify-content-center">
                        <div class="text-end mb-2">
                            <div class="text-muted small">Giá/đêm</div>
                            <div class="h5 fw-bold text-primary mb-0">${formatMoney(roomType.base_price)}</div>
                        </div>

                        <!-- Quantity Selector -->
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Số phòng</label>
                            <div class="d-flex align-items-center gap-1 justify-content-end">
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2"
                                        onclick="changeRoomQuantity('${roomType.room_type_id}', -1, ${roomType.available_count}, ${roomType.base_price}, '${roomType.name}', ${roomType.adult_capacity || 2}, ${roomType.child_capacity || 0})"
                                        id="qtyMinus_${roomType.room_type_id}" style="font-size: 0.8rem;">−</button>
                                <input type="number" class="form-control form-control-sm text-center room-quantity py-0"
                                       id="qty_${roomType.room_type_id}"
                                       data-room-type="${roomType.room_type_id}"
                                       data-price="${roomType.base_price}"
                                       data-name="${roomType.name}"
                                       data-adult-capacity="${roomType.adult_capacity || 2}"
                                       data-child-capacity="${roomType.child_capacity || 0}"
                                       value="0" min="0" max="${roomType.available_count}" readonly style="width: 40px; font-size: 0.9rem;">
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2"
                                        onclick="changeRoomQuantity('${roomType.room_type_id}', 1, ${roomType.available_count}, ${roomType.base_price}, '${roomType.name}', ${roomType.adult_capacity || 2}, ${roomType.child_capacity || 0})"
                                        id="qtyPlus_${roomType.room_type_id}" style="font-size: 0.8rem;">+</button>
                            </div>
                        </div>

                        <!-- Subtotal -->
                        <div class="mt-1 text-end room-subtotal" id="subtotal_${roomType.room_type_id}" style="display: none;">
                            <small class="text-muted subtotal-text" style="font-size: 0.75rem;">-</small>
                            <strong class="text-success d-block subtotal-amount">-</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Individual Room Forms Container -->
            <div id="roomForms_${roomType.room_type_id}" class="room-forms-container mt-2" style="display: none;">
                <!-- Forms will be generated here dynamically -->
            </div>
        </div>
    `}).join('');

    // Add event listeners for guest inputs
    document.querySelectorAll('.room-adults, .room-children-0-5, .room-children-6-11').forEach(input => {
        input.addEventListener('change', function() {
            const roomTypeId = this.dataset.roomType;
            const roomIndex = this.dataset.roomIndex;
            updateSelectedRoomData(roomTypeId, roomIndex);
        });
    });
}

// Change room quantity with +/- buttons
function changeRoomQuantity(roomTypeId, delta, maxAvailable, price, name, adultCapacity, childCapacity) {
    const qtyInput = document.getElementById(`qty_${roomTypeId}`);
    const currentQty = parseInt(qtyInput.value) || 0;
    const newQty = currentQty + delta;

    if (newQty < 0 || newQty > maxAvailable) return;

    qtyInput.value = newQty;
    updateRoomCardState(roomTypeId, newQty, price, name, adultCapacity, childCapacity);
}

// Update room card visual state and generate individual room forms
function updateRoomCardState(roomTypeId, quantity, price, name, adultCapacity, childCapacity) {
    const card = document.getElementById(`roomCard_${roomTypeId}`);
    const roomFormsContainer = document.getElementById(`roomForms_${roomTypeId}`);
    const subtotalDiv = document.getElementById(`subtotal_${roomTypeId}`);
    const minusBtn = document.getElementById(`qtyMinus_${roomTypeId}`);

    if (quantity > 0) {
        card.classList.add('selected');
        card.style.border = '2px solid #198754';
        roomFormsContainer.style.display = 'block';

        const subtotal = price * quantity * nights;
        subtotalDiv.querySelector('.subtotal-text').textContent = `${quantity} phòng × ${nights} đêm`;
        subtotalDiv.querySelector('.subtotal-amount').textContent = formatMoney(subtotal);
        subtotalDiv.style.display = 'block';

        generateRoomForms(roomTypeId, quantity, price, name, adultCapacity, childCapacity);

        const isFirstTimeSelect = !selectedRooms[roomTypeId];

        if (!selectedRooms[roomTypeId]) {
            selectedRooms[roomTypeId] = {
                room_type_id: roomTypeId,
                room_type_key: sanitizeRoomTypeKey(name),
                room_type_label: name,
                quantity: quantity,
                base_price: price,
                name: name,
                adult_capacity: adultCapacity,
                child_capacity: childCapacity,
                rooms: []
            };
        }
        selectedRooms[roomTypeId].quantity = quantity;
        selectedRooms[roomTypeId].adult_capacity = adultCapacity;
        selectedRooms[roomTypeId].child_capacity = childCapacity;

        let hasNewRoom = isFirstTimeSelect;
        for (let i = 0; i < quantity; i++) {
            if (!selectedRooms[roomTypeId].rooms[i]) {
                selectedRooms[roomTypeId].rooms[i] = {
                    adults: 1,
                    children_0_5: 0,
                    children_6_11: 0,
                    price_per_night: price,
                    extra_adult_fee: 0,
                    child_fee: 0
                };
                hasNewRoom = true;
            }
        }
        // Trim excess rooms if quantity decreased
        selectedRooms[roomTypeId].rooms = selectedRooms[roomTypeId].rooms.slice(0, quantity);

        // Gọi generateGuestDetailsForm nếu có phòng mới
        if (hasNewRoom) {
            generateGuestDetailsForm();
        }

    } else {
        card.classList.remove('selected');
        card.style.border = '';
        roomFormsContainer.style.display = 'none';
        roomFormsContainer.innerHTML = '';
        subtotalDiv.style.display = 'none';
        delete selectedRooms[roomTypeId];

        // Xóa form khách của phòng này
        const rowsContainer = document.querySelector('#guestFormsContainer .guest-rows');
        if (rowsContainer) {
            const roomForms = rowsContainer.querySelectorAll(`[data-room-id^="${sanitizeRoomTypeKey(name)}_"]`);
            roomForms.forEach(form => form.remove());
        }
    }

    // Update minus button state
    minusBtn.disabled = quantity <= 0;

    calculateTotal();
}

// Generate individual room forms
function generateRoomForms(roomTypeId, quantity, price, name, adultCapacity, childCapacity) {
    const container = document.getElementById(`roomForms_${roomTypeId}`);
    const currentForms = container.querySelectorAll('.individual-room-form').length;

    while (container.querySelectorAll('.individual-room-form').length > quantity) {
        container.lastElementChild.remove();
    }

    for (let i = currentForms; i < quantity; i++) {
        const roomIndex = i;
        const formHtml = `
            <div class="individual-room-form card border-0 shadow-sm mb-2" id="roomForm_${roomTypeId}_${roomIndex}">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0 text-primary">Phòng ${roomIndex + 1}</h6>
                        <span class="text-muted small">TC: 3 (tính cả trẻ 0–5); trẻ 0–5 miễn phụ thu · Tối đa 6 · Tối đa 3 trẻ 0–5</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1" style="font-size: 0.75rem;">Người lớn</label>
                            <input type="number" class="form-control form-control-sm room-adults"
                                   id="adults_${roomTypeId}_${roomIndex}"
                                   data-room-type="${roomTypeId}"
                                   data-room-index="${roomIndex}"
                                   min="1" max="6" value="1"
                                   style="font-size: 0.85rem;" onchange="updateRoomGuestData('${roomTypeId}', ${roomIndex})">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1" style="font-size: 0.75rem;">Trẻ 0–5 tuổi</label>
                            <input type="number" class="form-control form-control-sm room-children-0-5"
                                   id="children05_${roomTypeId}_${roomIndex}"
                                   data-room-type="${roomTypeId}"
                                   data-room-index="${roomIndex}"
                                   min="0" max="3" value="0"
                                   style="font-size: 0.85rem;" onchange="updateRoomGuestData('${roomTypeId}', ${roomIndex})">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1" style="font-size: 0.75rem;" title="50% giá phòng/đêm mỗi em">Trẻ 6–11 tuổi</label>
                            <input type="number" class="form-control form-control-sm room-children-6-11"
                                   id="children611_${roomTypeId}_${roomIndex}"
                                   data-room-type="${roomTypeId}"
                                   data-room-index="${roomIndex}"
                                   min="0" max="5" value="0"
                                   style="font-size: 0.85rem;" onchange="updateRoomGuestData('${roomTypeId}', ${roomIndex})">
                        </div>
                    </div>
                    <div id="feeDisplay_${roomTypeId}_${roomIndex}" class="mt-2"></div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', formHtml);
    }
}

// Update guest data for a specific room and recalculate price
function updateRoomGuestData(roomTypeId, roomIndex) {
    if (!selectedRooms[roomTypeId]) return;

    const adults = parseInt(document.getElementById(`adults_${roomTypeId}_${roomIndex}`).value) || 1;
    const children05 = parseInt(document.getElementById(`children05_${roomTypeId}_${roomIndex}`).value) || 0;
    const children611 = parseInt(document.getElementById(`children611_${roomTypeId}_${roomIndex}`).value) || 0;

    selectedRooms[roomTypeId].rooms[roomIndex].adults = adults;
    selectedRooms[roomTypeId].rooms[roomIndex].children_0_5 = children05;
    selectedRooms[roomTypeId].rooms[roomIndex].children_6_11 = children611;

    // Update price details for this room
    updateRoomPriceDetails(roomTypeId, roomIndex, adults, children05, children611);

    // Recalculate total
    calculateTotal();

    // Không gọi generateGuestDetailsForm ở đây để tránh mất dữ liệu đã nhập
    // generateGuestDetailsForm chỉ được gọi khi thêm/xóa phòng
}

// Calculate price details with surcharge for extra guests
function updateRoomPriceDetails(roomTypeId, roomIndex, adults, children05, children611) {
    const roomData = selectedRooms[roomTypeId];
    const room = availableRoomsData.find(r => r.room_type_id == roomTypeId);
    if (!room) return;

    const basePrice = parseFloat(room.base_price) || 0;
    const aRate = room.adult_surcharge_rate ?? null;
    const cRate = room.child_surcharge_rate ?? null;
    const br = bookingPriceBreakdown(basePrice, adults, children05, children611, aRate, cRate, room.standard_capacity, room.max_occupancy);

    // Không hiển thị cảnh báo giới hạn sức chứa - chỉ tính phụ thu
    const limitError = document.getElementById(`limitError_${roomTypeId}_${roomIndex}`);
    if (limitError) {
        limitError.remove();
    }

    roomData.rooms[roomIndex].extra_adult_fee = br.adultFee;
    roomData.rooms[roomIndex].child_fee = br.childFee;
    roomData.rooms[roomIndex].price_per_night = br.perNight;

    const feeDisplay = document.getElementById(`feeDisplay_${roomTypeId}_${roomIndex}`);
    if (feeDisplay) {
        let feeHtml = `<div class="text-muted small mb-1">Tổng khách trong phòng: <strong>${br.effective}</strong> (NL: ${adults}, trẻ 6–11: ${children611}, trẻ 0–5: ${children05}). Tiêu chuẩn: <strong>${br.stdCap}</strong>, tối đa: <strong>${br.maxCap}</strong>. Trẻ 0–5 tối đa: <strong>${br.maxC05}</strong>.</div>`;
        if (br.adultFee > 0) {
            feeHtml += `<div class="text-danger small">Phụ thu NL vượt TC (${br.extraAdults} người): +${formatMoney(br.adultFee)}/đêm</div>`;
        }
        if (br.childFee > 0) {
            feeHtml += `<div class="text-danger small">Phụ thu trẻ 6–11 vượt TC (${br.extraChildren} em): +${formatMoney(br.childFee)}/đêm</div>`;
        }
        feeDisplay.innerHTML = feeHtml;
    }

    updateRoomCardSubtotal(roomTypeId);
}

// Update room card subtotal display
function updateRoomCardSubtotal(roomTypeId) {
    const roomType = selectedRooms[roomTypeId];
    if (!roomType) return;

    const room = availableRoomsData.find(r => r.room_type_id == roomTypeId);
    if (!room) return;

    const basePrice = parseFloat(room.base_price) || 0;
    const aRate = room.adult_surcharge_rate ?? null;
    const cRate = room.child_surcharge_rate ?? null;

    let typeSubtotal = 0;
    let typeExtraFees = 0;

    roomType.rooms.forEach(roomData => {
        const adults = roomData.adults || 1;
        const children05 = roomData.children_0_5 || 0;
        const children611 = roomData.children_6_11 || 0;

        const br = bookingPriceBreakdown(basePrice, adults, children05, children611, aRate, cRate, room.standard_capacity, room.max_occupancy);
        const roomPricePerNight = br.perNight;
        typeSubtotal += roomPricePerNight * nights;
        typeExtraFees += br.surcharge * nights;
    });

    // Update subtotal display on card
    const subtotalDiv = document.getElementById(`subtotal_${roomTypeId}`);
    if (subtotalDiv) {
        const qty = roomType.quantity;
        const subtotalText = subtotalDiv.querySelector('.subtotal-text');
        const subtotalAmount = subtotalDiv.querySelector('.subtotal-amount');

        if (subtotalText && subtotalAmount) {
            subtotalText.textContent = `${qty} phòng × ${nights} đêm`;

            // Calculate base price separately
            const baseTotal = basePrice * qty * nights;

            if (typeExtraFees > 0) {
                subtotalAmount.innerHTML = `
                    <div class="text-primary fw-bold">${formatMoney(typeSubtotal)}</div>
                    <div class="small">
                        <span class="text-muted">Giá gốc: ${formatMoney(baseTotal)}</span>
                        <span class="text-danger"> + Phụ thu: ${formatMoney(typeExtraFees)}</span>
                    </div>
                `;
            } else {
                subtotalAmount.innerHTML = `<span class="text-primary fw-bold">${formatMoney(typeSubtotal)}</span>`;
            }
        }
    }
}

// Legacy function - kept for compatibility but not used
function updateSelectedRoomData(roomTypeId, roomIndex) {
    updateRoomGuestData(roomTypeId, roomIndex);
}

function calculateTotal() {
    let subtotal = 0;
    let extraFeesTotal = 0;

    // Build room summary HTML
    let roomSummaryHtml = '';

    Object.values(selectedRooms).forEach((roomType, typeIndex) => {
        const room = availableRoomsData.find(r => r.room_type_id == roomType.room_type_id);
        if (!room) return;

        const basePrice = parseFloat(room.base_price) || 0;
        const aRate = room.adult_surcharge_rate ?? null;
        const cRate = room.child_surcharge_rate ?? null;

        roomType.rooms.forEach((roomData, roomIndex) => {
            const adults = roomData.adults || 1;
            const children05 = roomData.children_0_5 || 0;
            const children611 = roomData.children_6_11 || 0;

            const br = bookingPriceBreakdown(basePrice, adults, children05, children611, aRate, cRate, room.standard_capacity, room.max_occupancy);
            const roomPricePerNight = br.perNight;
            const roomSubtotal = roomPricePerNight * nights;

            subtotal += roomSubtotal;
            extraFeesTotal += br.surcharge * nights;

            roomSummaryHtml += `
                <div class="mb-2 p-2 bg-light rounded">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold small">${room.name} (Phòng ${roomIndex + 1})</span>
                        <span class="text-primary fw-bold">${formatMoney(roomSubtotal)}</span>
                    </div>
                    <div class="small text-muted">
                        ${nights} đêm x ${formatMoney(roomPricePerNight)}
                    </div>
                    ${br.adultFee > 0 ? `<div class="text-danger small">Phụ thu NL vượt TC (${br.extraAdults}): +${formatMoney(br.adultFee * nights)}</div>` : ''}
                    ${br.childFee > 0 ? `<div class="text-danger small">Phụ thu trẻ 6–11 vượt TC (${br.extraChildren} em): +${formatMoney(br.childFee * nights)}</div>` : ''}
                </div>
            `;

            roomData.extra_adult_fee = br.adultFee;
            roomData.child_fee = br.childFee;
            roomData.price_per_night = roomPricePerNight;
        });
    });

    // Update room summary display if container exists
    const roomSummaryContainer = document.getElementById('roomSummaryContainer');
    if (roomSummaryContainer) {
        roomSummaryContainer.innerHTML = roomSummaryHtml || '<p class="text-muted small">Chưa có phòng nào được chọn</p>';
    }

    document.getElementById('subtotalAmount').textContent = formatMoney(subtotal);

    // Apply discount
    const discount = parseFloat(document.getElementById('discount_amount_input').value) || 0;
    const total = Math.max(0, subtotal - discount);

    document.getElementById('totalAmount').textContent = formatMoney(total);
    document.getElementById('total_price_input').value = total;

    // Update cash amount if paid selected
    const cashStatus = document.getElementById('cash_status');
    if (cashStatus && cashStatus.value === 'paid') {
        document.getElementById('cash_amount').value = total;
    }

    if (discount > 0) {
        document.getElementById('discountRow').style.display = 'flex';
        document.getElementById('discountAmount').textContent = '-' + formatMoney(discount);
    } else {
        document.getElementById('discountRow').style.display = 'none';
    }
}

function applyCoupon() {
    const code = document.getElementById('coupon_code').value.trim();
    if (!code) return;

    fetch(`{{ route('admin.bookings.validate-coupon') }}?code=${code}`)
        .then(r => r.json())
        .then(data => {
            const msgDiv = document.getElementById('couponMessage');
            if (data.valid) {
                let subtotal = 0;
                Object.values(selectedRooms).forEach(roomType => {
                    const room = availableRoomsData.find(r => r.room_type_id == roomType.room_type_id);
                    if (!room) return;
                    const basePrice = parseFloat(room.base_price) || 0;
                    const aR = room.adult_surcharge_rate ?? null;
                    const cR = room.child_surcharge_rate ?? null;
                    roomType.rooms.forEach(rd => {
                        const br = bookingPriceBreakdown(basePrice, rd.adults || 1, rd.children_0_5 || 0, rd.children_6_11 || 0, aR, cR, room.standard_capacity, room.max_occupancy);
                        subtotal += br.perNight * nights;
                    });
                });

                const discount = subtotal * (data.discount_percent / 100);
                document.getElementById('discount_amount_input').value = discount;
                msgDiv.innerHTML = `<span class="text-success">✓ Áp dụng thành công! Giảm ${data.discount_percent}%</span>`;
                calculateTotal();
            } else {
                document.getElementById('discount_amount_input').value = 0;
                msgDiv.innerHTML = `<span class="text-danger">✗ ${data.message}</span>`;
                calculateTotal();
            }
        });
}

function formatMoney(amount) {
    // Round to avoid floating point issues
    const rounded = Math.round(amount);
    return new Intl.NumberFormat('vi-VN').format(rounded) + 'đ';
}

// Toggle payment method UI
function togglePaymentMethod() {
    const isCash = document.getElementById('payment_cash').checked;
    const isVnpay = document.getElementById('payment_vnpay').checked;

    const cashStatus = document.getElementById('cashPaymentStatus');
    const vnpayInfo = document.getElementById('vnpayInfo');

    if (isVnpay) {
        cashStatus.style.display = 'none';
        document.getElementById('cashAmountDiv').style.display = 'none';
        vnpayInfo.style.display = 'block';
    } else if (isCash) {
        vnpayInfo.style.display = 'none';
        cashStatus.style.display = 'block';
        toggleCashAmount();
    }
}

// Toggle cash amount input based on status
function toggleCashAmount() {
    const status = document.getElementById('cash_status').value;
    const amountDiv = document.getElementById('cashAmountDiv');

    if (status === 'paid') {
        amountDiv.style.display = 'block';
        // Auto-fill with total amount
        const total = parseFloat(document.getElementById('total_price_input').value) || 0;
        document.getElementById('cash_amount').value = total;
    } else {
        amountDiv.style.display = 'none';
        document.getElementById('cash_amount').value = 0;
    }
}

// Update transfer content when total changes
function updateTransferContent() {
    const total = document.getElementById('totalAmount').textContent;
    // This will be updated with actual booking ID after creation
}

// Simple functions to show/hide guest inputs
function addMoreGuests() {
    for (let i = 3; i <= 4; i++) {
        const row = document.getElementById(`guestRow${i}`);
        if (row) {
            row.style.display = 'block';
        }
    }

    document.getElementById('removeGuestBtn').style.display = 'inline-block';
    event.target.style.display = 'none';
}

function removeGuestInputs() {
    for (let i = 3; i <= 4; i++) {
        const row = document.getElementById(`guestRow${i}`);
        if (row) {
            row.style.display = 'none';
            // Clear the values
            const inputs = row.querySelectorAll('input[type="text"]');
            inputs.forEach(input => input.value = '');
        }
    }

    document.getElementById('removeGuestBtn').style.display = 'none';
    document.querySelector('[onclick="addMoreGuests()"]').style.display = 'inline-block';
}

// Before submit, add selected rooms to form
function prepareFormData() {
    const form = document.getElementById('bookingForm');

    // Remove old dynamic inputs
    form.querySelectorAll('.dynamic-room-input').forEach(el => el.remove());
    form.querySelectorAll('.dynamic-guest-input').forEach(el => el.remove());

    // Add new room inputs
    Object.values(selectedRooms).forEach((roomType, typeIndex) => {
        roomType.rooms.forEach((roomData, roomIndex) => {
            const globalIndex = typeIndex * roomType.quantity + roomIndex;
            const addInput = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `rooms[${globalIndex}][${name}]`;
                input.value = value;
                input.className = 'dynamic-room-input';
                form.appendChild(input);
            };

            addInput('room_type_id', parseInt(roomType.room_type_id));
            addInput('quantity', 1);
            addInput('adults', parseInt(roomData.adults) || 1);
            addInput('children_0_5', parseInt(roomData.children_0_5) || 0);
            addInput('children_6_11', parseInt(roomData.children_6_11) || 0);
            addInput('price_per_night', parseFloat(roomData.price_per_night) || roomType.base_price);
        });
    });

    // Add guest inputs - LẤY TẤT CẢ GUEST INPUTS TRONG DOM
    Object.values(selectedRooms).forEach((roomType, typeIndex) => {
        const roomTypeKey = roomType.room_type_key || sanitizeRoomTypeKey(roomType.name);

        roomType.rooms.forEach((roomData, roomIndex) => {
            const roomId = `${roomTypeKey}_${roomIndex}`;
            const roomDiv = document.querySelector(`[data-room-id="${roomId}"]`);

            if (!roomDiv) return;

            // Lấy tất cả guest inputs trong roomDiv
            const guestInputs = roomDiv.querySelectorAll('.guest-name-input');

            guestInputs.forEach((nameInput, guestIdx) => {
                const cccdInput = roomDiv.querySelectorAll('.guest-cccd-input')[guestIdx];
                const nameValue = nameInput?.value?.trim() || '';
                const cccdValue = cccdInput?.value?.trim() || '';

                // Chỉ thêm nếu có tên
                if (nameValue) {
                    const addGuestInput = (field, value) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `guests[${roomTypeKey}][${roomIndex}][${guestIdx}][${field}]`;
                        input.value = value;
                        input.className = 'dynamic-guest-input';
                        form.appendChild(input);
                    };

                    // Xác định type từ input hidden hoặc label
                    const typeInput = nameInput.closest('.row')?.querySelector('input[type="hidden"][name*="[type]"]');
                    const type = typeInput?.value || 'adult';

                    addGuestInput('room_index', roomIndex);
                    addGuestInput('type', type);
                    addGuestInput('is_representative', 0); // Không có đại diện trong phòng
                    addGuestInput('name', nameValue);
                    addGuestInput('cccd', cccdValue);
                }
            });
        });
    });
}

// Global representative data (single for entire booking)
let representativeData = { name: '', cccd: '' };

function getRepresentativeValues() {
    const repNameInput = document.querySelector('input[name="representative_name"]');
    const repCccdInput = document.querySelector('input[name="representative_cccd"]');
    return {
        name: repNameInput?.value?.trim() || '',
        cccd: repCccdInput?.value?.trim() || ''
    };
}

function setRepresentativeValue(field, value) {
    representativeData[field] = value;
}

function generateGuestDetailsForm() {
    const container = document.getElementById('guestFormsContainer');
    if (!container) return;

    const selectedRoomTypes = Object.values(selectedRooms);
    if (selectedRoomTypes.length === 0) {
        container.style.display = 'none';
        container.innerHTML = '';
        return;
    }

    container.style.display = 'block';

    // Tạo structure với phần đại diện riêng ở đầu
    if (!container.querySelector('.representative-section')) {
        container.innerHTML = `
            <div class="card-header bg-white border-0 rounded-top-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="h6 mb-0 fw-bold">Thông tin chi tiết khách hàng</h2>
                    <span class="badge bg-warning text-dark border">Bắt buộc</span>
                </div>
                <div class="small text-muted mt-1 guest-count-hint"></div>
            </div>
            <div class="card-body">
                <!-- PHẦN NGƯỜI ĐẠI DIỆN - CHỈ 1 LẦN -->
                <div class="representative-section mb-4 border border-primary rounded p-3 bg-light">
                    <h5 class="mb-3 text-primary fw-bold">
                        <i class="bi bi-person-check me-2"></i>Người đại diện
                        <span class="badge bg-primary ms-2">Bắt buộc</span>
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Họ tên người đại diện <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="representative_name"
                                   class="form-control representative-name-input"
                                   placeholder="Nhập họ tên người đại diện"
                                   value="${representativeData.name}"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">CCCD/CMND <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="representative_cccd"
                                   class="form-control representative-cccd-input"
                                   placeholder="Nhập số CCCD (12 số)"
                                   value="${representativeData.cccd}"
                                   pattern="\\d{12}"
                                   minlength="12"
                                   maxlength="12"
                                   required>
                        </div>
                    </div>
                </div>

                <!-- PHẦN CÁC PHÒNG - KHÔNG CÓ ĐẠI DIỆN -->
                <div class="guest-rows"></div>
            </div>
        `;

        // Add event listeners for representative inputs
        const repNameInput = container.querySelector('.representative-name-input');
        const repCccdInput = container.querySelector('.representative-cccd-input');

        if (repNameInput) {
            repNameInput.addEventListener('input', function() {
                setRepresentativeValue('name', this.value);
            });
        }
        if (repCccdInput) {
            repCccdInput.addEventListener('input', function() {
                setRepresentativeValue('cccd', this.value);
            });
        }
    }

    const rowsContainer = container.querySelector('.guest-rows');
    if (!rowsContainer) return;

    let totalGuests = 0;

    selectedRoomTypes.forEach((roomType, typeIndex) => {
        const roomTypeKey = roomType.room_type_key || sanitizeRoomTypeKey(roomType.name);
        const roomTypeLabel = roomType.room_type_label || roomType.name;

        if (!guestData[roomTypeKey]) {
            guestData[roomTypeKey] = [];
        }

        // Loop through each specific room
        roomType.rooms.forEach((roomData, roomIndex) => {
            const adultsCount = parseInt(roomData.adults, 10) || 0;
            const children0_5Count = parseInt(roomData.children_0_5, 10) || 0;
            const children6_11Count = parseInt(roomData.children_6_11, 10) || 0;
            const roomGuestCount = adultsCount + children0_5Count + children6_11Count;
            totalGuests += roomGuestCount;

            const roomId = `${roomTypeKey}_${roomIndex}`;

            // Check if this room already exists in DOM
            let roomDiv = rowsContainer.querySelector(`[data-room-id="${roomId}"]`);

            if (!roomDiv) {
                // Tạo form cho tất cả khách trong phòng (KHÔNG bao gồm đại diện)
                let guestInputsHtml = '';
                let globalGuestIdx = 0;

                // Đảm bảo guestData cho roomTypeKey được khởi tạo
                if (!guestData[roomTypeKey]) {
                    guestData[roomTypeKey] = {};
                }

                // Người lớn - BẮT ĐẦU TỪ 1 để bỏ qua đại diện
                for (let i = 1; i < adultsCount; i++) {
                    const guestIdx = globalGuestIdx++;
                    const labelText = `Người lớn ${i}`;

                    if (!guestData[roomTypeKey][guestIdx]) {
                        guestData[roomTypeKey][guestIdx] = {};
                    }
                    const savedName = guestData[roomTypeKey][guestIdx]?.name ?? '';
                    const savedCccd = guestData[roomTypeKey][guestIdx]?.cccd ?? '';

                    guestInputsHtml += `
                        <div class="row g-3 mb-3 border-bottom pb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">
                                    ${labelText}
                                    <span class="badge bg-secondary ms-1">Phòng ${roomIndex + 1}</span>
                                </label>
                                <input type="hidden" name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][room_index]" value="${roomIndex}">
                                <input type="hidden" name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][type]" value="adult">
                                <input type="hidden" name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][is_representative]" value="0">
                                <input type="text"
                                       name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][name]"
                                       class="form-control guest-name-input"
                                       data-room-type="${roomTypeKey}"
                                       data-room-index="${roomIndex}"
                                       data-guest-idx="${guestIdx}"
                                       placeholder="Nhập họ tên"
                                       value="${savedName}"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">CCCD (không bắt buộc)</label>
                                <input type="text"
                                       name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][cccd]"
                                       class="form-control guest-cccd-input"
                                       data-room-type="${roomTypeKey}"
                                       data-room-index="${roomIndex}"
                                       data-guest-idx="${guestIdx}"
                                       placeholder="Nhập số CCCD nếu có"
                                       value="${savedCccd}"
                                       pattern="\\d{12}"
                                       minlength="12"
                                       maxlength="12">
                            </div>
                        </div>
                    `;
                }

                // Trẻ em 0-5 tuổi (không cần CCCD)
                for (let i = 0; i < children0_5Count; i++) {
                    const guestIdx = globalGuestIdx++;

                    if (!guestData[roomTypeKey][roomIndex]) {
                        guestData[roomTypeKey][roomIndex] = {};
                    }
                    if (!guestData[roomTypeKey][roomIndex][guestIdx]) {
                        guestData[roomTypeKey][roomIndex][guestIdx] = {};
                    }
                    const savedName = guestData[roomTypeKey][roomIndex][guestIdx]?.name ?? '';

                    guestInputsHtml += `
                        <div class="row g-3 mb-3 border-bottom pb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">
                                    Trẻ em 0-5 tuổi ${i + 1}
                                    <span class="badge bg-info ms-1">Phòng ${roomIndex + 1}</span>
                                </label>
                                <input type="hidden" name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][room_index]" value="${roomIndex}">
                                <input type="hidden" name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][type]" value="child_0_5">
                                <input type="hidden" name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][is_representative]" value="0">
                                <input type="text"
                                       name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][name]"
                                       class="form-control guest-name-input"
                                       data-room-type="${roomTypeKey}"
                                       data-room-index="${roomIndex}"
                                       data-guest-idx="${guestIdx}"
                                       placeholder="Nhập họ tên trẻ"
                                       value="${savedName}"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">CCCD (không bắt buộc)</label>
                                <input type="text"
                                       name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][cccd]"
                                       class="form-control guest-cccd-input"
                                       data-room-type="${roomTypeKey}"
                                       data-room-index="${roomIndex}"
                                       data-guest-idx="${guestIdx}"
                                       placeholder="Nhập số CCCD nếu có">
                            </div>
                        </div>
                    `;
                }

                // Trẻ em 6-11 tuổi (không cần CCCD)
                for (let i = 0; i < children6_11Count; i++) {
                    const guestIdx = globalGuestIdx++;

                    if (!guestData[roomTypeKey][roomIndex]) {
                        guestData[roomTypeKey][roomIndex] = {};
                    }
                    if (!guestData[roomTypeKey][roomIndex][guestIdx]) {
                        guestData[roomTypeKey][roomIndex][guestIdx] = {};
                    }
                    const savedName = guestData[roomTypeKey][roomIndex][guestIdx]?.name ?? '';

                    guestInputsHtml += `
                        <div class="row g-3 mb-3 ${i < children6_11Count - 1 ? 'border-bottom pb-3' : ''}">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">
                                    Trẻ em 6-11 tuổi ${i + 1}
                                    <span class="badge bg-warning text-dark ms-1">Phòng ${roomIndex + 1}</span>
                                </label>
                                <input type="hidden" name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][room_index]" value="${roomIndex}">
                                <input type="hidden" name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][type]" value="child_6_11">
                                <input type="hidden" name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][is_representative]" value="0">
                                <input type="text"
                                       name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][name]"
                                       class="form-control guest-name-input"
                                       data-room-type="${roomTypeKey}"
                                       data-room-index="${roomIndex}"
                                       data-guest-idx="${guestIdx}"
                                       placeholder="Nhập họ tên trẻ"
                                       value="${savedName}"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">CCCD (không bắt buộc)</label>
                                <input type="text"
                                       name="guests[${roomTypeKey}][${roomIndex}][${guestIdx}][cccd]"
                                       class="form-control guest-cccd-input"
                                       data-room-type="${roomTypeKey}"
                                       data-room-index="${roomIndex}"
                                       data-guest-idx="${guestIdx}"
                                       placeholder="Nhập số CCCD nếu có">
                            </div>
                        </div>
                    `;
                }

                roomDiv = document.createElement('div');
                roomDiv.className = 'room-guest-group';
                roomDiv.dataset.roomId = roomId;
                roomDiv.innerHTML = guestInputsHtml;
                rowsContainer.appendChild(roomDiv);

                // Add event listeners for all inputs
                roomDiv.querySelectorAll('.guest-name-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const roomIdx = parseInt(this.dataset.roomIndex);
                        const guestIdx = parseInt(this.dataset.guestIdx);
                        if (!guestData[this.dataset.roomType][roomIdx]) {
                            guestData[this.dataset.roomType][roomIdx] = {};
                        }
                        if (!guestData[this.dataset.roomType][roomIdx][guestIdx]) {
                            guestData[this.dataset.roomType][roomIdx][guestIdx] = {};
                        }
                        guestData[this.dataset.roomType][roomIdx][guestIdx].name = this.value;
                    });
                });
                roomDiv.querySelectorAll('.guest-cccd-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const roomIdx = parseInt(this.dataset.roomIndex);
                        const guestIdx = parseInt(this.dataset.guestIdx);
                        if (!guestData[this.dataset.roomType][roomIdx]) {
                            guestData[this.dataset.roomType][roomIdx] = {};
                        }
                        if (!guestData[this.dataset.roomType][roomIdx][guestIdx]) {
                            guestData[this.dataset.roomType][roomIdx][guestIdx] = {};
                        }
                        guestData[this.dataset.roomType][roomIdx][guestIdx].cccd = this.value;
                    });
                });
            }
        });
    });

    const hint = container.querySelector('.guest-count-hint');
    if (hint) {
        const roomCount = selectedRoomTypes.reduce((sum, rt) => sum + (rt.rooms?.length || 0), 0);
        const totalPeople = selectedRoomTypes.reduce((sum, rt) => {
            return sum + rt.rooms.reduce((roomSum, r) => roomSum + (r.adults || 1) + (r.children_0_5 || 0) + (r.children_6_11 || 0), 0);
        }, 0);
        hint.textContent = `Vui lòng nhập thông tin người đại diện và ${totalPeople - 1} khách cho ${roomCount} phòng đã chọn.`;
    }
}

// Simple function to select a room and fill form data
function selectRoom(roomId, adults, children) {
    document.getElementById('form_room_id').value = roomId;
    document.getElementById('form_adults').value = adults || 1;
    document.getElementById('form_children').value = children || 0;
    console.log('Room selected:', roomId, 'Adults:', adults, 'Children:', children);
}

// Form validation and preparation
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    let hasRooms = false;
    Object.values(selectedRooms).forEach(roomType => {
        if (roomType.quantity > 0) hasRooms = true;
    });

    if (!hasRooms) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất một phòng.');
        return false;
    }

    // Prepare dynamic fields before submission
    prepareFormData();
    console.log('Form submitting with selected rooms:', selectedRooms);
});
</script>
@endsection
