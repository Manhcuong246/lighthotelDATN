@extends('layouts.admin')

@section('title', 'Lịch sử đổi phòng')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Đổi phòng
            </h1>
            <div class="text-muted small">Quản lý lịch sử đổi phòng, hoàn tác và theo dõi</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.room-changes.create') }}" class="btn btn-primary rounded-2">
                <i class="bi bi-plus-lg me-1"></i>Đổi phòng mới
            </a>
        </div>
    </div>

    <!-- Thống kê -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body text-center py-3">
                    <div class="text-muted small">Tổng số lần đổi</div>
                    <div class="fs-4 fw-bold text-primary">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body text-center py-3">
                    <div class="text-muted small">Hôm nay</div>
                    <div class="fs-4 fw-bold text-success">{{ $stats['today'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body text-center py-3">
                    <div class="text-muted small">Nâng hạng</div>
                    <div class="fs-4 fw-bold text-primary">{{ $stats['upgrades'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body text-center py-3">
                    <div class="text-muted small">Hạ hạng</div>
                    <div class="fs-4 fw-bold text-success">{{ $stats['downgrades'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body text-center py-3">
                    <div class="text-muted small">Tổng tăng giá</div>
                    <div class="fs-6 fw-bold text-danger">{{ number_format($stats['total_price_increase'], 0, ',', '.') }} ₫</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body text-center py-3">
                    <div class="text-muted small">Tổng giảm giá</div>
                    <div class="fs-6 fw-bold text-success">{{ number_format(abs($stats['total_price_decrease']), 0, ',', '.') }} ₫</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2"></i>Bộ lọc</h6>
                <a href="{{ route('staff.room-changes.index') }}" class="btn btn-sm btn-outline-secondary rounded-2">Xóa lọc</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('staff.room-changes.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">Tìm kiếm</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Mã đơn, phòng, lý do...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Chênh lệch giá</label>
                    <select name="price_direction" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        <option value="increase" {{ request('price_direction') === 'increase' ? 'selected' : '' }}>Tăng</option>
                        <option value="decrease" {{ request('price_direction') === 'decrease' ? 'selected' : '' }}>Giảm</option>
                        <option value="same" {{ request('price_direction') === 'same' ? 'selected' : '' }}>Không đổi</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Loại đổi phòng</label>
                    <select name="change_type" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        <option value="same_grade" {{ request('change_type') === 'same_grade' ? 'selected' : '' }}>Cùng hạng</option>
                        <option value="upgrade" {{ request('change_type') === 'upgrade' ? 'selected' : '' }}>Nâng hạng</option>
                        <option value="downgrade" {{ request('change_type') === 'downgrade' ? 'selected' : '' }}>Hạ hạng</option>
                        <option value="emergency" {{ request('change_type') === 'emergency' ? 'selected' : '' }}>Khẩn cấp</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Từ ngày</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Đến ngày</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary w-100 rounded-2">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Booking</th>
                            <th>Khách hàng</th>
                            <th>Từ phòng</th>
                            <th></th>
                            <th>Đến phòng</th>
                            <th>Loại đổi</th>
                            <th>Đêm còn lại</th>
                            <th>Chênh lệch</th>
                            <th>Người đổi</th>
                            <th>Thời gian</th>
                            <th class="text-center pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($histories as $h)
                        <tr>
                            <td class="ps-3 fw-bold text-muted">#{{ $h->id }}</td>
                            <td>
                                <span class="text-decoration-none fw-bold">#{{ $h->booking_id }}</span>
                                @if($h->booking)
                                    <br><small class="text-muted">{{ $h->booking->status }}</small>
                                @endif
                            </td>
                            <td>
                                @if($h->booking && $h->booking->user)
                                    {{ $h->booking->user->full_name }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $h->fromRoom?->name ?? 'N/A' }}</span>
                                @if($h->fromRoom?->roomType)
                                    <br><small class="text-muted">{{ $h->fromRoom->roomType->name }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <i class="bi bi-arrow-right text-primary"></i>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $h->toRoom?->name ?? 'N/A' }}</span>
                                @if($h->toRoom?->roomType)
                                    <br><small class="text-muted">{{ $h->toRoom->roomType->name }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $h->change_type_badge }}">{{ $h->change_type_label }}</span>
                            </td>
                            <td class="text-center">
                                @if($h->remaining_nights > 0)
                                    <span class="fw-semibold">{{ $h->remaining_nights }}</span>
                                    <small class="text-muted">đêm</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php $diff = $h->price_difference ?? 0; @endphp
                                @if($diff > 0)
                                    <span class="text-danger fw-semibold">+{{ number_format($diff, 0, ',', '.') }} ₫</span>
                                @elseif($diff < 0)
                                    <span class="text-success fw-semibold">{{ number_format($diff, 0, ',', '.') }} ₫</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $h->changedBy?->full_name ?? 'System' }}
                                </span>
                            </td>
                            <td>
                                <div class="small">{{ $h->changed_at->format('d/m/Y') }}</div>
                                <div class="small text-muted">{{ $h->changed_at->format('H:i') }}</div>
                            </td>
                            <td class="text-center pe-3">
                                <a href="{{ route('staff.room-changes.show', $h->id) }}" class="btn btn-sm btn-outline-info rounded-2" title="Chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Chưa có lịch sử đổi phòng nào
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($histories->hasPages())
        <div class="card-footer bg-white border-0">
            <div class="d-flex justify-content-center">
                {{ $histories->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
