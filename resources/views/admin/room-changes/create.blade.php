@php
    $reasons = config('room_changes.reasons', [
        'guest_request' => 'Khách yêu cầu đổi phòng',
        'room_issue'    => 'Phòng bị lỗi thiết bị',
        'upgrade'       => 'Khách muốn nâng hạng',
        'noise'         => 'Tiếng ồn / không gian ồn ào',
        'view_request'  => 'Khách muốn đổi view',
        'maintenance'   => 'Bảo trì phòng',
        'emergency'     => 'Khẩn cấp kỹ thuật',
        'other'         => 'Lý do khác',
    ]);
    $timeRestriction = config('room_changes.time_restriction_hour', 22);
    $upgradePolicy = config('room_changes.upgrade_policy', 'add_to_folio');
    $downgradePolicy = config('room_changes.downgrade_policy', 'credit');
@endphp
@extends('layouts.admin')

@section('title', 'Đổi phòng')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Đổi phòng mới
            </h1>
            <div class="text-muted small">Chọn đơn đặt phòng, chọn phòng cần đổi và phòng mới</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.room-changes.index') }}" class="btn btn-outline-secondary btn-sm rounded-2">
                <i class="bi bi-clock-history me-1"></i>Lịch sử
            </a>
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
        <strong>Lỗi!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form id="roomChangeForm" action="{{ route('admin.room-changes.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <!-- Cột trái: Chọn booking & phòng cũ -->
            <div class="col-lg-6">
                <!-- Bước 1: Chọn Booking -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-primary text-white rounded-top-3">
                        <h5 class="mb-0"><i class="bi bi-1-circle me-2"></i>Chọn đơn đặt phòng</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mã đơn đặt phòng</label>
                            <div class="input-group">
                                <input type="number" name="booking_id" id="booking_id" class="form-control" 
                                    value="{{ $booking?->id ?? old('booking_id') }}" 
                                    placeholder="Nhập mã đơn đặt phòng" required
                                    min="1">
                                <button type="button" class="btn btn-outline-primary" id="btnLoadBooking">
                                    <i class="bi bi-search me-1"></i>Tìm
                                </button>
                            </div>
                            <div class="form-text">Nhập mã đơn và nhấn Tìm để tải thông tin</div>
                        </div>

                        <!-- Thông tin booking (hiển thị sau khi load) -->
                        <div id="bookingInfo" class="d-none">
                            <div class="alert alert-light border">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-1"><strong>Khách hàng:</strong> <span id="infoGuestName">—</span></div>
                                        <div class="mb-1"><strong>Trạng thái:</strong> <span id="infoStatus" class="badge">—</span></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-1"><strong>Nhận phòng:</strong> <span id="infoCheckIn">—</span></div>
                                        <div class="mb-1"><strong>Trả phòng:</strong> <span id="infoCheckOut">—</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($booking)
                        <div class="alert alert-light border">
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-1"><strong>Khách hàng:</strong> {{ $booking->user?->full_name ?? 'N/A' }}</div>
                                    <div class="mb-1"><strong>Trạng thái:</strong> 
                                        <span class="badge bg-{{ $booking->status === 'confirmed' ? 'success' : ($booking->status === 'checked_in' ? 'info' : 'warning') }}">
                                            {{ $booking->status }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-1"><strong>Nhận phòng:</strong> {{ $booking->check_in?->format('d/m/Y') }}</div>
                                    <div class="mb-1"><strong>Trả phòng:</strong> {{ $booking->check_out?->format('d/m/Y') }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Bước 2: Chọn phòng cần đổi -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-secondary text-white rounded-top-3">
                        <h5 class="mb-0"><i class="bi bi-2-circle me-2"></i>Chọn phòng cần đổi</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phòng hiện tại</label>
                            <select name="old_room_id" id="old_room_id" class="form-select" required>
                                <option value="">-- Chọn đơn trước --</option>
                                @if($booking)
                                    @foreach($currentBookingRooms as $br)
                                    <option value="{{ $br->room_id }}" {{ old('old_room_id') == $br->room_id ? 'selected' : '' }}>
                                        {{ $br->room?->name ?? 'N/A' }} — {{ $br->room?->roomType?->name ?? 'N/A' }} — {{ number_format($br->price_per_night, 0, ',', '.') }} ₫/đêm
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Chi tiết phòng hiện tại -->
                        <div id="currentRoomDetails" class="d-none">
                            <div class="alert alert-light border">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-1"><strong>Phòng:</strong> <span id="detailRoomName">—</span></div>
                                        <div class="mb-1"><strong>Loại:</strong> <span id="detailRoomType">—</span></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-1"><strong>Giá/đêm:</strong> <span id="detailPrice">—</span></div>
                                        <div class="mb-1"><strong>Số đêm:</strong> <span id="detailNights">—</span></div>
                                        <div class="mb-1"><strong>Thành tiền:</strong> <span id="detailSubtotal" class="fw-bold">—</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Chọn phòng mới & lý do -->
            <div class="col-lg-6">
                <!-- Bước 3: Chọn phòng mới -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-success text-white rounded-top-3">
                        <h5 class="mb-0"><i class="bi bi-3-circle me-2"></i>Chọn phòng mới</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phòng mới</label>
                            <select name="new_room_id" id="new_room_id" class="form-select" required>
                                <option value="">-- Chọn phòng cần đổi trước --</option>
                            </select>
                            <div class="form-text">Chỉ hiển thị phòng sẵn sàng (Ready/Clean) trong khoảng thời gian đặt</div>
                        </div>

                        <!-- Chi tiết phòng mới -->
                        <div id="newRoomDetails" class="d-none">
                            <div class="alert alert-info border">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-1"><strong>Phòng mới:</strong> <span id="newDetailRoomName">—</span></div>
                                        <div class="mb-1"><strong>Loại:</strong> <span id="newDetailRoomType">—</span></div>
                                        <div class="mb-1"><strong>Sức chứa:</strong> <span id="newDetailCapacity">—</span></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-1"><strong>Giá/đêm:</strong> <span id="newDetailPrice">—</span></div>
                                        <div class="mb-1"><strong>Chênh lệch/đêm:</strong> <span id="newDetailDiff">—</span></div>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="row">
                                    <div class="col-6">
                                        <div><strong>Số đêm còn lại:</strong> <span id="newDetailRemainingNights" class="fw-bold">—</span></div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <strong>Tổng chênh lệch:</strong> <span id="totalDiff" class="fs-5">—</span>
                                    </div>
                                </div>
                                <!-- Loại đổi phòng -->
                                <div class="mt-2" id="changeTypeInfo"></div>
                                <!-- Cảnh báo sức chứa -->
                                <div class="mt-2 d-none" id="capacityWarning">
                                    <div class="alert alert-danger mb-0 py-1 small">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        <span id="capacityWarningText"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bước 4: Lý do & Xác nhận -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-warning text-dark rounded-top-3">
                        <h5 class="mb-0"><i class="bi bi-4-circle me-2"></i>Lý do đổi & Xác nhận</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lý do đổi phòng <span class="text-danger">*</span></label>
                            <select name="reason" id="reasonSelect" class="form-select mb-2" required>
                                <option value="">-- Chọn lý do --</option>
                                @foreach($reasons as $key => $label)
                                <option value="{{ $label }}" {{ old('reason') === $label ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="reason_custom" id="reasonCustom" class="form-control d-none"
                                placeholder="Nhập lý do cụ thể..." maxlength="500">
                        </div>

                        <!-- Khẩn cấp -->
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_emergency" id="isEmergency" class="form-check-input" value="1">
                            <label class="form-check-label text-danger fw-bold" for="isEmergency">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Trường hợp khẩn cấp
                            </label>
                            <div class="form-text">Bỏ qua giới hạn giờ đổi phòng (sau {{ $timeRestriction }}:00). Chỉ dùng cho trường hợp khẩn cấp kỹ thuật.</div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                            <div>
                                <strong>Quy tắc nghiệp vụ:</strong>
                                <ul class="mb-0 small">
                                    <li>Chỉ cho đổi sang phòng trạng thái <strong>sẵn sàng</strong> (Ready/Clean)</li>
                                    @if($upgradePolicy === 'pay_now')
                                    <li>Nâng hạng: Khách phải thanh toán bổ sung ngay</li>
                                    @elseif($upgradePolicy === 'add_to_folio')
                                    <li>Nâng hạng: Phí bổ sung ghi nợ vào hóa đơn tổng (Folio)</li>
                                    @endif
                                    @if($downgradePolicy === 'refund')
                                    <li>Hạ hạng: Hoàn tiền ngay cho khách</li>
                                    @elseif($downgradePolicy === 'credit')
                                    <li>Hạ hạng: Số tiền chênh lệch ghi credit vào Folio</li>
                                    @endif
                                    <li>Phòng cũ sẽ tự động chuyển sang trạng thái <strong>"Cần dọn dẹp"</strong></li>
                                    <li>Lệnh dọn phòng tự động gửi cho Housekeeping</li>
                                    <li>Giới hạn giờ: Không đổi phòng sau {{ $timeRestriction }}:00 (trừ khẩn cấp)</li>
                                </ul>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-2" id="btnSubmit" disabled>
                                <i class="bi bi-check-lg me-2"></i>Xác nhận đổi phòng
                            </button>
                            <a href="{{ route('admin.room-changes.index') }}" class="btn btn-outline-secondary rounded-2">
                                Hủy bỏ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bookingIdInput = document.getElementById('booking_id');
    const btnLoadBooking = document.getElementById('btnLoadBooking');
    const bookingInfo = document.getElementById('bookingInfo');
    const oldRoomSelect = document.getElementById('old_room_id');
    const currentRoomDetails = document.getElementById('currentRoomDetails');
    const newRoomSelect = document.getElementById('new_room_id');
    const newRoomDetails = document.getElementById('newRoomDetails');
    const btnSubmit = document.getElementById('btnSubmit');
    const reasonSelect = document.getElementById('reasonSelect');
    const reasonCustom = document.getElementById('reasonCustom');
    const isEmergency = document.getElementById('isEmergency');
    const changeTypeInfo = document.getElementById('changeTypeInfo');
    const capacityWarning = document.getElementById('capacityWarning');
    const capacityWarningText = document.getElementById('capacityWarningText');

    let currentPricePerNight = 0;
    let currentNights = 0;
    let currentGuests = 0;
    let availableRoomsData = [];

    // Lý do: hiện input custom khi chọn "Lý do khác"
    reasonSelect.addEventListener('change', function() {
        if (this.value === 'Lý do khác') {
            reasonCustom.classList.remove('d-none');
            reasonCustom.required = true;
            reasonCustom.focus();
        } else {
            reasonCustom.classList.add('d-none');
            reasonCustom.required = false;
        }
    });

    // Bước 1: Load thông tin booking
    btnLoadBooking.addEventListener('click', loadBooking);
    bookingIdInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); loadBooking(); }
    });

    function loadBooking() {
        const bookingId = bookingIdInput.value;
        if (!bookingId) return;

        btnLoadBooking.disabled = true;
        btnLoadBooking.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang tải...';

        fetch(`{{ route('admin.room-changes.booking-rooms') }}?booking_id=${bookingId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Không thể tải thông tin booking');
                    return;
                }

                // Hiển thị thông tin booking
                bookingInfo.classList.remove('d-none');
                document.getElementById('infoGuestName').textContent = data.booking.guest_name;
                document.getElementById('infoStatus').textContent = data.booking.status;
                document.getElementById('infoCheckIn').textContent = data.booking.check_in;
                document.getElementById('infoCheckOut').textContent = data.booking.check_out;

                // Cập nhật dropdown phòng hiện tại
                oldRoomSelect.innerHTML = '<option value="">-- Chọn phòng --</option>';
                data.data.forEach(room => {
                    const opt = document.createElement('option');
                    opt.value = room.room_id;
                    const totalGuests = (room.adults || 0) + (room.children_6_11 || 0) + (room.children_0_5 || 0);
                    opt.textContent = `${room.room_name} - ${room.room_type} - ${new Intl.NumberFormat('vi-VN').format(room.price_per_night)}₫/đêm (${totalGuests} khách)`;
                    opt.dataset.price = room.price_per_night;
                    opt.dataset.nights = room.nights;
                    opt.dataset.subtotal = room.subtotal;
                    opt.dataset.roomName = room.room_name;
                    opt.dataset.roomType = room.room_type;
                    opt.dataset.guests = totalGuests;
                    oldRoomSelect.appendChild(opt);
                });

                // Reset phòng mới
                newRoomSelect.innerHTML = '<option value="">-- Chọn phòng cần đổi trước --</option>';
                newRoomDetails.classList.add('d-none');
                currentRoomDetails.classList.add('d-none');
                btnSubmit.disabled = true;
            })
            .catch(err => alert('Lỗi kết nối: ' + err.message))
            .finally(() => {
                btnLoadBooking.disabled = false;
                btnLoadBooking.innerHTML = '<i class="bi bi-search me-1"></i>Tìm';
            });
    }

    // Bước 2: Chọn phòng cũ -> Hiện chi tiết & Load phòng mới
    oldRoomSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];

        if (this.value) {
            currentPricePerNight = parseFloat(selected.dataset.price) || 0;
            currentNights = parseInt(selected.dataset.nights) || 1;
            currentGuests = parseInt(selected.dataset.guests) || 0;

            currentRoomDetails.classList.remove('d-none');
            document.getElementById('detailRoomName').textContent = selected.dataset.roomName;
            document.getElementById('detailRoomType').textContent = selected.dataset.roomType;
            document.getElementById('detailPrice').textContent = new Intl.NumberFormat('vi-VN').format(currentPricePerNight) + ' ₫';
            document.getElementById('detailNights').textContent = currentNights;
            document.getElementById('detailSubtotal').textContent = new Intl.NumberFormat('vi-VN').format(currentPricePerNight * currentNights) + ' ₫';

            // Load phòng mới
            loadAvailableRooms(this.value);
        } else {
            currentRoomDetails.classList.add('d-none');
            newRoomSelect.innerHTML = '<option value="">-- Chọn phòng cần đổi trước --</option>';
            newRoomDetails.classList.add('d-none');
            btnSubmit.disabled = true;
        }
    });

    function loadAvailableRooms(currentRoomId) {
        const bookingId = bookingIdInput.value;
        if (!bookingId || !currentRoomId) return;

        newRoomSelect.innerHTML = '<option value="">Đang tải...</option>';
        newRoomSelect.disabled = true;

        fetch(`{{ route('admin.room-changes.available-rooms') }}?booking_id=${bookingId}&current_room_id=${currentRoomId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;

                availableRoomsData = data.data;
                newRoomSelect.innerHTML = '<option value="">-- Chọn phòng mới --</option>';

                if (data.data.length === 0) {
                    newRoomSelect.innerHTML = '<option value="">Không có phòng trống</option>';
                    return;
                }

                // Nhóm theo loại đổi phòng: cùng hạng, nâng hạng, hạ hạng
                const sameGrade = data.data.filter(r => r.change_type === 'same_grade');
                const upgrades = data.data.filter(r => r.change_type === 'upgrade');
                const downgrades = data.data.filter(r => r.change_type === 'downgrade');

                if (sameGrade.length > 0) {
                    const group = document.createElement('optgroup');
                    group.label = '🔵 Cùng hạng (không phụ phí)';
                    sameGrade.forEach(room => {
                        const opt = document.createElement('option');
                        opt.value = room.id;
                        const capacityWarn = !room.has_capacity ? ' ⚠️ Quá tải' : '';
                        opt.textContent = `${room.name} - ${room.room_type?.name || 'N/A'} (${new Intl.NumberFormat('vi-VN').format(room.base_price)}₫)${capacityWarn}`;
                        opt.dataset.price = room.base_price;
                        opt.dataset.name = room.name;
                        opt.dataset.type = room.room_type?.name || 'N/A';
                        opt.dataset.maxGuests = room.max_guests;
                        opt.dataset.hasCapacity = room.has_capacity ? '1' : '0';
                        opt.dataset.changeType = room.change_type;
                        opt.dataset.remainingNights = room.remaining_nights;
                        opt.dataset.totalDiff = room.total_price_difference;
                        opt.dataset.priceDiffPerNight = room.price_diff_per_night;
                        group.appendChild(opt);
                    });
                    newRoomSelect.appendChild(group);
                }

                if (upgrades.length > 0) {
                    const group = document.createElement('optgroup');
                    group.label = '⬆️ Nâng hạng (phụ phí)';
                    upgrades.forEach(room => {
                        const opt = document.createElement('option');
                        opt.value = room.id;
                        const capacityWarn = !room.has_capacity ? ' ⚠️ Quá tải' : '';
                        opt.textContent = `${room.name} - ${room.room_type?.name || 'N/A'} (${new Intl.NumberFormat('vi-VN').format(room.base_price)}₫) +${new Intl.NumberFormat('vi-VN').format(room.price_diff_per_night)}₫/đêm${capacityWarn}`;
                        opt.dataset.price = room.base_price;
                        opt.dataset.name = room.name;
                        opt.dataset.type = room.room_type?.name || 'N/A';
                        opt.dataset.maxGuests = room.max_guests;
                        opt.dataset.hasCapacity = room.has_capacity ? '1' : '0';
                        opt.dataset.changeType = room.change_type;
                        opt.dataset.remainingNights = room.remaining_nights;
                        opt.dataset.totalDiff = room.total_price_difference;
                        opt.dataset.priceDiffPerNight = room.price_diff_per_night;
                        group.appendChild(opt);
                    });
                    newRoomSelect.appendChild(group);
                }

                if (downgrades.length > 0) {
                    const group = document.createElement('optgroup');
                    group.label = '⬇️ Hạ hạng (hoàn tiền)';
                    downgrades.forEach(room => {
                        const opt = document.createElement('option');
                        opt.value = room.id;
                        const capacityWarn = !room.has_capacity ? ' ⚠️ Quá tải' : '';
                        opt.textContent = `${room.name} - ${room.room_type?.name || 'N/A'} (${new Intl.NumberFormat('vi-VN').format(room.base_price)}₫) ${new Intl.NumberFormat('vi-VN').format(room.price_diff_per_night)}₫/đêm${capacityWarn}`;
                        opt.dataset.price = room.base_price;
                        opt.dataset.name = room.name;
                        opt.dataset.type = room.room_type?.name || 'N/A';
                        opt.dataset.maxGuests = room.max_guests;
                        opt.dataset.hasCapacity = room.has_capacity ? '1' : '0';
                        opt.dataset.changeType = room.change_type;
                        opt.dataset.remainingNights = room.remaining_nights;
                        opt.dataset.totalDiff = room.total_price_difference;
                        opt.dataset.priceDiffPerNight = room.price_diff_per_night;
                        group.appendChild(opt);
                    });
                    newRoomSelect.appendChild(group);
                }
            })
            .catch(err => {
                newRoomSelect.innerHTML = '<option value="">Lỗi tải danh sách</option>';
                console.error(err);
            })
            .finally(() => {
                newRoomSelect.disabled = false;
            });
    }

    // Bước 3: Chọn phòng mới -> Hiện chênh lệch giá & thông tin nghiệp vụ
    newRoomSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];

        if (this.value && selected.dataset.price) {
            const newPrice = parseFloat(selected.dataset.price);
            const diffPerNight = parseFloat(selected.dataset.priceDiffPerNight || (newPrice - currentPricePerNight));
            const remainingNights = parseInt(selected.dataset.remainingNights || currentNights);
            const totalDiff = parseFloat(selected.dataset.totalDiff || (diffPerNight * remainingNights));
            const changeType = selected.dataset.changeType || 'same_grade';
            const maxGuests = parseInt(selected.dataset.maxGuests || 0);
            const hasCapacity = selected.dataset.hasCapacity === '1';

            newRoomDetails.classList.remove('d-none');
            document.getElementById('newDetailRoomName').textContent = selected.dataset.name;
            document.getElementById('newDetailRoomType').textContent = selected.dataset.type;
            document.getElementById('newDetailCapacity').textContent = maxGuests + ' khách';
            document.getElementById('newDetailPrice').textContent = new Intl.NumberFormat('vi-VN').format(newPrice) + ' ₫';
            document.getElementById('newDetailRemainingNights').textContent = remainingNights + ' đêm';

            // Chênh lệch / đêm
            const diffEl = document.getElementById('newDetailDiff');
            if (diffPerNight > 0) {
                diffEl.innerHTML = `<span class="text-danger">+${new Intl.NumberFormat('vi-VN').format(diffPerNight)} ₫</span>`;
            } else if (diffPerNight < 0) {
                diffEl.innerHTML = `<span class="text-success">${new Intl.NumberFormat('vi-VN').format(diffPerNight)} ₫</span>`;
            } else {
                diffEl.innerHTML = '<span class="text-muted">Không đổi</span>';
            }

            // Tổng chênh lệch
            const totalDiffEl = document.getElementById('totalDiff');
            if (totalDiff > 0) {
                totalDiffEl.innerHTML = `<span class="text-danger fw-bold">+${new Intl.NumberFormat('vi-VN').format(totalDiff)} ₫</span>`;
            } else if (totalDiff < 0) {
                totalDiffEl.innerHTML = `<span class="text-success fw-bold">${new Intl.NumberFormat('vi-VN').format(totalDiff)} ₫</span>`;
            } else {
                totalDiffEl.innerHTML = '<span class="text-muted fw-bold">Không đổi</span>';
            }

            // Loại đổi phòng
            const changeTypeLabels = {
                'same_grade': '<span class="badge bg-secondary">Cùng hạng</span> Không phụ phí',
                'upgrade': '<span class="badge bg-primary">Nâng hạng</span> Phí bổ sung = (Giá mới - Giá cũ) × ' + remainingNights + ' đêm',
                'downgrade': '<span class="badge bg-success">Hạ hạng</span> Hoàn tiền hoặc ghi credit',
            };
            changeTypeInfo.innerHTML = changeTypeLabels[changeType] || '';

            // Cảnh báo sức chứa
            if (!hasCapacity) {
                capacityWarning.classList.remove('d-none');
                capacityWarningText.textContent = `Phòng mới chỉ chứa tối đa ${maxGuests} khách, hiện có ${currentGuests} khách!`;
            } else {
                capacityWarning.classList.add('d-none');
            }

            btnSubmit.disabled = !hasCapacity;
        } else {
            newRoomDetails.classList.add('d-none');
            capacityWarning.classList.add('d-none');
            changeTypeInfo.innerHTML = '';
            btnSubmit.disabled = true;
        }
    });

    // Xử lý lý do tùy chọn
    function getReasonValue() {
        if (reasonSelect.value === 'Lý do khác' && reasonCustom.value.trim()) {
            return reasonCustom.value.trim();
        }
        return reasonSelect.value;
    }

    // Confirm trước khi submit
    document.getElementById('roomChangeForm').addEventListener('submit', function(e) {
        const newRoom = newRoomSelect.options[newRoomSelect.selectedIndex];
        const roomName = newRoom?.dataset?.name;
        const reason = getReasonValue();
        const isEmergencyChecked = isEmergency.checked;

        let confirmMsg = `Bạn có chắc muốn đổi sang phòng ${roomName}?`;
        if (isEmergencyChecked) {
            confirmMsg += '\n\n⚠️ BẠN ĐANG ĐÁNH DẤU KHẨN CẤP - Giới hạn giờ sẽ bị bỏ qua.';
        }
        if (!confirm(confirmMsg)) {
            e.preventDefault();
        } else {
            // Nếu lý do là "Lý do khác", thay thế giá trị reason
            if (reasonSelect.value === 'Lý do khác' && reasonCustom.value.trim()) {
                // Tạo hidden input với lý do custom
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'reason';
                hiddenInput.value = reasonCustom.value.trim();
                this.appendChild(hiddenInput);
                reasonSelect.removeAttribute('name');
            }
        }
    });

    // Tự load nếu đã có booking_id
    @if($booking)
        loadBooking();
    @endif
});
</script>
@endpush
