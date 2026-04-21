{{--
    Component modal đổi phòng nâng cao với AJAX load danh sách phòng
    Sử dụng: @include('admin.bookings.partials.room-change-modal', ['booking' => $booking, 'bookingRoom' => $br])
--}}

@php
    $modalId = 'changeRoomModalV2_' . $bookingRoom->room_id;
@endphp

<!-- Nút mở modal -->
<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
    <i class="bi bi-arrow-left-right me-1"></i>Đổi phòng
</button>

<!-- Modal Đổi phòng nâng cao -->
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-arrow-left-right me-2"></i>
                    Đổi phòng: {{ $bookingRoom->room->name ?? '—' }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.bookings.changeRoomV2', $booking) }}" method="POST" class="room-change-form">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="old_room_id" value="{{ $bookingRoom->room_id }}">
                    
                    <!-- Thông tin phòng hiện tại -->
                    <div class="alert alert-light border mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Phòng hiện tại:</strong>
                                <span class="badge bg-secondary">{{ $bookingRoom->room->name ?? 'N/A' }}</span>
                                @if($bookingRoom->room?->roomType)
                                    <br><small class="text-muted">{{ $bookingRoom->room->roomType->name }}</small>
                                @endif
                            </div>
                            <div class="col-md-6 text-md-end">
                                <strong>Giá hiện tại:</strong>
                                <span class="badge bg-info">{{ number_format($bookingRoom->price_per_night, 0, ',', '.') }} ₫/đêm</span>
                            </div>
                        </div>
                    </div>

                    <!-- Chọn phòng mới -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-door-open me-1"></i>Chọn phòng mới
                        </label>
                        <select name="new_room_id" class="form-select new-room-select" required data-current-room="{{ $bookingRoom->room_id }}">
                            <option value="">-- Đang tải danh sách phòng... --</option>
                        </select>
                        <div class="form-text text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Chỉ hiển thị các phòng trống trong khoảng thởi gian đặt phòng
                        </div>
                    </div>

                    <!-- Thông tin phòng mới (sẽ được cập nhật bởi JS) -->
                    <div class="new-room-info d-none">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Phòng mới:</strong>
                                    <span class="new-room-name badge bg-primary"></span>
                                    <br><small class="new-room-type text-muted"></small>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <strong>Giá mới:</strong>
                                    <span class="new-room-price badge bg-success"></span>
                                    <br><small class="price-difference text-muted"></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lý do đổi -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-chat-left-text me-1"></i>Lý do đổi phòng
                        </label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="Ví dụ: Khách yêu cầu đổi phòng rộng hơn, Phòng hỏng thiết bị..." required></textarea>
                    </div>

                    <!-- Cảnh báo -->
                    <div class="alert alert-warning d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div>
                            <strong>Lưu ý:</strong> Sau khi đổi phòng, giá sẽ được tính lại theo giá của phòng mới.
                            @if($booking->status === 'checked_in')
                                <br>Đơn đang check-in, việc đổi phòng sẽ cập nhật trạng thái phòng ngay lập tức.
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Hủy
                    </button>
                    <button type="submit" class="btn btn-primary" disabled>
                        <i class="bi bi-check-lg me-1"></i>Xác nhận đổi phòng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý load danh sách phòng khi modal mở
    const modal = document.getElementById('{{ $modalId }}');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function() {
        const select = modal.querySelector('.new-room-select');
        const currentRoomId = select.dataset.currentRoom;
        const bookingId = {{ $booking->id }};

        // Load danh sách phòng trống
        fetch(`{{ route('admin.bookings.available-rooms-for-change', $booking) }}?current_room_id=${currentRoomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    select.innerHTML = '<option value="">-- Chọn phòng thay thế --</option>';
                    
                    // Phân loại phòng
                    const sameTypeRooms = data.data.filter(r => r.is_same_type);
                    const diffTypeRooms = data.data.filter(r => !r.is_same_type);

                    // Nhóm cùng loại phòng
                    if (sameTypeRooms.length > 0) {
                        const optgroup = document.createElement('optgroup');
                        optgroup.label = 'Cùng loại phòng (khuyến nghị)';
                        sameTypeRooms.forEach(room => {
                            const option = document.createElement('option');
                            option.value = room.id;
                            option.textContent = `${room.name} - ${room.room_type?.name || 'N/A'} (${new Intl.NumberFormat('vi-VN').format(room.base_price)}₫)`;
                            option.dataset.price = room.base_price;
                            option.dataset.name = room.name;
                            option.dataset.type = room.room_type?.name || 'N/A';
                            option.dataset.diff = room.price_difference;
                            optgroup.appendChild(option);
                        });
                        select.appendChild(optgroup);
                    }

                    // Nhóm khác loại phòng
                    if (diffTypeRooms.length > 0) {
                        const optgroup = document.createElement('optgroup');
                        optgroup.label = 'Khác loại phòng';
                        diffTypeRooms.forEach(room => {
                            const option = document.createElement('option');
                            option.value = room.id;
                            option.textContent = `${room.name} - ${room.room_type?.name || 'N/A'} (${new Intl.NumberFormat('vi-VN').format(room.base_price)}₫)`;
                            option.dataset.price = room.base_price;
                            option.dataset.name = room.name;
                            option.dataset.type = room.room_type?.name || 'N/A';
                            option.dataset.diff = room.price_difference;
                            optgroup.appendChild(option);
                        });
                        select.appendChild(optgroup);
                    }

                    if (data.data.length === 0) {
                        select.innerHTML = '<option value="">Không có phòng trống</option>';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading available rooms:', error);
                select.innerHTML = '<option value="">Lỗi tải danh sách phòng</option>';
            });
    });

    // Xử lý khi chọn phòng mới
    const select = modal.querySelector('.new-room-select');
    const submitBtn = modal.querySelector('button[type="submit"]');
    const infoDiv = modal.querySelector('.new-room-info');

    select.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        
        if (this.value) {
            const price = parseFloat(selected.dataset.price);
            const name = selected.dataset.name;
            const type = selected.dataset.type;
            const diff = parseFloat(selected.dataset.diff);
            const currentPrice = {{ $bookingRoom->price_per_night }};
            const nights = {{ $bookingRoom->nights }};

            infoDiv.classList.remove('d-none');
            infoDiv.querySelector('.new-room-name').textContent = name;
            infoDiv.querySelector('.new-room-type').textContent = type;
            infoDiv.querySelector('.new-room-price').textContent = new Intl.NumberFormat('vi-VN').format(price) + ' ₫/đêm';
            
            const totalDiff = diff * nights;
            const diffText = infoDiv.querySelector('.price-difference');
            if (totalDiff > 0) {
                diffText.innerHTML = `<span class="text-danger">Tăng thêm: ${new Intl.NumberFormat('vi-VN').format(totalDiff)} ₫ (${nights} đêm)</span>`;
            } else if (totalDiff < 0) {
                diffText.innerHTML = `<span class="text-success">Giảm: ${new Intl.NumberFormat('vi-VN').format(Math.abs(totalDiff))} ₫ (${nights} đêm)</span>`;
            } else {
                diffText.innerHTML = '<span class="text-muted">Giá không đổi</span>';
            }

            submitBtn.disabled = false;
        } else {
            infoDiv.classList.add('d-none');
            submitBtn.disabled = true;
        }
    });

    // Xác nhận trước khi submit
    modal.querySelector('form').addEventListener('submit', function(e) {
        const selected = select.options[select.selectedIndex];
        const roomName = selected.dataset.name;
        
        if (!confirm(`Bạn có chắc muốn đổi sang phòng ${roomName}?`)) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
