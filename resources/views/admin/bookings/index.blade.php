@extends('layouts.admin')

@section('title', 'Quản lý đặt phòng')

@section('content')
<<<<<<< HEAD
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 fw-bold mb-0 text-dark">📋 Quản lý đặt phòng</h1>

        <!-- Notification Bell -->
        <div class="dropdown">
            <button class="btn btn-light btn-lg position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell-fill fs-5" style="color: #ff6b6b;"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    {{ $bookings->total() }}
                    <span class="visually-hidden">đơn chưa xem</span>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 300px;">
                <li><h6 class="dropdown-header">📬 Đơn đặt phòng</h6></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.bookings.index') }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><strong>📊 Tổng đơn:</strong></span>
                            <span class="badge bg-primary">{{ $bookings->total() }}</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.bookings.index') }}?status=pending">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><strong>⏳ Chờ xác nhận:</strong></span>
                            <span class="badge bg-warning">{{ $bookings->where('status', 'pending')->count() }}</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.bookings.index') }}?status=confirmed">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><strong>✓ Đã xác nhận:</strong></span>
                            <span class="badge bg-info">{{ $bookings->where('status', 'confirmed')->count() }}</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.bookings.index') }}?status=completed">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><strong>✓✓ Hoàn thành:</strong></span>
                            <span class="badge bg-success">{{ $bookings->where('status', 'completed')->count() }}</span>
                        </div>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center small text-primary fw-bold" href="{{ route('admin.bookings.index') }}">→ Xem danh sách chi tiết</a></li>
            </ul>
=======
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Quản lý đặt phòng</h1>
        <div class="d-flex flex-wrap gap-2">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.bookings.create') }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tạo đơn
            </a>
            @endif

            <div class="dropdown">
                <button class="btn btn-light btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell-fill fs-5" style="color: #ff6b6b;"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $counts['total'] ?? (is_object($bookings) && method_exists($bookings, 'total') ? $bookings->total() : (is_array($bookings) ? count($bookings) : 0)) }}
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 280px;">
                    <li><h6 class="dropdown-header">Đơn đặt phòng</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.bookings.index') }}">
                            <div class="d-flex justify-content-between">
                                <span><strong>Tổng đơn:</strong></span>
                                <span class="badge bg-primary">{{ $counts['total'] ?? 0 }}</span>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.bookings.index') }}?status=pending">
                            <div class="d-flex justify-content-between">
                                <span><strong>Chờ xác nhận:</strong></span>
                                <span class="badge bg-warning">{{ $counts['pending'] ?? 0 }}</span>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.bookings.index') }}?status=confirmed">
                            <div class="d-flex justify-content-between">
                                <span><strong>Đã xác nhận:</strong></span>
                                <span class="badge bg-info">{{ $counts['confirmed'] ?? 0 }}</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
>>>>>>> vinam
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
<<<<<<< HEAD
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
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    {{-- PRIMARY ACTION BUTTONS BASED ON STATUS (Priority buttons) --}}
                                    @if($booking->status === 'pending')
                                        <form action="{{ route('admin.bookings.requestPayment', $booking) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary px-3">
                                                <i class="bi bi-credit-card"></i> Yêu cầu thanh toán
                                            </button>
                                        </form>
                                    @elseif($booking->status === 'awaiting_payment')
                                        <form action="{{ route('admin.bookings.confirmPayment', $booking) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success px-3">
                                                <i class="bi bi-check-circle"></i> Xác nhận thanh toán
                                            </button>
                                        </form>
                                    @endif

                                    @if($booking->isCheckinAllowed())
                                        <form action="{{ route('admin.bookings.checkIn', $booking) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info px-3">
                                                <i class="bi bi-door-open"></i> Check-in
                                            </button>
                                        </form>
                                    @endif

                                    @if($booking->isCheckoutAllowed())
                                        <form action="{{ route('admin.bookings.checkOut', $booking) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning px-3">
                                                <i class="bi bi-door-closed"></i> Check-out
                                            </button>
                                        </form>
                                    @endif

                                    {{-- STANDARD ACTION BUTTONS (Xem, Sửa, Hủy, Xóa) - Always together --}}
                                    <div class="btn-group btn-group-sm" role="group">
                                        {{-- View button --}}
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline-primary" title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        {{-- Edit button --}}
                                        <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-outline-secondary" title="Sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        {{-- Cancel button --}}
                                        @if($booking->status !== 'cancelled' && $booking->status !== 'completed')
                                            <form action="{{ route('admin.bookings.updateStatus', $booking) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="btn btn-outline-danger" title="Hủy" onclick="return confirm('Hủy đơn đặt phòng này?')">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Delete button (admin only) --}}
                                        @if(auth()->user()->isAdmin())
                                            <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa vĩnh viễn đơn #{{ $booking->id }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Xóa">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
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
=======
    <div class="card shadow-sm border-0">
        <div class="card-header py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background: linear-gradient(90deg, #3b49d6 0%, #4b3bd6 100%);">
            <h5 class="mb-0 text-white fw-semibold">Danh sách đơn đặt phòng</h5>
            <form action="{{ route('admin.bookings.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm khách, phòng, mã đơn..." style="width: 200px;">
                <select name="status" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
                <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-search me-1"></i>Tìm</button>
                @if(request()->hasAny(['q','status']))
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-light btn-sm">Xóa bộ lọc</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow: visible;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 180px;">👤 Khách hàng</th>
                            <th style="width: 120px;">🏨 Phòng</th>
                            <th style="width: 100px;">📅 Check-in</th>
                            <th style="width: 100px;">📅 Check-out</th>
                            <th style="width: 50px;" class="text-center">👥</th>
                            <th style="width: 120px;" class="text-end">💰 Tổng tiền</th>
                            <th style="width: 110px;">📊 Trạng thái</th>
                            <th style="width: 220px;">⚡ Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            <tr>
                                <td><strong>#{{ $booking->id }}</strong></td>
                                <td>
                                    <div class="fw-bold">{{ $booking->user?->full_name ?? '—' }}</div>
                                    <small class="text-muted">{{ $booking->user?->email ?? '—' }}</small>
                                </td>
                                 <td>
                                    @if($booking->rooms->count() > 1)
                                        <div class="fw-bold text-primary">{{ $booking->rooms->count() }} phòng</div>
                                        <small class="text-muted">{{ $booking->rooms->pluck('name')->implode(', ') }}</small>
                                    @else
                                        <span class="badge bg-primary">{{ $booking->rooms->first()->name ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>{{ $booking->check_in?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $booking->check_out?->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        {{ $booking->bookingRooms->sum(function($br) { 
                                            return $br->adults + $br->children_0_5 + $br->children_6_11; 
                                        }) }}
                                    </span>
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
                                    <span class="badge bg-{{ $statusColors[$booking->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$booking->status] ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary" title="Xem">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-sm btn-outline-secondary" title="Sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" style="position: fixed; inset: 0px auto auto 0px; transform: translate3d(0px, 38px, 0px); z-index: 9999;">
                                                @if($booking->status === 'pending')
                                                <li>
                                                    <form action="{{ route('admin.bookings.updateStatus', $booking) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="status" value="confirmed">
                                                        <button class="dropdown-item text-success">
                                                            <i class="bi bi-check-circle me-2"></i> Xác nhận đơn
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif

                                                @if($booking->isCheckinAllowed())
                                                <li>
                                                    <form action="{{ route('admin.bookings.checkIn', $booking) }}" method="POST">
                                                        @csrf
                                                        <button class="dropdown-item text-info">
                                                            <i class="bi bi-box-arrow-in-right me-2"></i> Check-in
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif

                                                @if($booking->isCheckoutAllowed())
                                                <li>
                                                    <form action="{{ route('admin.bookings.checkOut', $booking) }}" method="POST">
                                                        @csrf
                                                        <button class="dropdown-item text-warning">
                                                            <i class="bi bi-box-arrow-right me-2"></i> Check-out
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif

                                                @if($booking->status !== 'cancelled' && $booking->status !== 'completed')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('admin.bookings.updateStatus', $booking) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button class="dropdown-item text-danger">
                                                            <i class="bi bi-x-circle me-2"></i> Hủy đơn
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif

                                                @if(auth()->user()->isAdmin())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn đơn #{{ $booking->id }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i> Xóa vĩnh viễn
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
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                    📭 Chưa có đơn đặt phòng nào
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
>>>>>>> vinam
                </table>
            </div>
        </div>

        <!-- Pagination -->
<<<<<<< HEAD
        @if($bookings->hasPages())
=======
        @if(method_exists($bookings, 'hasPages') && $bookings->hasPages())
>>>>>>> vinam
            <div class="card-footer bg-white border-0 py-3">
                {{ $bookings->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<style>
    .card {
<<<<<<< HEAD
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
        border-top-lef        php artisan tinker
        $user = \App\Models\User::find(USER_ID);
        $admin = \App\Models\Role::where('name','admin')->first();
        $user->roles()->attach($admin->id);t-radius: 10px;
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




=======
        border-radius: 12px;
        overflow: hidden;
    }
    .card-header {
        border-radius: 12px 12px 0 0 !important;
    }
    .table th {
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .table td {
        vertical-align: middle;
    }
    .badge {
        font-size: 0.85rem;
        padding: 0.4em 0.7em;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
>>>>>>> vinam
</style>
@endsection
