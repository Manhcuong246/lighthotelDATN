@extends('layouts.app')

@section('title', 'Check-in - Đơn #' . $booking->id)

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <!-- Booking Header -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-calendar-check me-2"></i>
                        Check-in - Đơn đặt phòng #{{ $booking->id }}
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Khách hàng:</strong> {{ $booking->user->full_name ?? 'N/A' }}</p>
                            <p><strong>Email:</strong> {{ $booking->user->email ?? 'N/A' }}</p>
                            <p><strong>Điện thoại:</strong> {{ $booking->user->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Ngày nhận phòng:</strong> {{ $booking->check_in->format('d/m/Y') }}</p>
                            <p><strong>Ngày trả phòng:</strong> {{ $booking->check_out->format('d/m/Y') }}</p>
                            <p><strong>Trạng thái:</strong> 
                                <span class="badge bg-{{ $booking->status === 'confirmed' ? 'success' : 'warning' }}">
                                    {{ $booking->status === 'confirmed' ? 'Đã xác nhận' : 'Chờ xác nhận' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guest List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people-fill me-2"></i>
                        Danh sách khách ({{ $booking->bookingGuests->count() }} người)
                    </h5>
                    <div>
                        <button type="button" class="btn btn-success btn-sm" onclick="checkInAllGuests()">
                            <i class="bi bi-check-circle me-1"></i>
                            Check-in tất cả
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshGuestList()">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Làm mới
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($booking->bookingGuests->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-person-x display-4 text-muted"></i>
                            <p class="text-muted mt-2">Chưa có thông tin khách hàng</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Tên khách</th>
                                        <th>Loại</th>
                                        <th>CCCD</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="guestTableBody">
                                    @foreach($booking->bookingGuests as $index => $guest)
                                        <tr data-guest-id="{{ $guest->id }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $guest->name }}</td>
                                            <td>
                                                <span class="badge bg-{{ $guest->type === 'adult' ? 'primary' : 'info' }}">
                                                    {{ $guest->type === 'adult' ? 'Người lớn' : 'Trẻ em' }}
                                                </span>
                                            </td>
                                            <td>{{ $guest->cccd ?: '-' }}</td>
                                            <td>
                                                <span class="badge guest-status bg-{{ $guest->status === 'checked_in' ? 'success' : 'warning' }}" data-guest-id="{{ $guest->id }}">
                                                    {{ $guest->status === 'checked_in' ? 'Đã check-in' : 'Chờ check-in' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($guest->status === 'pending')
                                                    <button type="button" 
                                                            class="btn btn-success btn-sm"
                                                            onclick="checkInGuest({{ $guest->id }})">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Check-in
                                                    </button>
                                                @else
                                                    <button type="button" 
                                                            class="btn btn-outline-warning btn-sm"
                                                            onclick="undoCheckIn({{ $guest->id }})">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                                                        Hoàn tác
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="text-primary">{{ $booking->bookingGuests->count() }}</h5>
                                        <p class="mb-0">Tổng số khách</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h5>{{ $booking->bookingGuests->where('status', 'checked_in')->count() }}</h5>
                                        <p class="mb-0">Đã check-in</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h5>{{ $booking->bookingGuests->where('status', 'pending')->count() }}</h5>
                                        <p class="mb-0">Chờ check-in</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Thành công</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="successMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function checkInGuest(guestId) {
    if (!confirm('Bạn có chắc muốn check-in cho khách này?')) {
        return;
    }

    fetch(`/checkin/guests/${guestId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            status: 'checked_in'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateGuestStatusInTable(guestId, 'checked_in');
            updateSummary();
            showSuccessMessage('Check-in thành công!');
        } else {
            alert('Có lỗi xảy ra: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi thực hiện check-in');
    });
}

function undoCheckIn(guestId) {
    if (!confirm('Bạn có chắc muốn hoàn tác check-in cho khách này?')) {
        return;
    }

    fetch(`/checkin/guests/${guestId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            status: 'pending'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateGuestStatusInTable(guestId, 'pending');
            updateSummary();
            showSuccessMessage('Hoàn tác check-in thành công!');
        } else {
            alert('Có lỗi xảy ra: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi hoàn tác check-in');
    });
}

function checkInAllGuests() {
    if (!confirm('Bạn có chắc muốn check-in cho tất cả khách chưa check-in?')) {
        return;
    }

    fetch(`/checkin/bookings/{{ $booking->id }}/checkin-all`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        return response.text();
    })
    .then(data => {
        if (data) {
            // If it's HTML response, it means we were redirected
            document.open();
            document.write(data);
            document.close();
        } else {
            // Refresh the page to show updated status
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi check-in tất cả khách');
    });
}

function updateGuestStatusInTable(guestId, status) {
    const row = document.querySelector(`tr[data-guest-id="${guestId}"]`);
    if (!row) return;

    const statusBadge = row.querySelector('.guest-status');
    const actionCell = row.querySelector('td:last-child');
    
    if (status === 'checked_in') {
        statusBadge.className = 'badge guest-status bg-success';
        statusBadge.textContent = 'Đã check-in';
        
        actionCell.innerHTML = `
            <button type="button" 
                    class="btn btn-outline-warning btn-sm"
                    onclick="undoCheckIn(${guestId})">
                <i class="bi bi-arrow-counterclockwise me-1"></i>
                Hoàn tác
            </button>
        `;
    } else {
        statusBadge.className = 'badge guest-status bg-warning';
        statusBadge.textContent = 'Chờ check-in';
        
        actionCell.innerHTML = `
            <button type="button" 
                    class="btn btn-success btn-sm"
                    onclick="checkInGuest(${guestId})">
                <i class="bi bi-check-circle me-1"></i>
                Check-in
            </button>
        `;
    }
}

function updateSummary() {
    const rows = document.querySelectorAll('#guestTableBody tr');
    let checkedInCount = 0;
    let totalCount = rows.length;

    rows.forEach(row => {
        const statusBadge = row.querySelector('.guest-status');
        if (statusBadge && statusBadge.textContent === 'Đã check-in') {
            checkedInCount++;
        }
    });

    // Update summary cards
    document.querySelector('.bg-success h5').textContent = checkedInCount;
    document.querySelector('.bg-warning h5').textContent = totalCount - checkedInCount;
}

function refreshGuestList() {
    location.reload();
}

function showSuccessMessage(message) {
    document.getElementById('successMessage').textContent = message;
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
}
</script>
@endpush
