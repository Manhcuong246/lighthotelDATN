@extends('layouts.admin')

@section('title', 'Bảng điều khiển - Admin')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Bảng điều khiển</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.statistics.export') }}" class="btn btn-outline-primary btn-sm btn-admin-icon" title="Xuất báo cáo"><i class="bi bi-download"></i></a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary btn-sm btn-admin-icon" title="Thêm đơn"><i class="bi bi-plus-lg"></i></a>
            @endif
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100 border-left-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="fs-6 fw-bold text-uppercase text-primary mb-1">Doanh thu hôm nay</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ number_format($todayRevenue, 0, ',', '.') }} ₫</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-primary-light text-primary-dark">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100 border-left-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="fs-6 fw-bold text-uppercase text-success mb-1">Doanh thu tháng</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ number_format($monthlyRevenue, 0, ',', '.') }} ₫</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-success-light text-success-dark">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100 border-left-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="fs-6 fw-bold text-uppercase text-info mb-1">Tỉ lệ lấp phòng</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $occupancyRate }}%</div>
                            <div class="small text-muted">Trung bình tháng: {{ $monthlyOccupancyRate }}%</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-info-light text-info-dark">
                                <i class="bi bi-pie-chart"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100 border-left-danger">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="fs-6 fw-bold text-uppercase text-danger mb-1">Tổng doanh thu</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ number_format($totalRevenue, 0, ',', '.') }} ₫</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-danger-light text-danger-dark">
                                <i class="bi bi-wallet2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="row g-3 g-md-4">
        <div class="col-12 col-md-6 col-xl-6">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h6 class="m-0 fw-bold">Top 5 phòng có doanh thu cao nhất</h6>
                </div>
                <div class="card-body">
                    @if($topRoomsByRevenue->isNotEmpty())
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div style="height: 260px; position: relative;">
                                <canvas id="topRoomsRevenueChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0">
                                @foreach($topRoomsByRevenue as $i => $r)
                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-truncate" style="max-width: 70%;">{{ $r->name }}</span>
                                    <strong class="text-success">{{ number_format($r->total_revenue, 0, ',', '.') }} ₫</strong>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @else
                    <p class="text-center text-muted py-5 mb-0">Chưa có dữ liệu doanh thu theo phòng.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-6">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h6 class="m-0 fw-bold">Tình trạng phòng</h6>
                </div>
                <div class="card-body">
                    @php
                        $totalRoomsForBar = max(1, $totalRooms);
                        $pctAvailable = round(($roomsAvailable / $totalRoomsForBar) * 100);
                        $pctBooked = round(($roomsBooked / $totalRoomsForBar) * 100);
                        $pctMaintenance = round(($roomsMaintenance / $totalRoomsForBar) * 100);
                    @endphp
                    <div class="chart-pie pt-4 pb-2">
                        <div class="d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Phòng trống</span>
                                <span class="badge bg-success">{{ $roomsAvailable }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctAvailable }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Có lịch đêm nay</span>
                                <span class="badge bg-warning text-dark">{{ $roomsBooked }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $pctBooked }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Bảo trì</span>
                                <span class="badge bg-secondary">{{ $roomsMaintenance }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $pctMaintenance }}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="me-2"><i class="bi bi-circle-fill text-success"></i> Trống</span>
                        <span class="me-2"><i class="bi bi-circle-fill text-warning"></i> Có lịch đêm nay</span>
                        <span class="me-2"><i class="bi bi-circle-fill text-secondary"></i> Bảo trì</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-6">
            <!-- Revenue Overview -->
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h6 class="m-0 fw-bold">Doanh thu 7 ngày gần nhất</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 220px;">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-6">
            <!-- Occupancy Overview -->
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h6 class="m-0 fw-bold">Tỉ lệ lấp phòng 7 ngày gần nhất</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 220px;">
                        <canvas id="occupancyRateChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Biểu đồ tròn: Top 5 phòng doanh thu cao nhất
    @if($topRoomsByRevenue->isNotEmpty())
    const topRoomsCtx = document.getElementById('topRoomsRevenueChart');
    if (topRoomsCtx) {
        new Chart(topRoomsCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($topRoomsByRevenue->pluck('name')->values()) !!},
                datasets: [{
                    data: {!! json_encode($topRoomsByRevenue->pluck('total_revenue')->values()) !!},
                    backgroundColor: ['#4361ee', '#3b82f6', '#0ea5e9', '#06b6d4', '#14b8a6'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return ctx.label + ': ' + new Intl.NumberFormat('vi-VN').format(ctx.raw) + ' ₫ (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    @endif

    // Biểu đồ doanh thu 7 ngày
    const ctx = document.getElementById('monthlyRevenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($revenueChart['labels']) !!},
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: {!! json_encode($revenueChart['data']) !!},
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: {{ $revenueChart['suggestedMax'] ?? 1000000 }},
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN').format(value) + ' ₫';
                        }
                    }
                }
            }
        }
    });

    // Biểu đồ tỉ lệ lấp phòng 7 ngày
    const occupancyCtx = document.getElementById('occupancyRateChart').getContext('2d');
    const occupancyLineChart = new Chart(occupancyCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($occupancyChart['labels'] ?? []) !!},
            datasets: [{
                label: 'Tỉ lệ lấp phòng (%)',
                data: {!! json_encode($occupancyChart['data'] ?? []) !!},
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection