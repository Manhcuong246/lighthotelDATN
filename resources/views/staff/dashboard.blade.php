@extends('layouts.admin')

@section('title', 'Staff Dashboard')

@section('content')

<div class="container-fluid px-3 px-lg-4">

    <!-- Tiêu đề -->
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1">
            Dashboard Nhân viên
        </h1>

        <div class="text-muted small">
            Tổng quan tình trạng phòng và khách — hiển thị bằng biểu đồ.
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.bookings.index') }}">Đặt phòng</a>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('staff.damage-reports.index') }}">Báo hỏng</a>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('staff.room-changes.index') }}">Đổi phòng</a>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Check-in hôm nay (lịch)</div>
                    <div class="h4 fw-bold text-primary">{{ $checkInToday }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Check-out hôm nay (lịch)</div>
                    <div class="h4 fw-bold text-danger">{{ $checkOutToday }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Khách đang lưu trú</div>
                    <div class="h4 fw-bold text-info">{{ $guestsStaying }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Phòng trống</div>
                    <div class="h4 fw-bold text-success">{{ $roomsAvailable }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Phòng bảo trì</div>
                    <div class="h4 fw-bold text-secondary">{{ $roomsMaintenance }}</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Biểu đồ: check-in / check-out hôm nay -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100 dash-staff-card">
                <div class="card-body p-3 p-md-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 fw-bold mb-0">Check-in hôm nay</h2>
                        <span class="badge bg-light text-muted border">Trạng thái</span>
                    </div>
                    <div class="staff-dash-chart flex-grow-1">
                        <canvas id="staffCheckInChart"></canvas>
                    </div>
                    @if(array_sum($checkInChart['data']) === 0)
                        <p class="text-center text-muted small mb-0 mt-2">Không có lịch check-in hôm nay.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100 dash-staff-card">
                <div class="card-body p-3 p-md-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 fw-bold mb-0">Check-out hôm nay</h2>
                        <span class="badge bg-light text-muted border">Trạng thái</span>
                    </div>
                    <div class="staff-dash-chart flex-grow-1">
                        <canvas id="staffCheckOutChart"></canvas>
                    </div>
                    @if(array_sum($checkoutChart['data']) === 0)
                        <p class="text-center text-muted small mb-0 mt-2">Không có lịch check-out hôm nay.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Xu hướng 7 ngày -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm dash-staff-card">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0">Lịch check-in / check-out (7 ngày gần nhất)</h2>
                        <span class="badge bg-light text-muted border">Số đơn theo ngày</span>
                    </div>
                    <div class="staff-dash-chart-trend">
                        <canvas id="staffWeekTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p class="small text-muted mb-0">
        Cần xem chi tiết từng đơn? Mở <a href="{{ route('admin.bookings.index') }}">danh sách đặt phòng</a>.
    </p>

</div>

<style>
    .dash-staff-card { border: 1px solid rgba(15, 23, 42, 0.06); }
    .staff-dash-chart { height: 260px; position: relative; }
    .staff-dash-chart-trend { height: 300px; position: relative; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sum = (arr) => arr.reduce((a, b) => a + b, 0);

    const checkInData = @json($checkInChart['data']);
    const checkInLabels = @json($checkInChart['labels']);
    const ciEl = document.getElementById('staffCheckInChart');
    if (ciEl && sum(checkInData) > 0) {
        new Chart(ciEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: checkInLabels,
                datasets: [{
                    data: checkInData,
                    backgroundColor: ['#198754', '#ffc107'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });
    }

    const coData = @json($checkoutChart['data']);
    const coLabels = @json($checkoutChart['labels']);
    const coEl = document.getElementById('staffCheckOutChart');
    if (coEl && sum(coData) > 0) {
        new Chart(coEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: coLabels,
                datasets: [{
                    data: coData,
                    backgroundColor: ['#6c757d', '#0dcaf0', '#e9ecef'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });
    }

    const wEl = document.getElementById('staffWeekTrendChart');
    if (wEl) {
        new Chart(wEl.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($weekLabels),
                datasets: [
                    {
                        label: 'Check-in (lịch)',
                        data: @json($weekCheckInCounts),
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                    },
                    {
                        label: 'Check-out (lịch)',
                        data: @json($weekCheckOutCounts),
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.06)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                    },
                },
            },
        });
    }
});
</script>

@endsection
