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
                            <div class="form-text">Danh sách phòng trống trong khoảng thởi gian đặt</div>
                        </div>

                        <!-- Chi tiết phòng mới -->
                        <div id="newRoomDetails" class="d-none">
                            <div class="alert alert-info border">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-1"><strong>Phòng mới:</strong> <span id="newDetailRoomName">—</span></div>
                                        <div class="mb-1"><strong>Loại:</strong> <span id="newDetailRoomType">—</span></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-1"><strong>Giá/đêm:</strong> <span id="newDetailPrice">—</span></div>
                                        <div class="mb-1"><strong>Chênh lệch/đêm:</strong> <span id="newDetailDiff">—</span></div>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="text-end">
                                    <strong>Tổng chênh lệch:</strong> <span id="totalDiff" class="fs-5">—</span>
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
                            <textarea name="reason" class="form-control" rows="3" 
                                placeholder="Ví dụ: Khách yêu cầu phòng rộng hơn, Thiết bị phòng hỏng, Tiếng ồn..." 
                                required>{{ old('reason') }}</textarea>
                        </div>

                        <div class="alert alert-warning d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                            <div>
                                <strong>Lưu ý:</strong>
                                <ul class="mb-0 small">
                                    <li>Giá sẽ được tính lại theo giá phòng mới</li>
                                    <li>Lịch sử đổi phòng sẽ được ghi nhận</li>
                                    <li>Trạng thái phòng sẽ được cập nhật nếu đang trong kỳ lưu trú</li>
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

    let currentPricePerNight = 0;
    let currentNights = 0;

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
                    opt.textContent = `${room.room_name} - ${room.room_type} - ${new Intl.NumberFormat('vi-VN').format(room.price_per_night)}₫/đêm`;
                    opt.dataset.price = room.price_per_night;
                    opt.dataset.nights = room.nights;
                    opt.dataset.subtotal = room.subtotal;
                    opt.dataset.roomName = room.room_name;
                    opt.dataset.roomType = room.room_type;
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

                newRoomSelect.innerHTML = '<option value="">-- Chọn phòng mới --</option>';

                if (data.data.length === 0) {
                    newRoomSelect.innerHTML = '<option value="">Không có phòng trống</option>';
                    return;
                }

                const sameType = data.data.filter(r => r.is_same_type);
                const diffType = data.data.filter(r => !r.is_same_type);

                if (sameType.length > 0) {
                    const group = document.createElement('optgroup');
                    group.label = 'Cùng loại phòng (khuyến nghị)';
                    sameType.forEach(room => {
                        const opt = document.createElement('option');
                        opt.value = room.id;
                        opt.textContent = `${room.name} - ${room.room_type?.name || 'N/A'} (${new Intl.NumberFormat('vi-VN').format(room.base_price)}₫)`;
                        opt.dataset.price = room.base_price;
                        opt.dataset.name = room.name;
                        opt.dataset.type = room.room_type?.name || 'N/A';
                        group.appendChild(opt);
                    });
                    newRoomSelect.appendChild(group);
                }

                if (diffType.length > 0) {
                    const group = document.createElement('optgroup');
                    group.label = 'Khác loại phòng';
                    diffType.forEach(room => {
                        const opt = document.createElement('option');
                        opt.value = room.id;
                        opt.textContent = `${room.name} - ${room.room_type?.name || 'N/A'} (${new Intl.NumberFormat('vi-VN').format(room.base_price)}₫)`;
                        opt.dataset.price = room.base_price;
                        opt.dataset.name = room.name;
                        opt.dataset.type = room.room_type?.name || 'N/A';
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

    // Bước 3: Chọn phòng mới -> Hiện chênh lệch giá
    newRoomSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];

        if (this.value && selected.dataset.price) {
            const newPrice = parseFloat(selected.dataset.price);
            const diffPerNight = newPrice - currentPricePerNight;
            const totalDiff = diffPerNight * currentNights;

            newRoomDetails.classList.remove('d-none');
            document.getElementById('newDetailRoomName').textContent = selected.dataset.name;
            document.getElementById('newDetailRoomType').textContent = selected.dataset.type;
            document.getElementById('newDetailPrice').textContent = new Intl.NumberFormat('vi-VN').format(newPrice) + ' ₫';

            const diffEl = document.getElementById('newDetailDiff');
            if (diffPerNight > 0) {
                diffEl.innerHTML = `<span class="text-danger">+${new Intl.NumberFormat('vi-VN').format(diffPerNight)} ₫</span>`;
            } else if (diffPerNight < 0) {
                diffEl.innerHTML = `<span class="text-success">${new Intl.NumberFormat('vi-VN').format(diffPerNight)} ₫</span>`;
            } else {
                diffEl.innerHTML = '<span class="text-muted">Không đổi</span>';
            }

            const totalDiffEl = document.getElementById('totalDiff');
            if (totalDiff > 0) {
                totalDiffEl.innerHTML = `<span class="text-danger fw-bold">+${new Intl.NumberFormat('vi-VN').format(totalDiff)} ₫</span>`;
            } else if (totalDiff < 0) {
                totalDiffEl.innerHTML = `<span class="text-success fw-bold">${new Intl.NumberFormat('vi-VN').format(totalDiff)} ₫</span>`;
            } else {
                totalDiffEl.innerHTML = '<span class="text-muted fw-bold">Không đổi</span>';
            }

            btnSubmit.disabled = false;
        } else {
            newRoomDetails.classList.add('d-none');
            btnSubmit.disabled = true;
        }
    });

    // Confirm trước khi submit
    document.getElementById('roomChangeForm').addEventListener('submit', function(e) {
        const newRoom = newRoomSelect.options[newRoomSelect.selectedIndex];
        const roomName = newRoom?.dataset?.name;
        if (!confirm(`Bạn có chắc muốn đổi sang phòng ${roomName}?`)) {
            e.preventDefault();
        }
    });

    // Tự load nếu đã có booking_id
    @if($booking)
        loadBooking();
    @endif
});
</script>
@endpush
