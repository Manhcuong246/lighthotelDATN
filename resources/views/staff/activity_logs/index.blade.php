@extends('layouts.admin')

@section('title', 'Nhật ký hoạt động')

@section('content')

<div class="container-fluid px-3 px-lg-4">

    <!-- Tiêu đề -->
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1">
            Nhật ký hoạt động
        </h1>

        <div class="text-muted small">
            Theo dõi các thao tác của nhân viên như check-in, check-out và đặt phòng.
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row g-3 mb-4">

        <!-- Tổng hoạt động -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="small text-muted">
                        Tổng hoạt động hôm nay
                    </div>

                    <div class="h4 fw-bold text-primary">
                        {{ $todayLogsCount ?? 0 }}
                    </div>

                </div>
            </div>
        </div>

        <!-- Check-in -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="small text-muted">
                        Check-in hôm nay
                    </div>

                    <div class="h4 fw-bold text-success">
                        {{ $checkInLogsCount ?? 0 }}
                    </div>

                </div>
            </div>
        </div>

        <!-- Check-out -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="small text-muted">
                        Check-out hôm nay
                    </div>

                    <div class="h4 fw-bold text-danger">
                        {{ $checkOutLogsCount ?? 0 }}
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Danh sách nhật ký -->
    <div class="card border-0 shadow-sm">

        <div class="card-header fw-bold">
            Danh sách hoạt động gần đây
        </div>

        <div class="card-body p-0">

            <table class="table mb-0">

                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Nhân viên</th>
                        <th>Hành động</th>
                        <th>Nội dung</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($logs as $log)

                    <tr>

                        <!-- Thời gian -->
                        <td>
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}
                        </td>

                        <!-- Nhân viên -->
                        <td>
                            {{ $log->user->full_name ?? 'N/A' }}
                        </td>

                        <!-- Hành động -->
                        <td>

                            @if($log->action == 'Check-in')

                                <span class="badge bg-success">
                                    Check-in
                                </span>

                            @elseif($log->action == 'Check-out')

                                <span class="badge bg-danger">
                                    Check-out
                                </span>

                            @elseif($log->action == 'Create Booking')

                                <span class="badge bg-primary">
                                    Đặt phòng
                                </span>

                            @else

                                <span class="badge bg-secondary">
                                    {{ $log->action }}
                                </span>

                            @endif

                        </td>

                        <!-- Nội dung -->
                        <td>
                            {{ $log->description }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            Chưa có hoạt động nào
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <!-- Pagination -->
        <div class="card-footer">
            {{ $logs->links() }}
        </div>

    </div>

</div>

@endsection