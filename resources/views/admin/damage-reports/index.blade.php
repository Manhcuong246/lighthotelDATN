@extends('layouts.admin')

@section('title', 'Quản lý báo cáo hư hỏng')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-dark fw-bold">🚨 Quản lý báo cáo hư hỏng</h1>
        <a href="{{ route('admin.damage-reports.create') }}" class="btn btn-danger">
            <i class="bi bi-plus-lg me-1"></i>Tạo báo cáo mới
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h3 class="text-danger mb-1">{{ $reports->where('severity', 'urgent')->count() }}</h3>
                    <small class="text-muted">Khẩn cấp</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-1">{{ $reports->where('severity', 'high')->count() }}</h3>
                    <small class="text-muted">Cao</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h3 class="text-info mb-1">{{ $reports->where('status', 'in_progress')->count() }}</h3>
                    <small class="text-muted">Đang xử lý</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success mb-1">{{ $reports->where('status', 'resolved')->count() }}</h3>
                    <small class="text-muted">Đã giải quyết</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Phòng</th>
                            <th>Loại lỗi</th>
                            <th>Mức độ</th>
                            <th>Trạng thái</th>
                            <th>Có khách</th>
                            <th>Ngày báo cáo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                        <tr class="{{ $report->severity === 'urgent' ? 'table-danger' : ($report->severity === 'high' ? 'table-warning' : '') }}">
                            <td>#{{ $report->id }}</td>
                            <td>
                                <strong>Phòng {{ $report->room->room_number ?? 'N/A' }}</strong>
                                <br><small class="text-muted">{{ $report->room->roomType->name ?? '' }}</small>
                            </td>
                            <td>{{ \App\Models\DamageReport::getDamageTypes()[$report->damage_type] ?? $report->damage_type }}</td>
                            <td>{!! $report->severity_badge !!}</td>
                            <td>{!! $report->status_badge !!}</td>
                            <td>
                                @if($report->booking_id)
                                    <span class="badge bg-danger">Có khách</span>
                                    @if($report->requires_room_change)
                                        <br><small class="text-danger">Cần chuyển phòng</small>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">Trống</span>
                                @endif
                            </td>
                            <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.damage-reports.show', $report) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Chi tiết
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle fs-1 text-success"></i>
                                <p class="mt-2">Không có báo cáo hư hỏng nào</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($reports->hasPages())
        <div class="card-footer">
            {{ $reports->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
