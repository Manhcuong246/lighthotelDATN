@extends('layouts.admin')

@section('title', 'Chi tiết báo cáo #' . $report->id)

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Báo cáo #{{ $report->id }}</h3>
            <small class="text-muted">Chi tiết hư hỏng phòng</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.damage-reports.edit', $report->id) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil"></i> Sửa
            </a>
            <a href="{{ route('staff.damage-reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Danh sách
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Phòng</dt>
                <dd class="col-sm-9">
                    @if($report->room)
                        Phòng {{ $report->room->room_number ?? $report->room->id }}
                        @if($report->room->roomType)
                            <span class="text-muted">({{ $report->room->roomType->name }})</span>
                        @endif
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3">Loại hư hỏng</dt>
                <dd class="col-sm-9 fw-semibold">
                    {{ $damageTypes[$report->damage_type] ?? $report->damage_type }}
                </dd>

                <dt class="col-sm-3">Mức độ</dt>
                <dd class="col-sm-9">{{ $severityLabels[$report->severity] ?? $report->severity }}</dd>

                <dt class="col-sm-3">Trạng thái</dt>
                <dd class="col-sm-9">{!! $report->status_badge !!}</dd>

                <dt class="col-sm-3">Người báo cáo</dt>
                <dd class="col-sm-9">{{ $report->reporter->full_name ?? $report->reporter->email ?? '—' }}</dd>

                <dt class="col-sm-3">Mô tả</dt>
                <dd class="col-sm-9">{{ $report->description }}</dd>

                <dt class="col-sm-3">Thời gian</dt>
                <dd class="col-sm-9">{{ $report->created_at?->format('d/m/Y H:i') }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
