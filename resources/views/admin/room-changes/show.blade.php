@extends('layouts.admin')

@section('title', 'Chi tiết đổi phòng #' . $history->id)

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Chi tiết đổi phòng #{{ $history->id }}
            </h1>
            <div class="text-muted small">{{ $history->changed_at->format('d/m/Y H:i') }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.room-changes.index') }}" class="btn btn-outline-secondary btn-sm rounded-2">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
            </a>
            @if($canRevert)
            <button type="button" class="btn btn-warning btn-sm rounded-2" data-bs-toggle="modal" data-bs-target="#revertModal">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Hoàn tác
            </button>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">
        <!-- Cột trái: Chi tiết lần đổi -->
        <div class="col-lg-8">
            <!-- Thông tin đổi phòng -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-primary text-white rounded-top-3">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Thông tin đổi phòng</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Phòng cũ -->
                        <div class="col-md-5">
                            <div class="card border-secondary h-100">
                                <div class="card-header bg-light text-secondary">
                                    <small class="fw-bold">PHÒNG CŨ</small>
                                </div>
                                <div class="card-body text-center py-4">
                                    <i class="bi bi-door-closed text-secondary fs-1"></i>
                                    <h3 class="mt-2 mb-1">{{ $history->fromRoom?->name ?? 'N/A' }}</h3>
                                    <p class="text-muted mb-2">{{ $history->fromRoom?->roomType?->name ?? 'N/A' }}</p>
                                    @if($history->old_price_per_night)
                                        <div class="badge bg-secondary fs-6">
                                            {{ number_format($history->old_price_per_night, 0, ',', '.') }} ₫/đêm
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Mũi tên -->
                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <div class="text-center">
                                <i class="bi bi-arrow-right-circle-fill text-primary fs-1"></i>
                                <div class="mt-1">
                                    @php $diff = $history->price_difference ?? 0; @endphp
                                    @if($diff > 0)
                                        <span class="badge bg-danger">+{{ number_format($diff, 0, ',', '.') }} ₫</span>
                                    @elseif($diff < 0)
                                        <span class="badge bg-success">{{ number_format($diff, 0, ',', '.') }} ₫</span>
                                    @else
                                        <span class="badge bg-secondary">Không đổi</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Phòng mới -->
                        <div class="col-md-5">
                            <div class="card border-primary h-100">
                                <div class="card-header bg-primary text-white">
                                    <small class="fw-bold">PHÒNG MỚI</small>
                                </div>
                                <div class="card-body text-center py-4">
                                    <i class="bi bi-door-open text-primary fs-1"></i>
                                    <h3 class="mt-2 mb-1">{{ $history->toRoom?->name ?? 'N/A' }}</h3>
                                    <p class="text-muted mb-2">{{ $history->toRoom?->roomType?->name ?? 'N/A' }}</p>
                                    @if($history->new_price_per_night)
                                        <div class="badge bg-primary fs-6">
                                            {{ number_format($history->new_price_per_night, 0, ',', '.') }} ₫/đêm
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chi tiết giá -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2"></i>Chi tiết giá</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted">Giá cũ / đêm</td>
                                    <td class="fw-bold">{{ number_format($history->old_price_per_night ?? 0, 0, ',', '.') }} ₫</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Giá mới / đêm</td>
                                    <td class="fw-bold">{{ number_format($history->new_price_per_night ?? 0, 0, ',', '.') }} ₫</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Chênh lệch</td>
                                    <td>
                                        @if($diff > 0)
                                            <span class="text-danger fw-bold">+{{ number_format($diff, 0, ',', '.') }} ₫</span>
                                        @elseif($diff < 0)
                                            <span class="text-success fw-bold">{{ number_format($diff, 0, ',', '.') }} ₫</span>
                                        @else
                                            <span class="text-muted">0 ₫</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Thông tin booking -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2"></i>Đơn đặt phòng</h5>
                </div>
                <div class="card-body">
                    @if($history->booking)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted">Mã đơn:</span>
                                <a href="{{ route('admin.bookings.show', $history->booking_id) }}" class="fw-bold">#{{ $history->booking_id }}</a>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Khách hàng:</span>
                                <span class="fw-bold">{{ $history->booking->user?->full_name ?? 'N/A' }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Trạng thái:</span>
                                <span class="badge bg-{{ $history->booking->status === 'confirmed' ? 'success' : 'warning' }}">
                                    {{ $history->booking->status }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted">Nhận phòng:</span>
                                <span class="fw-bold">{{ $history->booking->check_in?->format('d/m/Y') }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Trả phòng:</span>
                                <span class="fw-bold">{{ $history->booking->check_out?->format('d/m/Y') }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Tổng tiền:</span>
                                <span class="fw-bold text-primary">{{ number_format($history->booking->total_price, 0, ',', '.') }} ₫</span>
                            </div>
                        </div>
                    </div>
                    @else
                    <p class="text-muted">Đơn đặt phòng không còn tồn tại</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Cột phải: Lý do, người đổi, lịch sử -->
        <div class="col-lg-4">
            <!-- Lý do -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-chat-left-text me-2"></i>Lý do đổi</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $history->reason ?? 'Không ghi lý do' }}</p>
                </div>
            </div>

            <!-- Người thực hiện -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person me-2"></i>Người thực hiện</h5>
                </div>
                <div class="card-body">
                    @if($history->changedBy)
                    <div class="d-flex align-items-center">
                        <div class="bg-light rounded-circle p-2 me-3">
                            <i class="bi bi-person-fill fs-5 text-primary"></i>
                        </div>
                        <div>
                            <div class="fw-bold">{{ $history->changedBy->full_name }}</div>
                            <small class="text-muted">{{ $history->changedBy->email }}</small>
                        </div>
                    </div>
                    @else
                    <p class="text-muted mb-0">System</p>
                    @endif
                </div>
            </div>

            <!-- Liên kết báo cáo hỏng hóc -->
            @if($history->damageReport)
            <div class="card shadow-sm border border-warning rounded-3 mb-4">
                <div class="card-header bg-warning text-dark border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Báo cáo hỏng hóc</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Mã báo cáo:</strong> #{{ $history->damageReport->id }}</p>
                    <p class="mb-1"><strong>Mô tả:</strong> {{ Str::limit($history->damageReport->description, 100) }}</p>
                    <a href="{{ route('admin.damage-reports.show', $history->damageReport) }}" class="btn btn-sm btn-outline-warning mt-2">
                        Xem chi tiết
                    </a>
                </div>
            </div>
            @endif

            <!-- Hoàn tác -->
            @if($canRevert)
            <div class="card shadow-sm border border-warning rounded-3 mb-4">
                <div class="card-header bg-warning text-dark border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-counterclockwise me-2"></i>Hoàn tác</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Phòng cũ ({{ $history->fromRoom?->name }}) hiện đang trống. Bạn có thể hoàn tác lần đổi này.</p>
                    <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#revertModal">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Hoàn tác đổi phòng
                    </button>
                </div>
            </div>
            @else
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-body text-center text-muted py-3">
                    <i class="bi bi-lock fs-4"></i>
                    <p class="small mb-0">Không thể hoàn tác (phòng cũ đã được đặt hoặc đơn đã kết thúc)</p>
                </div>
            </div>
            @endif

            <!-- Lịch sử đổi phòng khác của booking này -->
            @if($bookingHistories->count() > 0)
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Lịch sử đổi phòng khác</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($bookingHistories as $bh)
                        <a href="{{ route('admin.room-changes.show', $bh->id) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-secondary">{{ $bh->fromRoom?->name }}</span>
                                    <i class="bi bi-arrow-right mx-1 small"></i>
                                    <span class="badge bg-primary">{{ $bh->toRoom?->name }}</span>
                                </div>
                                <small class="text-muted">{{ $bh->changed_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Hoàn tác -->
<div class="modal fade" id="revertModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Hoàn tác đổi phòng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.room-changes.revert', $history->id) }}" method="POST">
                @csrf
                @method('POST')
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Cảnh báo:</strong> Bạn sắp hoàn tác đổi phòng này.
                        Phòng sẽ được chuyển từ <strong>{{ $history->toRoom?->name }}</strong> về <strong>{{ $history->fromRoom?->name }}</strong>.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Lý do hoàn tác</label>
                        <textarea name="reason" class="form-control" rows="2" 
                            placeholder="Ví dụ: Khách yêu cầu đổi lại, Lỗi thao tác..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Xác nhận hoàn tác
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
