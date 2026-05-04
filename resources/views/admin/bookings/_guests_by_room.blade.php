{{-- Hiển thị danh sách khách được nhóm theo phòng cụ thể --}}
@props(['booking', 'guestsByRoom' => []])

<div class="guests-by-room-container">
    <h6 class="mb-3 d-flex align-items-center">
        <i class="bi bi-door-open me-2"></i>
        Phân phòng cho khách
        <span class="badge bg-info ms-2" id="assignmentStatus{{ $booking->id }}">Chưa hoàn thành</span>
    </h6>

    @if(empty($guestsByRoom))
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Chưa có thông tin khách hàng.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th style="width: 200px;">Phòng</th>
                        <th>Tên khách hàng</th>
                        <th>Loại</th>
                        <th>CCCD</th>
                        <th style="width: 100px;">Xác nhận</th>
                    </tr>
                </thead>
                <tbody>
                    @php $stt = 1; @endphp
                    @foreach($guestsByRoom as $roomGroup)
                        @php
                            $room = $roomGroup['room'];
                            $guests = $roomGroup['guests'];
                            $roomDisplay = $room
                                ? (($room->room_number ?? $room->name ?? '#' . $room->id) . ' (' . ($room->roomType?->name ?? 'Phòng') . ')')
                                : 'Chưa gán phòng';
                            $roomClass = $room ? 'table-success' : 'table-warning';
                        @endphp

                        {{-- Header cho mỗi phòng --}}
                        <tr class="{{ $roomClass }}">
                            <td colspan="6" class="fw-bold">
                                <i class="bi bi-door-closed me-2"></i>
                                {{ $roomDisplay }}
                                @if($room)
                                    <span class="badge bg-success ms-2">Đã gán</span>
                                @else
                                    <span class="badge bg-warning ms-2">Chưa gán</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Danh sách khách trong phòng --}}
                        @foreach($guests as $guest)
                            <tr data-guest-id="{{ $guest->id }}">
                                <td>{{ $stt++ }}</td>
                                <td>
                                    @if(!$room)
                                        {{-- Chưa gán phòng -> hiển thị dropdown chọn phòng --}}
                                        <select class="form-select form-select-sm room-assign-select"
                                                data-guest-id="{{ $guest->id }}"
                                                data-booking-id="{{ $booking->id }}">
                                            <option value="">-- Chọn phòng --</option>
                                            {{-- Options sẽ được load bằng JS --}}
                                        </select>
                                    @else
                                        <span class="text-success">
                                            <i class="bi bi-check-circle me-1"></i>
                                            {{ $roomDisplay }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $guest->name }}</td>
                                @php
                                    $gType = \App\Models\BookingGuest::normalizeTypeForStorage($guest->type ?? 'adult');
                                    $gBadge = $gType === 'adult' ? 'primary' : ($gType === 'child_6_11' ? 'warning text-dark' : 'info');
                                @endphp
                                <td>
                                    <span class="badge bg-{{ $gBadge }}">
                                        {{ \App\Models\BookingGuest::typeLabel($guest->type) }}
                                    </span>
                                </td>
                                <td>{{ $guest->cccd ?? '-' }}</td>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input guest-confirmation"
                                               type="checkbox"
                                               id="guestConfirm{{ $guest->id }}"
                                               data-guest-id="{{ $guest->id }}"
                                               {{ $guest->checkin_status === 'checked_in' ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tóm tắt --}}
        <div class="alert alert-light mt-3 border">
            <div class="row text-center">
                <div class="col-md-3">
                    <strong>Tổng khách:</strong>
                    <span class="badge bg-secondary" id="totalGuests{{ $booking->id }}">
                        {{ collect($guestsByRoom)->pluck('guests')->flatten()->count() }}
                    </span>
                </div>
                <div class="col-md-3">
                    <strong>Đã gán phòng:</strong>
                    <span class="badge bg-success" id="assignedGuests{{ $booking->id }}">0</span>
                </div>
                <div class="col-md-3">
                    <strong>Chưa gán phòng:</strong>
                    <span class="badge bg-warning" id="unassignedGuests{{ $booking->id }}">0</span>
                </div>
                <div class="col-md-3">
                    <strong>Đã xác nhận:</strong>
                    <span class="badge bg-info" id="confirmedGuests{{ $booking->id }}">0</span>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // JavaScript xử lý gán phòng
    document.addEventListener('DOMContentLoaded', function() {
        const bookingId = {{ $booking->id }};
        const modal = document.getElementById('checkinModal{{ $booking->id }}');

        if (!modal) return;

        // Load danh sách phòng có thể gán
        loadAvailableRooms(bookingId);

        // Xử lý khi chọn phòng
        modal.addEventListener('change', function(e) {
            if (e.target.classList.contains('room-assign-select')) {
                const guestId = e.target.dataset.guestId;
                const roomId = e.target.value;

                if (roomId) {
                    assignRoomToGuest(bookingId, guestId, roomId, e.target);
                }
            }
        });

        // Cập nhật số liệu
        updateAssignmentStats(bookingId);
    });

    // Load danh sách phòng từ API
    function loadAvailableRooms(bookingId) {
        fetch(`/admin/bookings/${bookingId}/available-rooms`)
            .then(r => r.json())
            .then(data => {
                if (data.rooms) {
                    const selects = document.querySelectorAll(`[data-booking-id="${bookingId}"].room-assign-select`);
                    selects.forEach(select => {
                        // Giữ option đầu tiên
                        const firstOption = select.options[0];
                        select.innerHTML = '';
                        select.appendChild(firstOption);

                        // Thêm các phòng
                        data.rooms.forEach(room => {
                            const option = document.createElement('option');
                            option.value = room.id;
                            option.textContent = `${room.room_number} (${room.room_type}) - Tối đa ${room.max_guests} khách`;
                            select.appendChild(option);
                        });
                    });
                }
            })
            .catch(err => console.error('Error loading rooms:', err));
    }

    // Gán phòng cho khách
    function assignRoomToGuest(bookingId, guestId, roomId, selectElement) {
        fetch(`/admin/bookings/${bookingId}/assign-room`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ guest_id: guestId, room_id: roomId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Thay dropdown bằng text xác nhận
                const cell = selectElement.closest('td');
                cell.innerHTML = `
                    <span class="text-success">
                        <i class="bi bi-check-circle me-1"></i>
                        ${data.guest.room_display}
                    </span>
                `;

                // Cập nhật stats
                updateAssignmentStats(bookingId);

                // Hiển thị thông báo
                showToast('Đã gán phòng thành công!', 'success');
            } else {
                showToast(data.error || 'Có lỗi xảy ra', 'error');
                selectElement.value = '';
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast('Có lỗi xảy ra khi gán phòng', 'error');
            selectElement.value = '';
        });
    }

    // Cập nhật số liệu
    function updateAssignmentStats(bookingId) {
        const modal = document.getElementById(`checkinModal${bookingId}`);
        if (!modal) return;

        const rows = modal.querySelectorAll('tbody tr[data-guest-id]');
        const total = rows.length;
        const assigned = modal.querySelectorAll('.room-assign-select').length === 0
            ? total
            : total - modal.querySelectorAll('.room-assign-select').length;
        const unassigned = total - assigned;
        const confirmed = modal.querySelectorAll('.guest-confirmation:checked').length;

        const totalEl = document.getElementById(`totalGuests${bookingId}`);
        const assignedEl = document.getElementById(`assignedGuests${bookingId}`);
        const unassignedEl = document.getElementById(`unassignedGuests${bookingId}`);
        const confirmedEl = document.getElementById(`confirmedGuests${bookingId}`);
        const statusEl = document.getElementById(`assignmentStatus${bookingId}`);

        if (totalEl) totalEl.textContent = total;
        if (assignedEl) assignedEl.textContent = assigned;
        if (unassignedEl) unassignedEl.textContent = unassigned;
        if (confirmedEl) confirmedEl.textContent = confirmed;

        if (statusEl) {
            if (unassigned === 0) {
                statusEl.textContent = 'Hoàn thành';
                statusEl.className = 'badge bg-success ms-2';
            } else {
                statusEl.textContent = `Còn ${unassigned} khách chưa gán phòng`;
                statusEl.className = 'badge bg-warning ms-2';
            }
        }
    }

    function showToast(message, type = 'info') {
        const bgClass = type === 'success' ? 'text-bg-success'
            : type === 'error' ? 'text-bg-danger'
            : 'text-bg-info';
        let container = document.getElementById('admin-assign-room-toast-stack');
        if (!container) {
            container = document.createElement('div');
            container.id = 'admin-assign-room-toast-stack';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1085';
            document.body.appendChild(container);
        }
        const el = document.createElement('div');
        el.className = 'toast align-items-center ' + bgClass + ' border-0';
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'polite');
        el.setAttribute('aria-atomic', 'true');
        const flex = document.createElement('div');
        flex.className = 'd-flex';
        const body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close me-2 m-auto' + (type === 'info' ? '' : ' btn-close-white');
        closeBtn.setAttribute('data-bs-dismiss', 'toast');
        closeBtn.setAttribute('aria-label', 'Đóng');
        flex.appendChild(body);
        flex.appendChild(closeBtn);
        el.appendChild(flex);
        container.appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 4000 });
        el.addEventListener('hidden.bs.toast', function () {
            el.remove();
        });
        toast.show();
    }
</script>
@endpush
