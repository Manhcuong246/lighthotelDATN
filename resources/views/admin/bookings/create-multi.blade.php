@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">🛎️ Tạo Đặt Phòng Nhiều Phòng</h4>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Step 1: Chọn Ngày -->
    <div class="card shadow-sm border-0 rounded-3 mb-4" id="step1">
        <div class="card-header bg-gradient rounded-top-3">
            <h5 class="mb-0 fw-bold">📅 Bước 1: Chọn Ngày Đặt Phòng</h5>
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
                    <button type="button" class="btn btn-primary w-100" onclick="checkAvailability()">
                        <i class="bi bi-search"></i> Tìm Phòng Trống
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Chọn Phòng và Thông Tin -->
    <form id="bookingForm" action="{{ route('admin.bookings.store-multi') }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="check_in" id="form_check_in">
        <input type="hidden" name="check_out" id="form_check_out">

        <!-- Danh sách phòng trống -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-gradient rounded-top-3">
                <h5 class="mb-0 fw-bold">🏨 Bước 2: Chọn Loại Phòng và Số Lượng</h5>
            </div>
            <div class="card-body p-3">
                <div id="availableRooms" class="d-flex flex-column">
                    <!-- Rooms will be loaded here via AJAX -->
                </div>
            </div>
        </div>

        <!-- Thông tin khách hàng -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-gradient rounded-top-3">
                <h5 class="mb-0 fw-bold">👤 Thông Tin Khách Hàng</h5>
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

        <!-- Mã giảm giá và thanh toán -->
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-gradient rounded-top-3">
                        <h5 class="mb-0 fw-bold">🎟️ Mã Giảm Giá</h5>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" name="coupon_code" id="coupon_code" class="form-control" placeholder="Nhập mã giảm giá">
                            <button type="button" class="btn btn-outline-primary" onclick="applyCoupon()">
                                Áp dụng
                            </button>
                        </div>
                        <div id="couponMessage" class="mt-2 small"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-gradient rounded-top-3">
                        <h5 class="mb-0 fw-bold">💳 Thanh Toán</h5>
                    </div>
                    <div class="card-body">
                        <!-- Payment Method Radio -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phương thức thanh toán *</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" checked onchange="togglePaymentMethod()">
                                    <label class="form-check-label" for="payment_cash">
                                        💵 Tiền mặt
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_bank" value="bank_transfer" onchange="togglePaymentMethod()">
                                    <label class="form-check-label" for="payment_bank">
                                        🏦 Chuyển khoản
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_vnpay" value="vnpay" onchange="togglePaymentMethod()">
                                    <label class="form-check-label" for="payment_vnpay">
                                        💳 VNPay (email SMTP + link có chữ ký)
                                    </label>
                                </div>
                            </div>
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

                        <!-- Bank Transfer Info (show when bank selected) -->
                        <div id="bankTransferInfo" class="alert alert-info" style="display: none;">
                            <h6 class="alert-heading fw-bold">🏦 Thông tin chuyển khoản</h6>
                            <hr class="my-2">
                            @if($hotelInfo)
                                <p class="mb-1"><strong>Ngân hàng:</strong> {{ $hotelInfo->bank_name ?? 'Vietcombank' }}</p>
                                <p class="mb-1"><strong>Số tài khoản:</strong> {{ $hotelInfo->bank_account ?? 'Chưa cấu hình' }}</p>
                                <p class="mb-1"><strong>Chủ tài khoản:</strong> {{ $hotelInfo->bank_account_name ?? 'Chưa cấu hình' }}</p>
                            @else
                                <p class="mb-1"><strong>Ngân hàng:</strong> Vietcombank</p>
                                <p class="mb-1"><strong>Số tài khoản:</strong> 1234567890</p>
                                <p class="mb-1"><strong>Chủ tài khoản:</strong> KHÁCH SẠN LIGHTHOTEL</p>
                            @endif
                            <p class="mb-0 text-danger"><strong>Nội dung CK:</strong> <span id="transferContent">BOOKING_<span id="bookingIdPlaceholder">[ID]</span></span></p>
                            <hr class="my-2">
                            <p class="mb-0 small text-muted">Sau khi tạo đơn, bạn sẽ được chuyển đến trang hướng dẫn chuyển khoản.</p>
                        </div>

                        <div id="vnpayInfo" class="alert alert-primary mb-0" style="display: none;">
                            <h6 class="alert-heading fw-bold mb-2">Thanh toán VNPay</h6>
                            <p class="small mb-0">Sau khi tạo đơn, hệ thống gửi email cho khách (nếu đã cấu hình SMTP) kèm link có chữ ký. Thời hạn ~{{ (int) config('vnpay.transaction_expire_minutes', 15) }} phút trên VNPay tính từ lúc khách <strong>bấm link</strong> trong email. Trang hướng dẫn admin cũng hiển thị cùng link để sao chép.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                        <button type="submit" class="btn btn-success btn-lg px-5">
                            <i class="bi bi-check-circle"></i> Xác Nhận Đặt Phòng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.bg-gradient { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); }
.room-card { transition: all 0.3s; border: 2px solid #dee2e6; }
.room-card:hover { border-color: #0d6efd; transform: translateY(-2px); }
.room-card.selected { border-color: #198754; background: #f8fff9; }
.room-image { height: 150px; object-fit: cover; border-radius: 8px; }
</style>

<script>
let availableRoomsData = [];
let selectedRooms = {};
let nights = 0;

// Set min checkout date when checkin changes
document.getElementById('check_in').addEventListener('change', function() {
    document.getElementById('check_out').min = this.value;
});

function checkAvailability() {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;

    if (!checkIn || !checkOut) {
        alert('Vui lòng chọn ngày nhận và trả phòng');
        return;
    }

    if (checkOut <= checkIn) {
        alert('Ngày trả phòng phải sau ngày nhận phòng');
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
            console.log('API Response:', data); // Debug log
            availableRoomsData = data.rooms;
            console.log('Rooms:', availableRoomsData); // Debug log
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
                        <div class="position-relative h-100" style="min-height: 100px;">
                            <img src="${roomImage}"
                                 class="w-100 h-100 rounded-start" style="object-fit: cover;"
                                 alt="${roomType.name}">
                            <span class="position-absolute top-0 start-0 m-1 badge bg-success small" style="font-size: 0.7rem;">
                                Còn ${roomType.available_count}
                            </span>
                        </div>
                    </div>
                    <!-- Room Details -->
                    <div class="col-md-7 col-sm-6 p-3">
                        <h6 class="fw-bold mb-1">${roomType.name}</h6>
                        <div class="text-muted small mb-1">
                            <i class="bi bi-aspect-ratio me-1"></i>${roomType.area || 30} m² ·
                            <i class="bi bi-people me-1"></i>Tối đa ${roomType.max_occupancy} người
                        </div>
                        <div class="mb-1">
                            <span class="badge bg-light text-dark border-0 rounded-pill px-2 py-1 me-1 small"><i class="bi bi-wifi me-1"></i>Wi-Fi</span>
                            <span class="badge bg-light text-dark border-0 rounded-pill px-2 py-1 me-1 small"><i class="bi bi-snow me-1"></i>AC</span>
                            <span class="badge bg-light text-dark border-0 rounded-pill px-2 py-1 small"><i class="bi bi-tv me-1"></i>TV</span>
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
                                        onclick="changeRoomQuantity('${roomType.room_type_id}', -1, ${roomType.available_count}, ${roomType.base_price}, '${roomType.name}', ${roomType.max_occupancy})"
                                        id="qtyMinus_${roomType.room_type_id}" style="font-size: 0.8rem;">−</button>
                                <input type="number" class="form-control form-control-sm text-center room-quantity py-0"
                                       id="qty_${roomType.room_type_id}"
                                       data-room-type="${roomType.room_type_id}"
                                       data-price="${roomType.base_price}"
                                       data-name="${roomType.name}"
                                       data-max="${roomType.max_occupancy}"
                                       value="0" min="0" max="${roomType.available_count}" readonly style="width: 40px; font-size: 0.9rem;">
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2"
                                        onclick="changeRoomQuantity('${roomType.room_type_id}', 1, ${roomType.available_count}, ${roomType.base_price}, '${roomType.name}', ${roomType.max_occupancy})"
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
function changeRoomQuantity(roomTypeId, delta, maxAvailable, price, name, maxOccupancy) {
    const qtyInput = document.getElementById(`qty_${roomTypeId}`);
    const currentQty = parseInt(qtyInput.value) || 0;
    const newQty = currentQty + delta;

    if (newQty < 0 || newQty > maxAvailable) return;

    qtyInput.value = newQty;
    updateRoomCardState(roomTypeId, newQty, price, name, maxOccupancy);
}

// Update room card visual state and generate individual room forms
function updateRoomCardState(roomTypeId, quantity, price, name, maxOccupancy) {
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

        // Generate/update individual room forms
        generateRoomForms(roomTypeId, quantity, price, name, maxOccupancy);

        // Update data structure
        if (!selectedRooms[roomTypeId]) {
            selectedRooms[roomTypeId] = {
                room_type_id: roomTypeId,
                quantity: quantity,
                price_per_night: price,
                name: name,
                rooms: []
            };
        }
        selectedRooms[roomTypeId].quantity = quantity;

        // Initialize room data if not exists
        for (let i = 0; i < quantity; i++) {
            if (!selectedRooms[roomTypeId].rooms[i]) {
                selectedRooms[roomTypeId].rooms[i] = {
                    adults: 1,
                    children_0_5: 0,
                    children_6_11: 0
                };
            }
        }
        // Trim excess rooms if quantity decreased
        selectedRooms[roomTypeId].rooms = selectedRooms[roomTypeId].rooms.slice(0, quantity);

    } else {
        card.classList.remove('selected');
        card.style.border = '';
        roomFormsContainer.style.display = 'none';
        roomFormsContainer.innerHTML = '';
        subtotalDiv.style.display = 'none';
        delete selectedRooms[roomTypeId];
    }

    // Update minus button state
    minusBtn.disabled = quantity <= 0;

    calculateTotal();
}

// Generate individual room forms
function generateRoomForms(roomTypeId, quantity, price, name, maxOccupancy) {
    const container = document.getElementById(`roomForms_${roomTypeId}`);
    const currentForms = container.querySelectorAll('.individual-room-form').length;

    // Remove excess forms if quantity decreased
    while (container.querySelectorAll('.individual-room-form').length > quantity) {
        container.lastElementChild.remove();
    }

    // Add new forms if quantity increased
    for (let i = currentForms; i < quantity; i++) {
        const roomIndex = i;
        const formHtml = `
            <div class="individual-room-form card border-0 shadow-sm mb-2" id="roomForm_${roomTypeId}_${roomIndex}">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0 text-primary">Phòng ${roomIndex + 1}</h6>
                        <span class="text-muted small">Tiêu chuẩn: ${maxOccupancy} người</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1" style="font-size: 0.75rem;">Người lớn</label>
                            <input type="number" class="form-control form-control-sm room-adults"
                                   id="adults_${roomTypeId}_${roomIndex}"
                                   data-room-type="${roomTypeId}"
                                   data-room-index="${roomIndex}"
                                   min="1" max="${maxOccupancy + 2}" value="1"
                                   style="font-size: 0.85rem;" onchange="updateRoomGuestData('${roomTypeId}', ${roomIndex})">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1" style="font-size: 0.75rem;">Trẻ 0-5t</label>
                            <input type="number" class="form-control form-control-sm room-children-0-5"
                                   id="children05_${roomTypeId}_${roomIndex}"
                                   data-room-type="${roomTypeId}"
                                   data-room-index="${roomIndex}"
                                   min="0" value="0"
                                   style="font-size: 0.85rem;" onchange="updateRoomGuestData('${roomTypeId}', ${roomIndex})">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1" style="font-size: 0.75rem;">Trẻ 6-11t</label>
                            <input type="number" class="form-control form-control-sm room-children-6-11"
                                   id="children611_${roomTypeId}_${roomIndex}"
                                   data-room-type="${roomTypeId}"
                                   data-room-index="${roomIndex}"
                                   min="0" value="0"
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
}

// Calculate price details with surcharge for extra guests
function updateRoomPriceDetails(roomTypeId, roomIndex, adults, children05, children611) {
    const roomData = selectedRooms[roomTypeId];
    const room = availableRoomsData.find(r => r.room_type_id == roomTypeId);
    if (!room) return;

    const basePrice = parseFloat(room.base_price) || 0;
    const maxAdults = room.max_occupancy || 2;
    const maxChildren = 1; // Default max children

    // Calculate extra guests
    const extraAdults = Math.max(0, adults - maxAdults);
    const totalChildren = children05 + children611;
    const chargeableChildren = Math.max(0, children611 - maxChildren);

    // Check limit (+2 max)
    const limitError = document.getElementById(`limitError_${roomTypeId}_${roomIndex}`);
    if (extraAdults > 2 || (totalChildren - maxChildren) > 2) {
        if (!limitError) {
            const formCard = document.getElementById(`roomForm_${roomTypeId}_${roomIndex}`);
            const errorHtml = `
                <div id="limitError_${roomTypeId}_${roomIndex}" class="alert alert-danger py-1 small mb-2 mt-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Vượt quá giới hạn +2 người
                </div>
            `;
            formCard.querySelector('.card-body').insertAdjacentHTML('beforeend', errorHtml);
        }
    } else if (limitError) {
        limitError.remove();
    }

    // Calculate fees (40% for adults, 30% for children)
    const extraAdultFee = extraAdults * (0.4 * basePrice);
    const childFee = chargeableChildren * (0.3 * basePrice);

    roomData.rooms[roomIndex].extra_adult_fee = extraAdultFee;
    roomData.rooms[roomIndex].child_fee = childFee;
    roomData.rooms[roomIndex].price_per_night = basePrice + extraAdultFee + childFee;

    // Update display
    const feeDisplay = document.getElementById(`feeDisplay_${roomTypeId}_${roomIndex}`);
    if (feeDisplay) {
        let feeHtml = '';
        if (extraAdultFee > 0) {
            feeHtml += `<div class="text-danger small">Phụ thu người lớn: +${formatMoney(extraAdultFee * nights)}</div>`;
        }
        if (childFee > 0) {
            feeHtml += `<div class="text-danger small">Phụ thu trẻ em: +${formatMoney(childFee * nights)}</div>`;
        }
        feeDisplay.innerHTML = feeHtml;
    }

    // Update main room card subtotal display
    updateRoomCardSubtotal(roomTypeId);
}

// Update room card subtotal display
function updateRoomCardSubtotal(roomTypeId) {
    const roomType = selectedRooms[roomTypeId];
    if (!roomType) return;

    const room = availableRoomsData.find(r => r.room_type_id == roomTypeId);
    if (!room) return;

    const basePrice = parseFloat(room.base_price) || 0;
    const maxAdults = room.max_occupancy || 2;
    const maxChildren = 1;

    let typeSubtotal = 0;
    let typeExtraFees = 0;

    roomType.rooms.forEach(roomData => {
        const adults = roomData.adults || 1;
        const children611 = roomData.children_6_11 || 0;

        const extraAdults = Math.max(0, adults - maxAdults);
        const chargeableChildren = Math.max(0, children611 - maxChildren);

        const extraAdultFeePerNight = extraAdults * (0.4 * basePrice);
        const childFeePerNight = chargeableChildren * (0.3 * basePrice);

        const roomPricePerNight = basePrice + extraAdultFeePerNight + childFeePerNight;
        typeSubtotal += roomPricePerNight * nights;
        typeExtraFees += (extraAdultFeePerNight + childFeePerNight) * nights;
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

    console.log('=== CALCULATE TOTAL ===');
    console.log('selectedRooms:', selectedRooms);
    console.log('availableRoomsData:', availableRoomsData);
    console.log('nights:', nights);

    // Build room summary HTML
    let roomSummaryHtml = '';

    Object.values(selectedRooms).forEach((roomType, typeIndex) => {
        const room = availableRoomsData.find(r => r.room_type_id == roomType.room_type_id);
        if (!room) {
            console.log('Room not found for type:', roomType.room_type_id);
            return;
        }

        const basePrice = parseFloat(room.base_price) || 0;
        console.log('Room:', room.name, 'basePrice:', basePrice, 'max_occupancy:', room.max_occupancy);

        roomType.rooms.forEach((roomData, roomIndex) => {
            const adults = roomData.adults || 1;
            const children05 = roomData.children_0_5 || 0;
            const children611 = roomData.children_6_11 || 0;

            console.log(`Room ${roomIndex + 1}: adults=${adults}, children05=${children05}, children611=${children611}`);

            const maxAdults = room.max_occupancy || 2;
            const maxChildren = 1;

            const extraAdults = Math.max(0, adults - maxAdults);
            const chargeableChildren = Math.max(0, children611 - maxChildren);

            console.log(`Extra adults: ${extraAdults}, Chargeable children: ${chargeableChildren}`);

            const extraAdultFeePerNight = extraAdults * (0.4 * basePrice);
            const childFeePerNight = chargeableChildren * (0.3 * basePrice);

            console.log(`Extra adult fee per night: ${extraAdultFeePerNight}, Child fee per night: ${childFeePerNight}`);

            const roomPricePerNight = basePrice + extraAdultFeePerNight + childFeePerNight;
            const roomSubtotal = roomPricePerNight * nights;

            console.log(`Room price per night: ${roomPricePerNight}, Room subtotal: ${roomSubtotal}`);

            subtotal += roomSubtotal;
            extraFeesTotal += (extraAdultFeePerNight + childFeePerNight) * nights;

            // Add to summary HTML
            roomSummaryHtml += `
                <div class="mb-2 p-2 bg-light rounded">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold small">${room.name} (Phòng ${roomIndex + 1})</span>
                        <span class="text-primary fw-bold">${formatMoney(roomSubtotal)}</span>
                    </div>
                    <div class="small text-muted">
                        ${nights} đêm x ${formatMoney(roomPricePerNight)}
                    </div>
                    ${extraAdultFeePerNight > 0 ? `<div class="text-danger small">Phụ thu người lớn: +${formatMoney(extraAdultFeePerNight * nights)}</div>` : ''}
                    ${childFeePerNight > 0 ? `<div class="text-danger small">Phụ thu trẻ em: +${formatMoney(childFeePerNight * nights)}</div>` : ''}
                </div>
            `;

            // Update room data for form submission
            roomData.extra_adult_fee = extraAdultFeePerNight;
            roomData.child_fee = childFeePerNight;
            roomData.price_per_night = roomPricePerNight;
        });
    });

    console.log('Final subtotal:', subtotal);
    console.log('Final extraFeesTotal:', extraFeesTotal);

    // Update room summary display if container exists
    const roomSummaryContainer = document.getElementById('roomSummaryContainer');
    if (roomSummaryContainer) {
        roomSummaryContainer.innerHTML = roomSummaryHtml || '<p class="text-muted small">Chưa có phòng nào được chọn</p>';
    }

    document.getElementById('subtotalAmount').textContent = formatMoney(subtotal);

    // Apply discount
    const discount = parseFloat(document.getElementById('discount_amount_input').value) || 0;
    const total = Math.max(0, subtotal - discount);

    console.log('Discount:', discount, 'Total:', total);

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
                Object.values(selectedRooms).forEach(room => {
                    subtotal += room.price_per_night * room.quantity * nights;
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
    const isBank = document.getElementById('payment_bank').checked;
    const isVnpay = document.getElementById('payment_vnpay').checked;

    const cashStatus = document.getElementById('cashPaymentStatus');
    const bankInfo = document.getElementById('bankTransferInfo');
    const vnpayInfo = document.getElementById('vnpayInfo');

    if (isVnpay) {
        cashStatus.style.display = 'none';
        document.getElementById('cashAmountDiv').style.display = 'none';
        bankInfo.style.display = 'none';
        vnpayInfo.style.display = 'block';
    } else if (isCash) {
        vnpayInfo.style.display = 'none';
        cashStatus.style.display = 'block';
        bankInfo.style.display = 'none';
        toggleCashAmount();
    } else if (isBank) {
        vnpayInfo.style.display = 'none';
        cashStatus.style.display = 'none';
        document.getElementById('cashAmountDiv').style.display = 'none';
        bankInfo.style.display = 'block';
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

// Before submit, add selected rooms to form
function prepareFormData() {
    const form = document.getElementById('bookingForm');

    // Remove old room inputs
    form.querySelectorAll('.dynamic-room-input').forEach(el => el.remove());

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
            addInput('quantity', 1); // Each room is quantity 1 now
            addInput('adults', parseInt(roomData.adults) || 1);
            addInput('children_0_5', parseInt(roomData.children_0_5) || 0);
            addInput('children_6_11', parseInt(roomData.children_6_11) || 0);
            addInput('price_per_night', parseFloat(roomData.price_per_night) || roomType.price_per_night);
        });
    });
}

document.getElementById('bookingForm').addEventListener('submit', function(e) {
    prepareFormData();

    if (Object.keys(selectedRooms).length === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất một phòng');
        return false;
    }
});
</script>
@endsection
