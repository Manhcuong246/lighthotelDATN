@extends('layouts.admin')

@section('title', 'Quản lý đặt phòng')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Quản lý đặt phòng</h1>
        <div class="d-flex flex-wrap gap-2">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.bookings.create-multi') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-layers me-1"></i>Tạo đơn nhiều phòng
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
            <h5 class="mb-0 text-white fw-semibold">{{ request('checkin_checkout') ? 'Check-in / Check-out' : 'Danh sách đơn đặt phòng' }}</h5>
            <form action="{{ route('admin.bookings.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="checkin_checkout" value="{{ request('checkin_checkout') }}">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm khách, phòng, mã đơn..." style="width: 200px;">
                <input type="date" name="check_in_from" value="{{ request('check_in_from') }}" class="form-control form-control-sm" style="width: 170px;" title="Từ ngày nhận phòng">
                <input type="date" name="check_in_to" value="{{ request('check_in_to') }}" class="form-control form-control-sm" style="width: 170px;" title="Đến ngày nhận phòng">
                <input type="date" name="check_out_from" value="{{ request('check_out_from') }}" class="form-control form-control-sm" style="width: 170px;" title="Từ ngày trả phòng">
                <input type="date" name="check_out_to" value="{{ request('check_out_to') }}" class="form-control form-control-sm" style="width: 170px;" title="Đến ngày trả phòng">
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
                            <th style="width: 100px;">📅 Ngày nhận phòng</th>
                            <th style="width: 100px;">📅 Ngày trả phòng</th>
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
                                            'confirmed' => 'Đã thanh toán',
                                            'completed' => 'Hoàn thành',
                                            'cancelled' => 'Đã hủy',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$booking->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$booking->status] ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    @if(request('checkin_checkout'))
                                        <div class="d-flex flex-wrap gap-2">
                                            @if($booking->isCheckinAllowed())
                                                <form action="{{ route('admin.bookings.checkIn', $booking) }}" method="POST">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success text-white fw-bold"><i class="bi bi-box-arrow-in-right me-1"></i>Xác nhận Check-in</button>
                                                </form>
                                            @elseif($booking->isCheckoutAllowed())
                                                <form action="{{ route('admin.bookings.checkOut', $booking) }}" method="POST">
                                                    @csrf
                                                    <button class="btn btn-sm btn-warning text-dark fw-bold"><i class="bi bi-box-arrow-right me-1"></i>Xác nhận Check-out</button>
                                                </form>
                                            @endif

                                            <button type="button" class="btn btn-sm btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#surchargeModal{{ $booking->id }}" title="Lập phiếu phát sinh phụ thu (Ví dụ: Thêm người)">
                                                <i class="bi bi-plus-circle me-1"></i>Phát sinh
                                            </button>
                                            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết đơn">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    @else
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

                                                @if($booking->status === 'pending')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $booking->id }}">
                                                        <i class="bi bi-x-circle me-2"></i> Hủy đơn
                                                    </button>
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
                                    @endif
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

<!-- Modal Hủy Đơn -->
@foreach($bookings as $booking)
<div class="modal fade" id="cancelModal{{ $booking->id }}" tabindex="-1" aria-labelledby="cancelModalLabel{{ $booking->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelModalLabel{{ $booking->id }}">
                    <i class="bi bi-x-circle me-2"></i>Xác nhận hủy đơn #{{ $booking->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.bookings.cancel', $booking) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Bạn có chắc muốn hủy đơn đặt phòng này?</strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cancelReason{{ $booking->id }}" class="form-label fw-bold">
                            Lý do hủy đơn <span class="text-danger">*</span>
                        </label>
                        <textarea name="cancel_reason" id="cancelReason{{ $booking->id }}" class="form-control" rows="4" 
                                  placeholder="Nhập lý do hủy đơn đặt phòng..." required></textarea>
                        <div class="form-text">Lý do sẽ được hiển thị cho khách hàng trong lịch sử đặt phòng.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Khách hàng:</strong><br>
                            {{ $booking->user?->full_name ?? '—' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Phòng:</strong><br>
                            @if($booking->rooms->count() > 1)
                                {{ $booking->rooms->count() }} phòng
                            @else
                                {{ $booking->rooms->first()->name ?? '—' }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Đóng
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Xác nhận hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- Modal Surcharge (Phiếu Phát Sinh) -->
@foreach($bookings as $booking)
<div class="modal fade" id="surchargeModal{{ $booking->id }}" tabindex="-1" aria-labelledby="surchargeModalLabel{{ $booking->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="surchargeModalLabel{{ $booking->id }}">
                    <i class="bi bi-plus-circle me-2"></i>Lập phiếu phát sinh đơn #{{ $booking->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.bookings.storeSurcharge', $booking) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Nhập lý do phụ thu (như số lượng người thêm, dịch vụ ngoài, v.v...) và số tiền tương ứng.
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason{{ $booking->id }}" class="form-label fw-bold">
                            Lý do phát sinh <span class="text-danger">*</span>
                        </label>
                        <textarea name="reason" id="reason{{ $booking->id }}" class="form-control" rows="3" 
                                  placeholder="Ví dụ: Phụ thu thêm 1 người lớn, Đền bù ly vỡ..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="amount{{ $booking->id }}" class="form-label fw-bold">
                            Số tiền phụ thu (VNĐ) <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="amount" id="amount{{ $booking->id }}" class="form-control" min="0" required placeholder="Ví dụ: 200000">
                    </div>
                    
                    <div class="row bg-light rounded p-2 m-0 mt-3 align-items-center">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <span class="text-muted d-block small">Khách hàng:</span>
                            <strong>{{ $booking->user?->full_name ?? '—' }}</strong>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="text-muted d-block small">Tổng tiền gốc đang có:</span>
                            <strong class="text-success">{{ number_format($booking->total_price, 0, ',', '.') }} ₫</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer pb-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Hủy bỏ
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-plus-circle me-1"></i>Lưu phiếu phát sinh
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection
