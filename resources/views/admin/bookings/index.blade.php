@extends('layouts.admin')

@section('title', 'Quản lý đặt phòng')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 fw-bold mb-0 text-dark">📋 Quản lý đặt phòng</h1>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill fs-6 px-3 py-2">{{ $bookings->total() }} đơn đặt phòng</span>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>✅ Thành công!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>❌ Lỗi!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Table Card -->
    <div class="card card-admin shadow-sm border-0">
        <div class="card-header-admin py-3 px-4">
            <h5 class="mb-0 text-white fw-semibold">📋 Danh sách đơn đặt phòng</h5>
        </div>
        <div class="card-body p-0">
            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60" style="text-align: left;">#</th>
                        <th width="180" style="text-align: left;">👤 Khách hàng</th>
                        <th width="120" style="text-align: left;">🏨 Phòng</th>
                        <th width="110" style="text-align: left;">📅 Check-in</th>
                        <th width="110" style="text-align: left;">📅 Check-out</th>
                        <th width="60" style="text-align: center;">👥</th>
                        <th width="120" style="text-align: right;">💰 Tổng tiền</th>
                        <th width="110" style="text-align: left;">📊 Trạng thái</th>
                        <th width="140" style="text-align: left;">⚡ Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>#{{ $booking->id }}</td>
                            <td>
                                <div class="fw-bold">{{ $booking->user?->full_name ?? '—' }}</div>
                                <small class="text-muted">{{ $booking->user?->email ?? '—' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $booking->room?->name ?? '—' }}</span>
                            </td>
                            <td class="text-muted">{{ $booking->check_in?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-muted">{{ $booking->check_out?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $booking->guests ?? 0 }}</span>
                            </td>
                            <td class="text-end">
                                <strong class="text-success">{{ number_format($booking->total_price ?? 0, 0, ',', '.') }} ₫</strong>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Chờ xác nhận',
                                        'confirmed' => 'Đã xác nhận',
                                        'completed' => 'Hoàn thành',
                                        'cancelled' => 'Đã hủy',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$booking->status] ?? 'secondary' }}">{{ $statusLabels[$booking->status] ?? '—' }}</span>
                            </td>
                            <td class="action-buttons">
                                <div class="d-flex align-items-center gap-2">
                                    {{-- View button --}}
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                    class="btn btn-sm btn-outline-primary px-3">
                                        Xem
                                    </a>

                                    {{-- Dropdown actions --}}
                                    <div class="btn-group btn-group-sm booking-dropdown">
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split px-2"
                                            data-bs-toggle="dropdown"
                                            data-bs-display="static"
                                            aria-expanded="false">
                                        </button>

                                        <ul class="dropdown-menu shadow-lg border-0 rounded-3">
                                            {{-- ===== PRIMARY ACTIONS ===== --}}
                                            @if($booking->status === 'pending')
                                            <li>
                                                <form action="{{ route('admin.bookings.updateStatus', $booking) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button class="dropdown-item text-success fw-semibold">
                                                        ✓ Xác nhận đơn
                                                    </button>
                                                </form>
                                            </li>
                                            @endif

                                            @if($booking->isCheckinAllowed())
                                            <li>
                                                <form action="{{ route('admin.bookings.checkIn', $booking) }}" method="POST">
                                                    @csrf
                                                    <button class="dropdown-item text-info fw-semibold">
                                                        🚪 Check-in
                                                    </button>
                                                </form>
                                            </li>
                                            @endif

                                            @if($booking->isCheckoutAllowed())
                                            <li>
                                                <form action="{{ route('admin.bookings.checkOut', $booking) }}" method="POST">
                                                    @csrf
                                                    <button class="dropdown-item text-warning fw-semibold">
                                                        🚪 Check-out
                                                    </button>
                                                </form>
                                            </li>
                                            @endif

                                            <li><hr class="dropdown-divider my-1"></li>

                                            {{-- ===== SECONDARY ===== --}}
                                            <li>
                                                <a href="{{ route('admin.bookings.edit', $booking) }}"
                                                class="dropdown-item">
                                                    ✏️ Sửa thông tin
                                                </a>
                                            </li>

                                            {{-- ===== DANGER ZONE ===== --}}
                                            @if($booking->status !== 'cancelled' && $booking->status !== 'completed')
                                            <li>
                                                <form action="{{ route('admin.bookings.updateStatus', $booking) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button class="dropdown-item text-danger">
                                                        ✕ Hủy đơn
                                                    </button>
                                                </form>
                                            </li>
                                            @endif

                                            @if(auth()->user()->isAdmin())
                                            <li>
                                                <form action="{{ route('admin.bookings.destroy', $booking) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Xóa vĩnh viễn đơn #{{ $booking->id }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger fw-semibold">
                                                        🗑️ Xóa vĩnh viễn
                                                    </button>
                                                </form>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">📭 Chưa có đơn đặt phòng</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($bookings->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $bookings->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<style>
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12) !important;
    }
    .rounded-3 { border-radius: 12px !important; }
    .rounded-top-3 { border-top-left-radius: 12px !important; border-top-right-radius: 12px !important; }
    .table-responsive { overflow-x: auto; }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    /* header styling to match rooms card but with softer gradient */
    .card-admin { overflow: visible; }
    .card-header-admin {
        background: linear-gradient(90deg, #3b49d6 0%, #4b3bd6 100%);
        color: #fff;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
        box-shadow: 0 2px 6px rgba(75,59,214,0.12) inset;
    }
    .booking-count { font-weight: 600; padding: 0.45rem 0.8rem; }

    .table thead th { vertical-align: middle; }
    .table tbody td { vertical-align: middle; }
    .badge { font-size: 0.9rem; }
    .btn-outline-primary { padding: 0.35rem 0.6rem; }
    .dropdown-item:hover { background-color: #f8f9fa; }

    /* Action buttons styling */
    .action-buttons {
        white-space: nowrap;
        min-width: 140px;
    }
    .action-buttons .d-flex {
        flex-wrap: nowrap;
    }
    .action-buttons .btn {
        flex-shrink: 0;
    }

    /* Table column alignment */
    .table tbody td:nth-child(1) { text-align: left; }      /* # */
    .table tbody td:nth-child(2) { text-align: left; }      /* Khách hàng */
    .table tbody td:nth-child(3) { text-align: left; }      /* Phòng */
    .table tbody td:nth-child(4) { text-align: left; }      /* Check-in */
    .table tbody td:nth-child(5) { text-align: left; }      /* Check-out */
    .table tbody td:nth-child(6) { text-align: center; }    /* Số khách */
    .table tbody td:nth-child(7) { text-align: right; }     /* Tổng tiền */
    .table tbody td:nth-child(8) { text-align: left; }      /* Trạng thái */
    .table tbody td:nth-child(9) { text-align: left; }      /* Hành động */

    /* Ensure consistent spacing */
    .table thead th {
        padding: 12px 8px;
        font-weight: 600;
        white-space: nowrap;
        font-size: 0.875rem;
    }
    .table tbody td {
        padding: 12px 8px;
        white-space: nowrap;
    }

    /* Specific styling for check-in/check-out columns */
    .table thead th:nth-child(4),
    .table thead th:nth-child(5) {
        min-width: 110px;
        font-size: 0.85rem;
    }
    /* FIX dropdown bị cắt trong table-responsive */


    /* Cho phép dropdown thoát khỏi card + table */
   /* Cho dropdown thoát khỏi table + card */
    .card,
    .table-responsive {
        overflow: visible !important;
    }

    /* Dropdown hành động */
    .booking-dropdown {
        position: relative;
    }

    /* Menu mở sang phải – ngoài khung */
    .booking-dropdown .dropdown-menu {
        position: absolute !important;
        top: 50%;
        left: calc(100% + 10px);
        transform: translateY(-50%);
        min-width: 220px;
        z-index: 9999;
        padding: 6px 0;
    }

    /* Hover mượt */
    .booking-dropdown .dropdown-item {
        padding: 8px 14px;
        border-radius: 6px;
    }

    .booking-dropdown .dropdown-item:hover {
        background-color: #f1f3f5;
    }




</style>
@endsection
