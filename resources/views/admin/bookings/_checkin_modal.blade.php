{{-- Modal Check-in khách hàng - Đơn #{{ $booking->id }} --}}
<div class="modal fade" id="checkinModal{{ $booking->id }}" tabindex="-1" aria-labelledby="checkinModalLabel{{ $booking->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="checkinModalLabel{{ $booking->id }}">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Check-in - Đơn #{{ $booking->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ action([App\Http\Controllers\Admin\BookingAdminController::class, 'checkInWithAssignment'], $booking) }}" method="POST" id="checkinForm{{ $booking->id }}">
                @csrf
                <div class="modal-body">
                    {{-- Thông tin booking --}}
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Người đại diện:</strong> {{ $booking->user?->full_name ?? '—' }}<br>
                                <strong>Email:</strong> {{ $booking->user?->email ?? '—' }}<br>
                                <strong>CCCD:</strong> 
                                @php
                                    // Thu 1: Lay truc tiep tu booking
                                    $cccd = $booking->cccd ?? null;
                                    
                                    // Thu 2: Tim trong booking guests neu booking khong co
                                    if (!$cccd) {
                                        $representativeGuest = $booking->bookingGuests()->where('is_representative', true)->first();
                                        $cccd = $representativeGuest?->cccd ?? null;
                                    }
                                    
                                    // Thu 3: Thu tim trong booking user
                                    if (!$cccd && $booking->user) {
                                        $cccd = $booking->user->cccd ?? null;
                                    }
                                    
                                    // Thu 4: Tim khach dau tien trong booking neu chua co
                                    if (!$cccd && $booking->bookingGuests->isNotEmpty()) {
                                        $firstGuest = $booking->bookingGuests->first();
                                        $cccd = $firstGuest?->cccd ?? null;
                                    }
                                    
                                    $cccdDisplay = $cccd ?? '—';
                                @endphp
                                {{ $cccdDisplay }}
                            </div>
                            <div class="col-md-6">
                                <strong>Số phòng đã đặt:</strong> {{ $booking->rooms->count() ?? 0 }} phòng<br>
                                <strong>Nhận phòng:</strong> {{ $booking->check_in?->format('d/m/Y') ?? '—' }}<br>
                                <strong>Trả phòng:</strong> {{ $booking->check_out?->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    {{-- Danh sách phòng đã đặt --}}
                    <div class="mb-3">
                        <h6><i class="bi bi-door-open me-2"></i>Phòng:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($booking->rooms as $room)
                                <span class="badge bg-primary">
                                    {{ $room?->room_number ?? '—' }} ({{ $room?->roomType?->name ?? '—' }})
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
                        <h6 class="mb-0"><i class="bi bi-people me-2"></i>Danh sách khách hàng:</h6>
                        <button type="button" class="btn btn-sm btn-success" onclick="addNewGuest({{ $booking->id }})">
                            <i class="bi bi-plus-circle me-1"></i>Thêm khách
                        </button>
                    </div>

                    {{-- Bảng khách hàng --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="guestTable{{ $booking->id }}">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px">STT</th>
                                    <th>Họ tên <span class="text-danger">*</span></th>
                                    <th style="width: 150px">CCCD <span class="text-danger">*</span></th>
                                    <th style="width: 100px">Loại</th>
                                    <th style="width: 200px">Phòng <span class="text-danger">*</span></th>
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
                        <tr class="guest-row" data-guest-id="" data-is-new="true" data-is-representative="false">
                            <td class="guest-stt"></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="text" name="guests[INDEX][name]" class="form-control form-control-sm guest-name-input" placeholder="Họ tên" required>
                                    <input type="hidden" name="guests[INDEX][id]" class="guest-id-input" value="">
                                    <span class="badge bg-primary representative-badge d-none">Người đại diện</span>
                                </div>
                            </td>
                            <td>
                                <input type="tel" name="guests[INDEX][cccd]" class="form-control form-control-sm guest-cccd-input" 
       placeholder="12 số CCCD" maxlength="12" pattern="[0-9]{12}" required 
       onkeypress="return event.charCode >= 48 && event.charCode <= 57"
       oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,12)"
       onpaste="return false"
       ondrop="return false">
                            </td>
                            <td>
                                <select name="guests[INDEX][type]" class="form-select form-select-sm guest-type-input">
                                    <option value="adult">Người lớn</option>
                                    <option value="child">Trẻ em</option>
                                </select>
                            </td>
                            <td class="guest-room-cell">
                                <div class="guest-slot-wrap mb-1"></div>
                                <input type="hidden" name="guests[INDEX][booking_room_id]" class="guest-booking-room-id-input" value="">
                                <select name="guests[INDEX][room_id]" class="form-select form-select-sm guest-room-input" required>
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
window.__checkInMeta = window.__checkInMeta || {};

function checkInEscapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text == null ? '' : String(text);
    return div.innerHTML;
}

function getCheckInBookingRoomsMeta(bookingId) {
    const m = window.__checkInMeta[bookingId];
    return (m && m.booking_rooms) ? m.booking_rooms : [];
}

function fillGuestRoomSelect(roomSelect, bookingRoomId, selectedRoomId, bookingId) {
    const meta = getCheckInBookingRoomsMeta(bookingId);
    const br = meta.find(x => String(x.id) === String(bookingRoomId));
    roomSelect.innerHTML = '<option value="">-- Chọn phòng --</option>';
    if (!br || !Array.isArray(br.room_options)) {
        return;
    }
    br.room_options.forEach(opt => {
        const o = document.createElement('option');
        o.value = opt.room_id;
        o.textContent = opt.label || ('#' + opt.room_id);
        if (selectedRoomId != null && String(opt.room_id) === String(selectedRoomId)) {
            o.selected = true;
        }
        roomSelect.appendChild(o);
    });
}

function setupGuestRoomControls(row, bookingId, guestData) {
    const meta = getCheckInBookingRoomsMeta(bookingId);
    const slotWrap = row.querySelector('.guest-slot-wrap');
    const hiddenBrid = row.querySelector('.guest-booking-room-id-input');
    const roomSel = row.querySelector('.guest-room-input');
    if (!slotWrap || !hiddenBrid || !roomSel) {
        return;
    }

    slotWrap.innerHTML = '';

    const frozen = guestData && guestData.status === 'checked_in';

    if (!meta.length) {
        hiddenBrid.value = '';
        roomSel.innerHTML = '<option value="">-- Chưa có dòng đặt --</option>';
        return;
    }

    if (meta.length === 1) {
        hiddenBrid.value = meta[0].id;
        fillGuestRoomSelect(roomSel, meta[0].id, guestData ? guestData.room_id : null, bookingId);
        if (frozen) {
            roomSel.disabled = true;
        }
        return;
    }

    const sel = document.createElement('select');
    sel.className = 'form-select form-select-sm guest-slot-input mb-1';
    if (!frozen) {
        sel.required = true;
    }
    sel.innerHTML = '<option value="">-- Chọn dòng đặt --</option>' +
        meta.map(br => {
            const label = (br.slot_label || 'Phòng') + (br.room_name ? ' — ' + br.room_name : '');
            return '<option value="' + br.id + '">' + checkInEscapeHtml(label) + '</option>';
        }).join('');

    const brid = guestData && guestData.booking_room_id ? String(guestData.booking_room_id) : '';

    sel.addEventListener('change', function() {
        hiddenBrid.value = sel.value;
        fillGuestRoomSelect(roomSel, sel.value, null, bookingId);
    });

    slotWrap.appendChild(sel);

    if (frozen) {
        sel.disabled = true;
        hiddenBrid.value = brid || '';
        if (brid) {
            sel.value = brid;
        }
        fillGuestRoomSelect(roomSel, brid || meta[0].id, guestData.room_id, bookingId);
        roomSel.disabled = true;
        return;
    }

    hiddenBrid.value = brid;
    if (brid) {
        sel.value = brid;
        fillGuestRoomSelect(roomSel, brid, guestData.room_id, bookingId);
    } else {
        fillGuestRoomSelect(roomSel, '', null, bookingId);
    }
}

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

// Load danh sách khách từ server
function loadGuestsForBooking(bookingId) {
    const tbody = document.getElementById('guestTableBody' + bookingId);
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Đang tải...</td></tr>';

    fetch(`/admin/bookings/${bookingId}/checkin-data`)
        .then(response => response.json().catch(() => ({ error: 'Phản hồi không hợp lệ (' + response.status + ')' })))
        .then(data => {
            tbody.innerHTML = '';

            window.__checkInMeta[bookingId] = {
                booking_rooms: data.booking_rooms || [],
            };

            if (data.error) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">' + checkInEscapeHtml(data.error) + '</td></tr>';
                return;
            }

            if (data.guests && data.guests.length > 0) {
                data.guests.forEach((guest, index) => {
                    addGuestRow(bookingId, guest, index + 1);
                });
            } else {
                // Nếu chưa có khách nào, thêm form trống
                tbody.innerHTML = '<tr class="no-guests"><td colspan="6" class="text-center text-muted py-4">Chưa có khách nào. Vui lòng thêm khách.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading guests:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Lỗi tải dữ liệu. Vui lòng thử lại.</td></tr>';
        });
}

// Thêm hàng khách mới
function addNewGuest(bookingId) {
    const tbody = document.getElementById('guestTableBody' + bookingId);
    const rowCount = tbody.querySelectorAll('.guest-row').length;

    const meta = getCheckInBookingRoomsMeta(bookingId);
    if (!meta.length) {
        alert('Chưa tải được danh sách phòng. Đóng modal và mở lại Check-in.');
        return;
    }

    // Xóa dòng "Chưa có khách" nếu có
    const noGuestsRow = tbody.querySelector('.no-guests');
    if (noGuestsRow) {
        noGuestsRow.remove();
    }

    addGuestRow(bookingId, null, rowCount + 1);
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
    row.querySelector('.guest-booking-room-id-input').name = `guests[${index}][booking_room_id]`;
    row.querySelector('.guest-room-input').name = `guests[${index}][room_id]`;
    row.querySelector('.guest-id-input').name = `guests[${index}][id]`;
    
    // Áp dụng giới hạn cho input CCCD
    const cccdInput = row.querySelector('.guest-cccd-input');
    // Ngăn nhập chữ
    cccdInput.addEventListener('keypress', function(e) {
        return e.charCode >= 48 && e.charCode <= 57;
    });
    // Xử lý input
    cccdInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g,'').slice(0,12);
    });
    // Ngăn paste
    cccdInput.addEventListener('paste', function(e) {
        e.preventDefault();
        return false;
    });
    // Ngăn drag/drop
    cccdInput.addEventListener('drop', function(e) {
        e.preventDefault();
        return false;
    });

    // Điền dữ liệu nếu có
    if (guestData) {
        row.dataset.guestId = guestData.id;
        row.dataset.isNew = 'false';
        row.dataset.isRepresentative = guestData.is_representative ? 'true' : 'false';

        row.querySelector('.guest-name-input').value = guestData.name || '';
        row.querySelector('.guest-cccd-input').value = guestData.cccd || '';
        row.querySelector('.guest-type-input').value = guestData.type || 'adult';
        row.querySelector('.guest-id-input').value = guestData.id || '';

        // Hiển thị badge người đại diện
        if (guestData.is_representative) {
            row.querySelector('.representative-badge').classList.remove('d-none');
            // Readonly input cho người đại diện (không cho sửa)
            row.querySelector('.guest-name-input').readOnly = true;
            row.querySelector('.guest-cccd-input').readOnly = true;
        }

        // Disable input cho khách đã check-in (nếu cần)
        if (guestData.status === 'checked_in') {
            row.classList.add('table-success');
            row.querySelector('.guest-name-input').readOnly = true;
            row.querySelector('.guest-cccd-input').readOnly = true;
            row.querySelector('.guest-type-input').disabled = true;
            row.querySelector('.btn-danger').disabled = true;
            row.querySelector('.btn-danger').classList.replace('btn-danger', 'btn-secondary');
        }
    }

    tbody.appendChild(row);
    setupGuestRoomControls(row, bookingId, guestData || null);
    renumberGuests(bookingId);
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
        // Xóa qua AJAX
        fetch(`/admin/booking-guests/${guestId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
        tbody.innerHTML = '<tr class="no-guests"><td colspan="6" class="text-center text-muted py-4">Chưa có khách nào. Vui lòng thêm khách.</td></tr>';
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

    // Kiểm tra có khách không
    if (rows.length === 0) {
        errors.push('Vui lòng thêm ít nhất 1 khách');
    }

    // Kiểm tra từng khách
    rows.forEach((row, index) => {
        const name = row.querySelector('.guest-name-input').value.trim();
        const cccd = row.querySelector('.guest-cccd-input').value.trim();
        const room = row.querySelector('.guest-room-input').value;
        const bookingRoomHidden = row.querySelector('.guest-booking-room-id-input');
        const slotSelect = row.querySelector('.guest-slot-input');
        const meta = getCheckInBookingRoomsMeta(bookingId);
        const brid = bookingRoomHidden ? bookingRoomHidden.value.trim() : '';

        if (!name) {
            errors.push(`Khách ${index + 1}: Thiếu họ tên`);
            row.querySelector('.guest-name-input').classList.add('is-invalid');
        } else {
            row.querySelector('.guest-name-input').classList.remove('is-invalid');
        }

        if (!cccd) {
            errors.push(`Khách ${index + 1}: Thiếu CCCD`);
            row.querySelector('.guest-cccd-input').classList.add('is-invalid');
        } else if (!/^\d{12}$/.test(cccd)) {
            errors.push(`Khách ${index + 1}: CCCD phải có 12 số`);
            row.querySelector('.guest-cccd-input').classList.add('is-invalid');
        } else {
            row.querySelector('.guest-cccd-input').classList.remove('is-invalid');
        }

        if (meta.length > 1 && !brid) {
            errors.push(`Khách ${index + 1}: Chưa chọn dòng đặt (slot phòng)`);
            if (slotSelect) {
                slotSelect.classList.add('is-invalid');
            }
        } else if (slotSelect) {
            slotSelect.classList.remove('is-invalid');
        }

        if (!room) {
            errors.push(`Khách ${index + 1}: Chưa chọn phòng cụ thể`);
            row.querySelector('.guest-room-input').classList.add('is-invalid');
        } else {
            row.querySelector('.guest-room-input').classList.remove('is-invalid');
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
    }

    errorDiv.classList.add('d-none');
    tbody.querySelectorAll('.guest-row select[disabled], .guest-row input[disabled]').forEach(function(el) {
        el.disabled = false;
    });
});
</script>
