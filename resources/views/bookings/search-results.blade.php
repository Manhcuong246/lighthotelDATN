@extends('layouts.app')

@section('title', 'Kết quả tìm phòng')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-door-open me-2"></i>
                        Phòng trống từ {{ \Carbon\Carbon::parse($check_in)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($check_out)->format('d/m/Y') }}
                    </h4>
                </div>
                <div class="card-body">
                    @if($availableRooms->count() > 0)
                        <form id="roomSelectionForm" method="POST" action="{{ route('bookings.booking-form') }}">
                            @csrf
                            <input type="hidden" name="check_in" value="{{ $check_in }}">
                            <input type="hidden" name="check_out" value="{{ $check_out }}">

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Chọn</th>
                                            <th>Loại phòng</th>
                                            <th>Ghi chú</th>
                                            <th>Giá/đêm</th>
                                            <th>Số đêm</th>
                                            <th>Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($availableRooms as $room)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="room_type_ids[]" value="{{ $room->room_type_id }}"
                                                           class="form-check-input room-checkbox" data-room-id="{{ $room->id }}">
                                                </td>
                                                <td>{{ $room->roomType->name ?? 'N/A' }}</td>
                                                <td class="small text-muted">Số phòng cụ thể sẽ được bố trí khi check-in</td>
                                                <td>{{ number_format(1000000, 0, ',', '.') }} VNĐ</td>
                                                <td>{{ \Carbon\Carbon::parse($check_in)->diffInDays(\Carbon\Carbon::parse($check_out)) }}</td>
                                                <td>{{ number_format(1000000 * \Carbon\Carbon::parse($check_in)->diffInDays(\Carbon\Carbon::parse($check_out)), 0, ',', '.') }} VNĐ</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" disabled>
                                    <i class="bi bi-arrow-right me-2"></i>
                                    Tiếp tục điền thông tin
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Không tìm thấy phòng trống trong khoảng thời gian đã chọn. Vui lòng chọn ngày khác.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Lưu thông tin khách khi thay đổi lựa chọn phòng
let guestDataByRoom = {};

// Xử lý chọn phòng
document.querySelectorAll('.room-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const checkedBoxes = document.querySelectorAll('.room-checkbox:checked');
        const continueBtn = document.getElementById('continueBtn');

        if (checkedBoxes.length > 0) {
            continueBtn.disabled = false;
        } else {
            continueBtn.disabled = true;
        }

        // Lưu thông tin khách hiện tại trước khi thay đổi
        saveCurrentGuestData();
    });
});

// Lưu thông tin khách hiện tại
function saveCurrentGuestData() {
    const guestSections = document.querySelectorAll('.guest-room-section');

    guestSections.forEach(section => {
        const roomIndex = section.dataset.roomIndex;
        const guestInputs = section.querySelectorAll('input[data-field="name"], input[data-field="cccd"]');

        if (!guestDataByRoom[roomIndex]) {
            guestDataByRoom[roomIndex] = {};
        }

        guestInputs.forEach(input => {
            const guestIndex = input.dataset.guestIndex;
            const field = input.dataset.field;

            if (!guestDataByRoom[roomIndex][guestIndex]) {
                guestDataByRoom[roomIndex][guestIndex] = {};
            }

            guestDataByRoom[roomIndex][guestIndex][field] = input.value;
        });

    });
}

// Khôi phục thông tin khách khi trang được load
function restoreGuestData() {
    Object.keys(guestDataByRoom).forEach(roomIndex => {
        const roomData = guestDataByRoom[roomIndex];
        const guestInputs = document.querySelectorAll(`#guestInputsContainer-${roomIndex} input[data-field]`);

        guestInputs.forEach(input => {
            const guestIndex = input.dataset.guestIndex;
            const field = input.dataset.field;

            if (roomData[guestIndex] && roomData[guestIndex][field]) {
                input.value = roomData[guestIndex][field];
            }
        });
    });
}

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Khôi phục thông tin khách khi trang được load
    setTimeout(restoreGuestData, 500);
});
</script>
@endsection
