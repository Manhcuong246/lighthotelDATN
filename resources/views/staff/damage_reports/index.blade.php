@extends('layouts.admin')

@section('title', 'Báo cáo hư hỏng (Staff)')

@section('content')
@php
    $damageTypeLabels = \App\Models\DamageReport::getDamageTypes();
@endphp
<div class="container-fluid px-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Báo cáo hư hỏng</h3>
            <small class="text-muted">Quản lý các báo cáo hư hỏng phòng</small>
        </div>

        <a href="{{ route('staff.damage-reports.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tạo báo cáo
        </a>
    </div>

    {{-- THÔNG BÁO --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- BỘ LỌC --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label class="form-label small text-muted">Tìm kiếm</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Loại, mã (broken_bed), hoặc nội dung mô tả..."
                           value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>

                        <option value="reported" {{ request('status')=='reported' ? 'selected' : '' }}>
                            Chờ xử lý
                        </option>

                        <option value="in_progress" {{ request('status')=='in_progress' ? 'selected' : '' }}>
                            Đang xử lý
                        </option>

                        <option value="resolved" {{ request('status')=='resolved' ? 'selected' : '' }}>
                            Hoàn thành
                        </option>

                        <option value="cancelled" {{ request('status')=='cancelled' ? 'selected' : '' }}>
                            Đã huỷ
                        </option>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-dark w-100">
                        <i class="bi bi-search"></i> Lọc
                    </button>

                    <a href="{{ route('staff.damage-reports.index') }}" class="btn btn-outline-secondary w-100">
                        Đặt lại
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- BẢNG --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th class="px-4">Phòng</th>
                            <th>Loại hư hỏng</th>
                            <th>Mức độ</th>
                            <th>Trạng thái</th>
                            <th width="200" class="text-end px-4">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td class="px-4">
                                    {{ $report->room ? ('Phòng ' . ($report->room->room_number ?? $report->room->id)) : '—' }}
                                </td>
                                <td class="fw-semibold">
                                    {{ $damageTypeLabels[$report->damage_type] ?? $report->damage_type }}
                                </td>
                                <td>
                                    {{ \App\Models\DamageReport::getSeverityLabels()[$report->severity] ?? $report->severity }}
                                </td>
                                <td>
                                    @switch($report->status)
                                        @case('reported')
                                            <span class="badge bg-warning text-dark">Chờ xử lý</span>
                                            @break

                                        @case('in_progress')
                                            <span class="badge bg-info text-dark">Đang xử lý</span>
                                            @break

                                        @case('resolved')
                                            <span class="badge bg-success">Hoàn thành</span>
                                            @break

                                        @case('cancelled')
                                            <span class="badge bg-danger">Đã huỷ</span>
                                            @break
                                    @endswitch
                                </td>

                                <td class="text-end px-4">
                                    <a href="{{ route('staff.damage-reports.show', $report->id) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('staff.damage-reports.edit', $report->id) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="{{ route('staff.damage-reports.destroy', $report->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xoá?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    Không có dữ liệu
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>

        {{-- PAGINATION --}}
        @if($reports->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $reports->links() }}
            </div>
        @endif

    </div>

</div>
@endsection