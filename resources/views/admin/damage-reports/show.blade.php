@extends('layouts.admin')

@section('title', 'Chi tiết báo cáo hư hỏng #' . $damageReport->id)

@push('styles')
<style>
    .damage-report-show .section-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #6c757d;
        margin-bottom: 0;
    }
    .damage-report-show .meta-label {
        color: #6c757d;
        min-width: 140px;
        display: inline-block;
    }
</style>
@endpush

@section('content')
@php
    $roomLabel = $damageReport->room
        ? ($damageReport->room->room_number ?: $damageReport->room->name ?: ('#' . $damageReport->room->id))
        : 'Đã xóa/chưa gán';
@endphp
<div class="container-fluid px-3 px-lg-4 damage-report-show">
    <div class="page-header mb-3">
        <div>
            <a href="{{ route('admin.damage-reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 mb-2">
                <i class="bi bi-arrow-left me-1"></i>Danh sách báo cáo
            </a>
            <h1 class="h3 fw-bold mb-1">Báo cáo hư hỏng #{{ $damageReport->id }}</h1>
            <p class="text-muted small mb-0">
                Phòng <strong>{{ $roomLabel }}</strong>
                @if($damageReport->room->roomType?->name)
                    — {{ $damageReport->room->roomType->name }}
                @endif
            </p>
        </div>
        <div class="text-md-end">
            {!! $damageReport->severity_badge !!}
            <div class="small text-muted mt-1">{{ $damageReport->created_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Left Column: Report Details -->
        <div class="col-md-8">
            <!-- Status Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                    <h2 class="section-title">Trạng thái báo cáo</h2>
                    {!! $damageReport->status_badge !!}
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.damage-reports.update-status', $damageReport) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Cập nhật trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="in_progress" {{ $damageReport->status == 'in_progress' ? 'selected' : '' }}>
                                        Đang xử lý
                                    </option>
                                    <option value="resolved" {{ $damageReport->status == 'resolved' ? 'selected' : '' }}>
                                        Đã giải quyết
                                    </option>
                                    <option value="cancelled" {{ $damageReport->status == 'cancelled' ? 'selected' : '' }}>
                                        Hủy báo cáo
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Chi phí sửa chữa (nếu có)</label>
                                <input type="number" name="repair_cost" class="form-control" min="0" step="1000"
                                    value="{{ $damageReport->repair_cost }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" style="height: 2.5rem;" title="Cập nhật">
                                    <i class="bi bi-check2-lg"></i>
                                    <span>Cập nhật</span>
                                </button>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Ghi chú giải quyết</label>
                            <textarea name="resolution_notes" class="form-control" rows="2"
                                placeholder="Mô tả cách giải quyết...">{{ $damageReport->resolution_notes }}</textarea>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Damage Details -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="section-title">Chi tiết hư hỏng</h2>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td width="150"><strong>Loại lỗi:</strong></td>
                            <td>{{ \App\Models\DamageReport::getDamageTypes()[$damageReport->damage_type] ?? $damageReport->damage_type }}</td>
                        </tr>
                        <tr>
                            <td><strong>Mức độ:</strong></td>
                            <td>{!! $damageReport->severity_badge !!}</td>
                        </tr>
                        <tr>
                            <td><strong>Người báo cáo:</strong></td>
                            <td>{{ $damageReport->reporter->full_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Ngày báo cáo:</strong></td>
                            <td>{{ $damageReport->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Mô tả:</strong></td>
                            <td class="text-wrap">{{ $damageReport->description }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Room Information -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="section-title">Thông tin phòng</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Phòng:</strong> {{ $roomLabel }}</p>
                            <p><strong>Loại phòng:</strong> {{ $damageReport->room->roomType->name ?? 'N/A' }}</p>
                            <p><strong>Trạng thái phòng:</strong>
                                @if($damageReport->room->status === 'maintenance')
                                    <span class="badge bg-danger">Bảo trì</span>
                                @elseif($damageReport->room->status === 'available')
                                    <span class="badge bg-success">Trống</span>
                                @else
                                    <span class="badge bg-warning">{{ $damageReport->room->status }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            @if($damageReport->room->maintenance_since)
                                <p><strong>Bảo trì từ:</strong> {{ $damageReport->room->maintenance_since->format('d/m/Y H:i') }}</p>
                            @endif
                            @if($damageReport->room->maintenance_note)
                                <p><strong>Ghi chú:</strong> {{ $damageReport->room->maintenance_note }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Information (if any) -->
            @if($damageReport->booking)
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="section-title">Thông tin khách đang ở</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Khách:</strong> {{ $damageReport->booking->user->full_name ?? 'N/A' }}</p>
                            <p><strong>Email:</strong> {{ $damageReport->booking->user->email ?? 'N/A' }}</p>
                            <p><strong>SĐT:</strong> {{ $damageReport->booking->user->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Nhận phòng:</strong> {{ $damageReport->booking->check_in->format('d/m/Y') }}</p>
                            <p><strong>Trả phòng:</strong> {{ $damageReport->booking->check_out->format('d/m/Y') }}</p>
                            <p><strong>Tổng tiền:</strong> {{ number_format($damageReport->booking->total_price) }}đ</p>
                        </div>
                    </div>

                    @if($damageReport->requires_room_change && !$damageReport->isResolved())
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Cần chuyển phòng cho khách!</strong>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Room Change History -->
            @if($damageReport->roomChangeHistories->count() > 0)
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="section-title">Lịch sử chuyển phòng</h2>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Từ phòng</th>
                                <th>Sang phòng</th>
                                <th>Lý do</th>
                                <th>Người thực hiện</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($damageReport->roomChangeHistories as $history)
                            <tr>
                                <td>{{ $history->changed_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $history->fromRoom->room_number ?? 'N/A' }}</td>
                                <td>{{ $history->toRoom->room_number ?? 'N/A' }}</td>
                                <td>{{ $history->reason }}</td>
                                <td>{{ $history->changedBy->full_name ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column: Actions -->
        <div class="col-md-4">
            <!-- Room Change Action -->
            @if($damageReport->booking_id && !$damageReport->isResolved() && count($alternativeRooms) > 0)
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="section-title">Chuyển phòng cho khách</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.damage-reports.change-room', $damageReport) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Chọn phòng mới</label>
                            <select name="new_room_id" class="form-select" required>
                                <option value="">-- Chọn phòng --</option>
                                @foreach($alternativeRooms as $room)
                                    <option value="{{ $room['id'] }}">
                                        Phòng {{ $room['room_number'] }} -
                                        {{ $room['room_type']['name'] ?? 'N/A' }}
                                        ({{ number_format($room['base_price']) }}đ)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 d-flex align-items-center justify-content-center gap-2" style="height: 2.5rem;" title="Chuyển phòng">
                            <i class="bi bi-arrow-left-right"></i>
                            <span>Chuyển phòng</span>
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Refund Action -->
            @if($damageReport->booking_id && !$damageReport->isResolved())
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="section-title">Hoàn tiền cho khách</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.damage-reports.process-refund', $damageReport) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Phần trăm hoàn tiền (%)</label>
                            <input type="number" name="refund_percentage" class="form-control"
                                min="0" max="100" value="100" required>
                            <small class="text-muted">
                                Tổng tiền booking: {{ number_format($damageReport->booking->total_price ?? 0) }}đ
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lý do hoàn tiền</label>
                            <textarea name="refund_reason" class="form-control" rows="2"
                                placeholder="Ví dụ: Phòng không thể sử dụng do giường hỏng..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 d-flex align-items-center justify-content-center gap-2" style="height: 2.5rem;" title="Xử lý hoàn tiền">
                            <i class="bi bi-cash-stack"></i>
                            <span>Xử lý hoàn tiền</span>
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Quick Info -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-0 py-3">
                    <h2 class="section-title">Thông tin nhanh</h2>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-person"></i>
                            <strong>Người báo cáo:</strong><br>
                            {{ $damageReport->reporter->full_name ?? 'N/A' }}
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-calendar"></i>
                            <strong>Ngày báo cáo:</strong><br>
                            {{ $damageReport->created_at->format('d/m/Y H:i') }}
                        </li>
                        @if($damageReport->resolved_at)
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            <strong>Giải quyết:</strong><br>
                            {{ $damageReport->resolved_at->format('d/m/Y H:i') }}
                        </li>
                        <li>
                            <i class="bi bi-person-check"></i>
                            <strong>Người giải quyết:</strong><br>
                            {{ $damageReport->resolver->full_name ?? 'N/A' }}
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
