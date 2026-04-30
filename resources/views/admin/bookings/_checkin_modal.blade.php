@php
    // Lấy thông tin người đại diện từ legacy guests (ưu tiên)
    $modalLegacyGuest = \App\Models\Guest::where('booking_id', $booking->id)
        ->where('is_representative', 1)
        ->first();
    $modalRepName = $modalLegacyGuest?->name ?? $booking->representative_name ?? $booking->user?->full_name ?? 'Chưa cập nhật';
    $modalRepCccd = $modalLegacyGuest?->cccd ?? $booking->cccd ?? 'Chưa cập nhật';
@endphp
{{-- Modal Check-in khách hàng - Đơn #{{ $booking->id }} - v1.2 --}}
<div class="modal fade" id="checkinModal{{ $booking->id }}" tabindex="-1" aria-labelledby="checkinModalLabel{{ $booking->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="checkinModalLabel{{ $booking->id }}">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Check-in - Đơn #{{ $booking->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ action([App\Http\Controllers\Admin\BookingAdminController::class, 'checkInWithAssignment'], $booking) }}" method="POST" id="checkinForm{{ $booking->id }}" novalidate>
                @csrf
                <div class="modal-body">
                    {{-- Thông tin booking --}}
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Người đại diện:</strong> {{ $modalRepName }}<br>
                                <strong>Email:</strong> {{ $booking->email ?? $booking->user?->email ?? 'Chưa cập nhật' }}<br>
                                <strong>CCCD:</strong> {{ $modalRepCccd }}
                            </div>
                            <div class="col-md-6">
                                <strong>Số phòng đã đặt:</strong> {{ $booking->bookingRooms->count() ?? 0 }} phòng<br>
                                <strong>Nhận phòng:</strong> {{ $booking->check_in?->format('d/m/Y') ?? '—' }}<br>
                                <strong>Trả phòng:</strong> {{ $booking->check_out?->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    {{-- Danh sách LOẠI PHÒNG đã đặt --}}
                    <div class="mb-3">
                        <h6><i class="bi bi-door-open me-2"></i>Loại phòng đã đặt:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @php
                                // Xử lý room types - gộp theo tên sau khi extract
                                $processedRoomTypes = $booking->bookingRooms->map(function($br) {
                                    $name = $br->room?->roomType?->name ?? 'Phòng';
                                    if (str_contains($name, ' ')) {
                                        $parts = explode(' ', $name);
                                        $name = end($parts);
                                    }
                                    return [
                                        'id' => $br->room?->room_type_id ?? $br->room_id ?? $br->id,
                                        'name' => $name,
                                    ];
                                });

                                // Đếm số lượng theo tên loại phòng
                                $roomTypeCounts = $processedRoomTypes->countBy('name');

                                // Lấy unique room types theo tên (giữ ID đầu tiên của mỗi loại)
                                $uniqueRoomTypes = $processedRoomTypes->unique('name');
                            @endphp
                            @foreach($uniqueRoomTypes as $rt)
                                <span class="badge bg-primary room-type-badge" data-room-type-id="{{ $rt['id'] }}">
                                    {{ $roomTypeCounts[$rt['name']] ?? 1 }} {{ $rt['name'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <hr>

                    {{-- Thông báo lỗi/validate --}}
                    <div id="validationErrors{{ $booking->id }}" class="alert alert-danger d-none">
                        <ul class="mb-0" id="errorList{{ $booking->id }}"></ul>
                    </div>

                    {{-- Danh sách khách hàng --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="bi bi-people me-2"></i>Danh sách khách:</h6>
                        <button type="button" class="btn btn-sm btn-success" onclick="addNewGuest({{ $booking->id }})">
                            <i class="bi bi-plus-circle me-1"></i>Thêm khách
                        </button>
                    </div>

                    {{-- Bảng khách lưu trú --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="guestTable{{ $booking->id }}">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px">STT</th>
                                    <th>Họ tên</th>
                                    <th style="width: 150px">CCCD</th>
                                    <th style="width: 100px">Loại phòng</th>
                                    <th style="width: 100px">Loại khách</th>
                                    <th style="width: 150px">Phòng</th>
                                    <th style="width: 80px">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="guestTableBody{{ $booking->id }}">
                                {{-- Dữ liệu sẽ được load bằng JS --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Template cho hàng khách mới (ẩn) --}}
                    <template id="guestRowTemplate{{ $booking->id }}">
                        <tr class="guest-row" data-guest-id="" data-is-new="true" data-is-representative="false" data-room-type-id="">
                            <td class="guest-stt"></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="text" name="guests[INDEX][name]" class="form-control form-control-sm guest-name-input" placeholder="Họ tên (bắt buộc nếu có)">
                                    <input type="hidden" name="guests[INDEX][id]" class="guest-id-input" value="">
                                    <input type="hidden" name="guests[INDEX][room_type_id]" class="guest-room-type-id-input" value="">
                                    <span class="badge bg-primary representative-badge d-none">Người đại diện</span>
                                </div>
                            </td>
                            <td>
                                <input type="tel" name="guests[INDEX][cccd]" class="form-control form-control-sm guest-cccd-input"
       placeholder="12 số CCCD" maxlength="12" pattern="[0-9]{12}"
       onkeypress="return event.charCode >= 48 && event.charCode <= 57"
       oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,12)">
                            </td>
                            <td>
                                <select name="guests[INDEX][room_type_id]" class="form-select form-select-sm guest-room-type-input" onchange="onRoomTypeChange(this, {{ $booking->id }})" data-room-type-id="">
                                    <option value="">-- Chọn loại --</option>
                                    @php
                                        // Lấy unique room types từ booking rooms
                                        $uniqueRoomTypes = $booking->bookingRooms->map(function($br) {
                                            return [
                                                'id' => $br->room?->room_type_id ?? $br->room_id ?? $br->id,
                                                'name' => $br->room?->roomType?->name ?? 'Phòng',
                                                'room_number' => $br->room?->room_number,
                                            ];
                                        })->unique('id');
                                    @endphp
                                    @foreach($uniqueRoomTypes as $rt)
                                        <option value="{{ $rt['id'] }}" data-room-type-id="{{ $rt['id'] }}">{{ $rt['name'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="guests[INDEX][type]" class="form-select form-select-sm guest-type-input">
                                    <option value="adult">Người lớn</option>
                                    <option value="child">Trẻ em</option>
                                </select>
                            </td>
                            <td>
                                <select name="guests[INDEX][room_id]" class="form-select form-select-sm guest-room-input" data-room-type-id="">
                                    <option value="">-- Chọn phòng --</option>
                                </select>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeGuestRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Đóng
                    </button>
                    <button type="submit" class="btn btn-info" id="checkinSubmitBtn{{ $booking->id }}">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Xác nhận Check-in
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Hàm giới hạn nhập CCCD chỉ 12 số
function limitCCCDInput(e) {
    const input = e.target;
    const key = e.key;

    // Chỉ cho phép nhập số (0-9)
    if (!/^[0-9]$/.test(key) && !['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(key)) {
        e.preventDefault();
        return;
    }

    // Ngăn không cho nhập thêm khi đã đủ 12 số (trừ các phim điều hướng)
    if (input.value.length >= 12 && /^[0-9]$/.test(key)) {
        e.preventDefault();
        return;
    }
}

// Load dữ liệu khách khi modal hiển thị
document.getElementById('checkinModal{{ $booking->id }}').addEventListener('show.bs.modal', function() {
    loadGuestsForBooking({{ $booking->id }});
});

// Store available rooms data for each room type
const availableRoomsData{{ $booking->id }} = {};

// Load danh sách khách từ server
function loadGuestsForBooking(bookingId) {
    const tbody = document.getElementById('guestTableBody' + bookingId);
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Đang tải...</td></tr>';

    // Load both guests and available rooms data
    Promise.all([
        fetch(`/admin/bookings/${bookingId}/checkin-data`).then(r => r.json()),
        fetch(`/admin/bookings/${bookingId}/available-rooms-by-type`).then(r => r.json()).catch(() => ({ rooms_by_type: {} }))
    ])
        .then(([guestData, roomsData]) => {
            console.log('guestData:', guestData);
            console.log('roomsData:', roomsData);

            // Kiểm tra dữ liệu trả về
            if (!guestData || typeof guestData !== 'object') {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Lỗi: Không nhận được dữ liệu khách</td></tr>';
                return;
            }

            tbody.innerHTML = '';

            // Store available rooms by room_type_id
            if (roomsData && roomsData.rooms_by_type) {
                Object.assign(availableRoomsData{{ $booking->id }}, roomsData.rooms_by_type);
                console.log('Available rooms loaded:', availableRoomsData{{ $booking->id }});
            } else {
                console.warn('No rooms_by_type in response:', roomsData);
            }

            // Xử lý guests - đảm bảo là array
            const guests = Array.isArray(guestData.guests) ? guestData.guests : [];
            const booking = guestData.booking || {};

            if (guests.length > 0) {
                guests.forEach((guest, index) => {
                    if (!guest.cccd && booking.representative_cccd) {
                        guest.cccd = booking.representative_cccd;
                    }
                    addGuestRow(bookingId, guest, index + 1);
                });
            } else {
                // Tạo khách từ booking info
                const roomTypes = document.querySelectorAll(`#checkinModal${bookingId} .room-type-badge`);
                if (roomTypes.length > 0) {
                    const firstBadge = roomTypes[0];
                    const roomTypeId = firstBadge?.dataset?.roomTypeId;
                    const roomTypeName = firstBadge?.textContent?.trim() || 'Phòng';

                    // Lấy tên và CCCD từ booking info (đã được controller xử lý)
                    const repName = booking.representative_name || 'Khách';
                    const repCccd = booking.representative_cccd || booking.cccd || '';

                    addGuestRow(bookingId, {
                        id: null,
                        name: repName,
                        cccd: repCccd,
                        room_type_id: roomTypeId,
                        room_type_name: roomTypeName,
                        type: 'adult',
                        is_representative: true,
                        status: 'pending'
                    }, 1);
                } else {
                    tbody.innerHTML = '<tr class="no-guests"><td colspan="7" class="text-center text-muted py-4">Chưa có khách nào. Vui lòng thêm khách.</td></tr>';
                }
            }
        })
        .catch(error => {
            console.error('Error loading guests:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Lỗi: ' + error.message + '</td></tr>';
        });
}

// Thêm hàng khách mới
function addNewGuest(bookingId) {
    const tbody = document.getElementById('guestTableBody' + bookingId);
    const rowCount = tbody.querySelectorAll('.guest-row').length;

    // Xóa dòng "Chưa có khách" nếu có
    const noGuestsRow = tbody.querySelector('.no-guests');
    if (noGuestsRow) {
        noGuestsRow.remove();
    }

    // Lấy room_type đầu tiên làm mặc định
    const firstRoomType = document.querySelector(`#checkinModal${bookingId} .room-type-badge`);
    const defaultRoomTypeId = firstRoomType?.dataset.roomTypeId || '';
    const defaultRoomTypeName = firstRoomType?.textContent.trim() || 'Phòng';

    addGuestRow(bookingId, {
        room_type_id: defaultRoomTypeId,
        room_type_name: defaultRoomTypeName,
        type: 'adult'
    }, rowCount + 1);
}

// Load phòng trống cho dropdown theo room_type_id
function loadAvailableRoomsForSelect(selectElement, roomTypeId, bookingId) {
    if (!roomTypeId || !selectElement) return;

    // Nếu là input ẩn thì không cần load
    if (selectElement.type === 'hidden') return;

    // Xử lý cả string và integer keys
    const roomsData = availableRoomsData{{ $booking->id }};
    let rooms = roomsData[roomTypeId] || roomsData[String(roomTypeId)] || roomsData[Number(roomTypeId)] || [];

    // Giữ option đầu tiên (placeholder)
    const firstOption = selectElement.options[0];
    selectElement.innerHTML = '';
    if (firstOption) {
        selectElement.appendChild(firstOption);
    }

    // Thêm các phòng trống
    rooms.forEach(room => {
        const option = document.createElement('option');
        option.value = room.id;
        option.textContent = room.room_number;
        selectElement.appendChild(option);
    });

    // Nếu không có phòng trống
    if (rooms.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Không có phòng trống';
        option.disabled = true;
        selectElement.appendChild(option);
    }
    // Không tự động chọn phòng - để người dùng tự chọn
}

// Khi đổi loại phòng, cập nhật danh sách phòng
function onRoomTypeChange(roomTypeSelect, bookingId) {
    const row = roomTypeSelect.closest('tr');
    const roomSelect = row.querySelector('.guest-room-input');
    const roomTypeId = roomTypeSelect.value;

    if (!roomTypeId) return;

    // Cập nhật data attribute
    roomSelect.dataset.roomTypeId = roomTypeId;

    // Load phòng mới theo loại phòng
    loadAvailableRoomsForSelect(roomSelect, roomTypeId, bookingId);

    // Reset giá trị phòng đã chọn
    roomSelect.value = '';
}

// Thêm hàng khách vào bảng
function addGuestRow(bookingId, guestData, stt) {
    const tbody = document.getElementById('guestTableBody' + bookingId);
    const template = document.getElementById('guestRowTemplate' + bookingId);
    const clone = template.content.cloneNode(true);
    const row = clone.querySelector('tr');

    // Gán số thứ tự
    row.querySelector('.guest-stt').textContent = stt;

    // Tạo index unique cho input names
    const index = Date.now() + '_' + stt;

    // Cập nhật name attributes
    row.querySelector('.guest-name-input').name = `guests[${index}][name]`;
    row.querySelector('.guest-cccd-input').name = `guests[${index}][cccd]`;
    row.querySelector('.guest-type-input').name = `guests[${index}][type]`;
    row.querySelector('.guest-id-input').name = `guests[${index}][id]`;
    row.querySelector('.guest-room-type-input').name = `guests[${index}][room_type_id]`;
    row.querySelector('.guest-room-input').name = `guests[${index}][room_id]`;

    // Áp dụng giới hạn cho input CCCD
    const cccdInput = row.querySelector('.guest-cccd-input');
    cccdInput.addEventListener('keypress', function(e) {
        return e.charCode >= 48 && e.charCode <= 57;
    });
    cccdInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g,'').slice(0,12);
    });
    cccdInput.addEventListener('paste', function(e) {
        e.preventDefault();
        return false;
    });
    cccdInput.addEventListener('drop', function(e) {
        e.preventDefault();
        return false;
    });

    // Điền dữ liệu nếu có
    if (guestData) {
        row.dataset.guestId = guestData.id || '';
        row.dataset.isNew = guestData.id ? 'false' : 'true';
        row.dataset.isRepresentative = guestData.is_representative ? 'true' : 'false';
        row.dataset.roomTypeId = guestData.room_type_id || '';

        row.querySelector('.guest-name-input').value = guestData.name || '';
        row.querySelector('.guest-cccd-input').value = guestData.cccd || '';
        row.querySelector('.guest-type-input').value = guestData.type || 'adult';
        row.querySelector('.guest-id-input').value = guestData.id || '';

        // Chọn loại phòng
        const roomTypeSelect = row.querySelector('.guest-room-type-input');
        if (roomTypeSelect && guestData.room_type_id) {
            roomTypeSelect.value = guestData.room_type_id;
        }

        // Hiển thị badge người đại diện
        if (guestData.is_representative) {
            row.querySelector('.representative-badge').classList.remove('d-none');
            row.querySelector('.guest-name-input').readOnly = true;
            row.querySelector('.guest-cccd-input').readOnly = true;
        }

        // Disable input cho khách đã check-in
        if (guestData.status === 'checked_in') {
            row.classList.add('table-success');
            row.querySelector('.guest-name-input').readOnly = true;
            row.querySelector('.guest-cccd-input').readOnly = true;
            row.querySelector('.btn-danger').disabled = true;
            row.querySelector('.btn-danger').classList.replace('btn-danger', 'btn-secondary');
        }
    }

    // Đã bỏ phần xử lý room select vì dùng mainRoomSelect chung

    tbody.appendChild(row);

    // Load danh sách phòng vào dropdown SAU KHI đã thêm vào DOM
    const roomSelect = row.querySelector('.guest-room-input');
    const roomTypeSelect = row.querySelector('.guest-room-type-input');

    if (roomTypeSelect && guestData.room_type_id) {
        roomTypeSelect.value = guestData.room_type_id;
    }

    if (roomSelect && guestData.room_type_id) {
        roomSelect.dataset.roomTypeId = guestData.room_type_id;
        loadAvailableRoomsForSelect(roomSelect, guestData.room_type_id, bookingId);

        // Chỉ chọn phòng nếu guest đã có room_id từ trước (edit mode)
        if (guestData.room_id && guestData.id) {
            setTimeout(() => {
                roomSelect.value = guestData.room_id;
            }, 100);
        }
    }

    renumberGuests(bookingId);
}

// Kiểm tra trùng phòng - đã bỏ vì dùng chung 1 phòng cho tất cả
function validateDuplicateRooms(bookingId) {
    return true; // Luôn trả về true vì dùng mainRoomSelect chung
}

// Xóa hàng khách
function removeGuestRow(btn) {
    const row = btn.closest('tr');
    const bookingId = row.closest('table').id.replace('guestTableBody', '');

    // Không cho xóa người đại diện
    if (row.dataset.isRepresentative === 'true') {
        alert('Không thể xóa người đại diện!');
        return;
    }

    // Nếu là khách đã có trong DB (có ID), cần gửi request xóa
    const guestId = row.dataset.guestId;
    if (guestId && row.dataset.isNew === 'false') {
        if (!confirm('Bạn có chắc muốn xóa khách này?')) {
            return;
        }
        fetch(`/admin/booking-guests/${guestId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        }).then(() => {
            row.remove();
            renumberGuests(bookingId);
        });
    } else {
        row.remove();
        renumberGuests(bookingId);
    }
}

// Đánh số lại STT
function renumberGuests(bookingId) {
    const tbody = document.getElementById('guestTableBody' + bookingId);
    const rows = tbody.querySelectorAll('.guest-row');

    if (rows.length === 0) {
        tbody.innerHTML = '<tr class="no-guests"><td colspan="7" class="text-center text-muted py-4">Chưa có khách nào. Vui lòng thêm khách.</td></tr>';
        return;
    }

    rows.forEach((row, index) => {
        row.querySelector('.guest-stt').textContent = index + 1;
    });
}

// Validate trước khi submit
document.getElementById('checkinForm{{ $booking->id }}').addEventListener('submit', function(e) {
    const bookingId = {{ $booking->id }};
    const tbody = document.getElementById('guestTableBody' + bookingId);
    const rows = tbody.querySelectorAll('.guest-row');
    const errors = [];

    // Kiểm tra từng khách
    rows.forEach((row, index) => {
        const name = row.querySelector('.guest-name-input').value.trim();
        const cccd = row.querySelector('.guest-cccd-input').value.trim();
        const room = row.querySelector('.guest-room-input').value;

        // Reset validation
        row.querySelector('.guest-name-input').classList.remove('is-invalid');
        row.querySelector('.guest-cccd-input').classList.remove('is-invalid');
        row.querySelector('.guest-room-input').classList.remove('is-invalid');

        if (!name) {
            errors.push(`Khách ${index + 1}: Thiếu họ tên`);
            row.querySelector('.guest-name-input').classList.add('is-invalid');
        }

        // CCCD phải đúng 12 số nếu có nhập
        if (cccd && !/^\d{12}$/.test(cccd)) {
            errors.push(`Khách ${index + 1}: CCCD phải có đúng 12 số`);
            row.querySelector('.guest-cccd-input').classList.add('is-invalid');
        }

        // Kiểm tra đã chọn phòng chưa
        if (!room) {
            errors.push(`Khách ${index + 1}: Chưa chọn phòng`);
            row.querySelector('.guest-room-input').classList.add('is-invalid');
        }
    });

    // Hiển thị lỗi
    const errorDiv = document.getElementById('validationErrors' + bookingId);
    const errorList = document.getElementById('errorList' + bookingId);

    if (errors.length > 0) {
        e.preventDefault();
        errorList.innerHTML = errors.map(err => `<li>${err}</li>`).join('');
        errorDiv.classList.remove('d-none');
        return false;
    } else {
        errorDiv.classList.add('d-none');
    }
});
</script>
