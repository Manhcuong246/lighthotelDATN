@extends('layouts.admin')

@section('title', 'Chi tiết báo cáo hư hỏng #' . $damageReport->id)

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('admin.damage-reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="text-dark fw-bold">
                🚨 Báo cáo hư hỏng #{{ $damageReport->id }}
            </h1>
            <p class="text-muted mb-0">
                Phòng <strong>{{ $damageReport->room->room_number ?? 'N/A' }}</strong> -
                {{ $damageReport->room->roomType->name ?? '' }}
            </p>
        </div>
        <div class="text-end">
            {!! $damageReport->severity_badge !!}
            <br><small class="text-muted">{{ $damageReport->created_at->format('d/m/Y H:i') }}</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Left Column: Report Details -->
        <div class="col-md-8">
            <!-- Status Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📋 Trạng thái báo cáo</h5>
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
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check"></i> Cập nhật
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
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">🔧 Chi tiết hư hỏng</h5>
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
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">🏨 Thông tin phòng</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Phòng:</strong> {{ $damageReport->room->room_number ?? 'N/A' }}</p>
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
            <div class="card shadow-sm mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">👤 Thông tin khách đang ở</h5>
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
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">📜 Lịch sử chuyển phòng</h5>
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
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">🔄 Chuyển phòng cho khách</h5>
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
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-arrow-left-right"></i> Chuyển phòng
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Refund Action -->
            @if($damageReport->booking_id && !$damageReport->isResolved())
            <div class="card shadow-sm mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">💰 Hoàn tiền cho khách</h5>
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
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-cash-stack"></i> Xử lý hoàn tiền
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Quick Info -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">ℹ️ Thông tin nhanh</h5>
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
