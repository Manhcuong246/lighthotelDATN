@extends('layouts.admin')

@section('title', 'Đổi phòng')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-4 px-0">
                    <li class="breadcrumb-item"><a href="{{ route('staff.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Đổi phòng</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(!$booking)
    <!-- Step 0: Chọn Booking -->
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary fw-bold">Tìm đơn đặt phòng cần đổi</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Chỉ các đơn đã <strong>Check-in</strong> mới hiện trong danh sách này.</p>
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="bookingSearchInput" class="form-control" placeholder="Nhập mã đơn, tên hoặc SĐT khách...">
                        <button class="btn btn-primary" type="button" id="btnSearchBooking">Tìm kiếm</button>
                    </div>
                    <div id="bookingSearchResults" class="list-group">
                        <!-- Kết quả tìm kiếm sẽ hiện ở đây -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <form action="{{ route('staff.room-changes.store') }}" method="POST" id="roomChangeForm">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->id }}">
        @if($currentBookingRoom)
        <input type="hidden" name="old_room_id" value="{{ $currentBookingRoom->room_id }}">
        <input type="hidden" name="old_price" value="{{ $currentBookingRoom->price_per_night }}">
        @endif

        <div class="row">
            <!-- Cột trái: Thông tin hiện tại -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary fw-bold">1. Thông tin đơn & Phòng hiện tại</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Mã Booking:</div>
                            <div class="col-sm-7 fw-bold">#{{ $booking->id }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Khách hàng:</div>
                            <div class="col-sm-7 fw-bold">{{ $booking->user->full_name ?? 'Khách lẻ' }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Số điện thoại:</div>
                            <div class="col-sm-7">{{ $booking->user->phone ?? 'N/A' }}</div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Thờii gian ở:</div>
                            <div class="col-sm-7">
                                {{ $booking->check_in->format('d/m/Y') }} - {{ $booking->check_out->format('d/m/Y') }}
                                <br><span class="badge bg-info-soft text-info mt-1">{{ $booking->nights }} đêm tổng cộng</span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Số đêm còn lại:</div>
                            <div class="col-sm-7 fw-bold text-danger">{{ $remainingNights }} đêm</div>
                        </div>
                        <div class="row mb-3 align-items-center">
                            <div class="col-sm-5 text-muted">Đang ở phòng:</div>
                            <div class="col-sm-7 fw-bold">
                                @if($booking->bookingRooms->count() > 1)
                                    <select class="form-select form-select-sm" onchange="window.location.href='{{ url('staff/room-changes/create/'.$booking->id) }}?room_id=' + this.value">
                                        @foreach($booking->bookingRooms as $br)
                                            <option value="{{ $br->room_id }}" {{ $currentBookingRoom->room_id == $br->room_id ? 'selected' : '' }}>
                                                {{ $br->room->room_number }} ({{ $br->room->roomType->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted fw-normal mt-1 d-block">Booking này có nhiều phòng. Hãy chọn phòng cần đổi.</small>
                                @else
                                    {{ $currentBookingRoom->room->room_number ?? 'N/A' }}
                                    ({{ $currentBookingRoom->room->roomType->name ?? 'N/A' }})
                                @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Giá hiện tại:</div>
                            <div class="col-sm-7">{{ number_format($currentBookingRoom->price_per_night ?? 0, 0, ',', '.') }} ₫ / đêm</div>
                        </div>
                        <div class="row mb-0">
                            <div class="col-sm-5 text-muted">Số khách hiện tại:</div>
                            <div class="col-sm-7">
                                <span class="badge bg-secondary">{{ $booking->guests()->count() }} Ngườii</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-primary fw-bold">2. Lý do đổi phòng</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <select name="reason" class="form-select" id="reasonSelect" required>
                                <option value="">-- Chọn lý do --</option>
                                <option value="Phòng bị lỗi">Phòng bị lỗi</option>
                                <option value="Khách yêu cầu">Khách yêu cầu</option>
                                <option value="Nâng cấp">Nâng cấp</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="mb-0 d-none" id="otherReasonDiv">
                            <textarea name="other_reason" class="form-control" placeholder="Nhập lý do chi tiết..." rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Chọn phòng mới -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary fw-bold">3. Chọn phòng mới</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefreshRooms">
                            <i class="bi bi-arrow-clockwise"></i> Làm mới
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="roomSelection" class="mb-4">
                            <div class="alert alert-light border mb-4">
                                <h6 class="fw-bold mb-2 small text-uppercase text-muted">Bộ lọc danh sách:</h6>
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <input type="text" id="searchRoom" class="form-control form-control-sm" placeholder="Tìm số phòng...">
                                    </div>
                                    <div class="col-md-4">
                                        <select id="filterType" class="form-select form-select-sm">
                                            <option value="">Tất cả loại phòng</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterPrice" class="form-select form-select-sm">
                                            <option value="">Sắp xếp giá</option>
                                            <option value="asc">Giá thấp đến cao</option>
                                            <option value="desc">Giá cao đến thấp</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover align-middle" id="roomsTable">
                                    <thead class="sticky-top bg-white">
                                        <tr class="small text-muted">
                                            <th>Phòng</th>
                                            <th>Loại</th>
                                            <th>Sức chứa</th>
                                            <th>Giá / Đêm</th>
                                            <th class="text-end">Chọn</th>
                                        </tr>
                                    </thead>
                                    <tbody id="roomsList">
                                        <tr>
                                            <td colspan="5" class="text-center py-4">Đang tải danh sách phòng...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Panel thanh toán dự kiến -->
                        <div id="predictionPanel" class="d-none">
                            <div class="bg-light rounded p-4 border">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Tính toán dự kiến ({{ $remainingNights }} đêm)</h6>
                                <div class="row g-3">
                                    <div class="col-md-6 border-end">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Chênh lệch giá phòng:</span>
                                            <span id="labelPriceDiff" class="fw-bold">0 ₫</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Phụ thu ngườii thêm:</span>
                                            <span id="labelSurcharge" class="fw-bold text-danger">0 ₫</span>
                                        </div>
                                        <div class="d-flex justify-content-between pt-2 border-top">
                                            <span class="h6 mb-0 fw-bold">Tổng chênh lệch:</span>
                                            <span id="labelTotalDiff" class="h6 mb-0 fw-bold text-primary">0 ₫</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 ps-md-4 d-flex flex-column justify-content-center">
                                        <div id="changeBadge" class="mb-2 text-center fs-5"></div>
                                        <p id="predictionNote" class="small text-muted mb-0 text-center"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <a href="{{ route('staff.room-changes.create') }}" class="btn btn-light px-4 me-2">Đổi đơn khác</a>
                                <button type="submit" class="btn btn-primary px-5" id="btnSubmit" disabled>Xác nhận đổi phòng</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden Inputs for logic -->
        <input type="hidden" name="new_room_id" id="inputNewRoomId">
        <input type="hidden" name="adults" value="{{ $booking->guests()->where('type', 'adult')->count() ?: 1 }}">
        <input type="hidden" name="children" value="{{ $booking->guests()->where('type', '!=', 'adult')->count() ?: 0 }}">
    </form>
    @endif
</div>

@push('styles')
<style>
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .table-hover tbody tr:hover { cursor: pointer; background-color: rgba(0, 123, 255, 0.05); }
    .selected-room { background-color: rgba(0, 123, 255, 0.1) !important; border-left: 4px solid #0d6efd; }
    .list-group-item-action:hover { background-color: #f8f9fa; }
    .sticky-top { z-index: 10; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(!$booking)
        // Logic tìm kiếm booking (Vanilla JS)
        let searchTimeout;

        function loadBookings(query = '') {
            const resultsContainer = document.getElementById('bookingSearchResults');
            if(!resultsContainer) return;

            resultsContainer.innerHTML = '<div class="list-group-item text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tìm kiếm...</div>';

            fetch("{{ route('staff.room-changes.search-booking') }}?q=" + encodeURIComponent(query))
                .then(response => response.json())
                .then(res => {
                    if(res.success) {
                        let html = '';
                        if(res.data.length === 0) {
                            html = '<div class="list-group-item text-center text-muted py-4">Không tìm thấy đơn nào đang check-in.</div>';
                        } else {
                            res.data.forEach(b => {
                                html += `
                                <a href="{{ url('staff/room-changes/create') }}/${b.id}?room_id=${b.room_id}" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold text-primary">#${b.id} - ${b.guest_name}</h6>
                                        <span class="badge bg-success">Phòng: ${b.room_number}</span>
                                    </div>
                                    <p class="mb-1 small text-muted"><i class="bi bi-telephone me-1"></i> ${b.phone}</p>
                                </a>
                                `;
                            });
                        }
                        resultsContainer.innerHTML = html;
                    }
                })
                .catch(error => {
                    resultsContainer.innerHTML = `<div class="list-group-item text-center text-danger py-4"><i class="bi bi-exclamation-triangle me-2"></i> Lỗi hệ thống: ${error.message}</div>`;
                });
        }

        const bookingSearchInput = document.getElementById('bookingSearchInput');
        const btnSearchBooking = document.getElementById('btnSearchBooking');

        if(bookingSearchInput) {
            bookingSearchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const q = this.value;
                searchTimeout = setTimeout(() => {
                    loadBookings(q);
                }, 300);
            });
        }

        if(btnSearchBooking) {
            btnSearchBooking.addEventListener('click', function() {
                const q = bookingSearchInput.value;
                loadBookings(q);
            });
        }

        loadBookings();

    @else
        // Logic thực hiện đổi phòng (Vanilla JS)
        const bookingId = {{ $booking->id }};
        const nightsRemaining = {{ $remainingNights }};
        const oldPrice = {{ $currentBookingRoom->price_per_night ?? 0 }};
        const totalAdults = {{ $booking->guests()->where('type', 'adult')->count() ?: 1 }};
        const totalChildren = {{ $booking->guests()->where('type', '!=', 'adult')->count() ?: 0 }};

        let allRooms = [];
        let selectedRoom = null;

        function loadRooms() {
            const roomsList = document.getElementById('roomsList');
            roomsList.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Đang tải...</td></tr>';

            const params = new URLSearchParams({
                booking_id: bookingId,
                total_adults: totalAdults,
                total_children: totalChildren
            });

            fetch("{{ route('staff.room-changes.available-rooms') }}?" + params.toString())
                .then(response => response.json())
                .then(res => {
                    if(res.success) {
                        allRooms = res.data;
                        renderRooms(allRooms);
                        updateTypeFilter(allRooms);
                    }
                })
                .catch(err => {
                    roomsList.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Lỗi tải danh sách phòng</td></tr>`;
                });
        }

        function renderRooms(rooms) {
            const roomsList = document.getElementById('roomsList');
            let html = '';
            if(rooms.length === 0) {
                html = '<tr><td colspan="5" class="text-center py-4">Không có phòng trống nào khả dụng.</td></tr>';
            } else {
                rooms.forEach(room => {
                    html += `
                    <tr class="room-row" data-id="${room.id}" style="cursor:pointer">
                        <td class="fw-bold">${room.room_number}</td>
                        <td>${room.room_type}</td>
                        <td><span class="badge bg-light text-dark">${room.capacity} Khách</span></td>
                        <td class="fw-bold text-primary">${new Intl.NumberFormat('vi-VN').format(room.price)} ₫</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-link p-0 fw-bold">Chọn</button>
                        </td>
                    </tr>
                    `;
                });
            }
            roomsList.innerHTML = html;

            // Re-attach event listeners to rows
            document.querySelectorAll('.room-row').forEach(row => {
                row.addEventListener('click', function() {
                    const roomId = this.getAttribute('data-id');
                    handleRoomSelection(roomId, this);
                });
            });
        }

        function handleRoomSelection(roomId, rowElement) {
            selectedRoom = allRooms.find(r => r.id == roomId);

            document.querySelectorAll('.room-row').forEach(r => r.classList.remove('selected-room'));
            rowElement.classList.add('selected-room');

            document.getElementById('inputNewRoomId').value = roomId;
            calculateFinal(selectedRoom);
            document.getElementById('predictionPanel').classList.remove('d-none');
            document.getElementById('btnSubmit').disabled = false;
        }

        function updateTypeFilter(rooms) {
            const types = [...new Set(rooms.map(r => r.room_type))];
            const filterType = document.getElementById('filterType');
            let options = '<option value="">Tất cả loại phòng</option>';
            types.forEach(t => options += `<option value="${t}">${t}</option>`);
            filterType.innerHTML = options;
        }

        function calculateFinal(room) {
            const priceDiff = (room.price - oldPrice) * nightsRemaining;
            const standardCapacity = room.standard_capacity || room.capacity;
            let surcharge = 0;
            const totalGuests = totalAdults + totalChildren;

            if (totalGuests > standardCapacity) {
                const extraAdults = Math.max(0, totalAdults - standardCapacity);
                const remainingForChildren = Math.max(0, standardCapacity - totalAdults);
                const extraChildren = Math.max(0, totalChildren - remainingForChildren);
                surcharge = (extraAdults * 200000 + extraChildren * 100000) * nightsRemaining;
            }

            const totalDiff = priceDiff + surcharge;
            const fmt = new Intl.NumberFormat('vi-VN');

            document.getElementById('labelPriceDiff').innerText = fmt.format(priceDiff) + ' ₫';
            document.getElementById('labelSurcharge').innerText = fmt.format(surcharge) + ' ₫';
            document.getElementById('labelTotalDiff').innerText = fmt.format(totalDiff) + ' ₫';

            let badgeHtml = '';
            if (room.price > oldPrice) {
                badgeHtml = '<span class="badge bg-primary px-3 py-2 rounded-pill"><i class="bi bi-graph-up-arrow me-1"></i> UPGRADE</span>';
            } else if (room.price < oldPrice) {
                badgeHtml = '<span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-graph-down-arrow me-1"></i> DOWNGRADE</span>';
            } else {
                badgeHtml = '<span class="badge bg-secondary px-3 py-2 rounded-pill">SAME GRADE</span>';
            }
            document.getElementById('changeBadge').innerHTML = badgeHtml;

            let note = '';
            if (totalDiff > 0) {
                note = 'Khách cần thanh toán thêm tổng cộng <strong>' + fmt.format(totalDiff) + ' ₫</strong>';
            } else if (totalDiff < 0) {
                note = 'Hệ thống sẽ ghi nhận hoàn trả <strong>' + fmt.format(Math.abs(totalDiff)) + ' ₫</strong>';
            } else {
                note = 'Không phát sinh thêm chi phí.';
            }
            document.getElementById('predictionNote').innerHTML = note;
        }

        // Filtering logic
        const searchRoom = document.getElementById('searchRoom');
        const filterType = document.getElementById('filterType');
        const filterPrice = document.getElementById('filterPrice');

        const applyFilters = function() {
            let filtered = allRooms.filter(r => {
                const searchVal = searchRoom.value.toLowerCase();
                const roomNum = String(r.room_number || '').toLowerCase();
                const roomType = String(r.room_type || '').toLowerCase();

                const matchSearch = roomNum.includes(searchVal) || roomType.includes(searchVal);
                const matchType = filterType.value === '' || r.room_type === filterType.value;

                return matchSearch && matchType;
            });
            if(filterPrice.value === 'asc') filtered.sort((a, b) => a.price - b.price);
            else if(filterPrice.value === 'desc') filtered.sort((a, b) => b.price - a.price);
            renderRooms(filtered);
        };

        if(searchRoom) searchRoom.addEventListener('input', applyFilters);
        if(filterType) filterType.addEventListener('change', applyFilters);
        if(filterPrice) filterPrice.addEventListener('change', applyFilters);

        // Reason select logic
        const reasonSelect = document.getElementById('reasonSelect');
        if(reasonSelect) {
            reasonSelect.addEventListener('change', function() {
                const otherReasonDiv = document.getElementById('otherReasonDiv');
                if(this.value === 'Khác') otherReasonDiv.classList.remove('d-none');
                else otherReasonDiv.classList.add('d-none');
            });
        }

        const btnRefreshRooms = document.getElementById('btnRefreshRooms');
        if(btnRefreshRooms) btnRefreshRooms.addEventListener('click', loadRooms);

        loadRooms();
    @endif
});
</script>
@endpush
@endsection
