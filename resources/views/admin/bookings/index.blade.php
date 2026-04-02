@extends('layouts.admin')

@section('title', 'Quản lý đặt phòng')

@section('content')
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
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.bookings.index') }}?status=cancellation_pending">
                            <div class="d-flex justify-content-between">
                                <span><strong>Chờ xử lý hủy:</strong></span>
                                <span class="badge bg-primary">{{ $counts['cancellation_pending'] ?? 0 }}</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
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
    <div class="card shadow-sm border-0">
        <div class="card-header py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background: linear-gradient(90deg, #3b49d6 0%, #4b3bd6 100%);">
            <h5 class="mb-0 text-white fw-semibold">Danh sách đơn đặt phòng</h5>
            <form action="{{ route('admin.bookings.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm khách, phòng, mã đơn..." style="width: 200px;">
                <select name="status" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                    <option value="cancellation_pending" {{ request('status') === 'cancellation_pending' ? 'selected' : '' }}>Chờ xử lý hủy</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Đã hoàn tiền</option>
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
                                        <small class="text-muted">{{ $booking->roomNamesLabel() }}</small>
                                    @else
                                        <span class="badge bg-primary">{{ $booking->roomNamesLabel() }}</span>
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
                                            'cancellation_pending' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'refunded' => 'success',
                                        ];
                                        $statusLabels = [
                                            'pending' => 'Chờ xác nhận',
                                            'confirmed' => 'Đã xác nhận',
                                            'cancellation_pending' => 'Chờ xử lý hủy',
                                            'completed' => 'Hoàn thành',
                                            'cancelled' => 'Đã hủy',
                                            'refunded' => 'Đã hoàn tiền',
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

                                                @if(!in_array($booking->status, ['cancelled', 'completed', 'refunded', 'cancellation_pending'], true))
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
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if(method_exists($bookings, 'hasPages') && $bookings->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $bookings->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<style>
    .card {
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
</style>
@endsection
