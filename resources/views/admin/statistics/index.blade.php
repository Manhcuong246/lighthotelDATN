@extends('layouts.admin')

@section('title', 'Thống kê - Admin')

@section('content')
<div class="container-fluid admin-page px-0">
    <div class="page-header mb-4">
        <h1>Thống kê doanh thu &amp; tỉ lệ lấp phòng</h1>
    </div>

    <!-- Thẻ thống kê chính -->
    <div class="row mb-4">
        <!-- Doanh thu hôm nay -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-primary text-uppercase mb-1">Doanh Thu Hôm Nay</div>
                    <div class="h3 mb-0">{{ number_format($todayRevenue, 0, ',', '.') }} đ</div>
                </div>
            </div>
        </div>

        <!-- Doanh thu tháng này -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-success text-uppercase mb-1">Doanh Thu Tháng Này</div>
                    <div class="h3 mb-0">{{ number_format($monthlyRevenue, 0, ',', '.') }} đ</div>
                </div>
            </div>
        </div>

        <!-- Tỉ lệ lấp phòng hôm nay -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-info text-uppercase mb-1">Tỉ Lệ Lấp Phòng Hôm Nay</div>
                    <div class="h3 mb-0">{{ $todayOccupancyRate }}%</div>
                </div>
            </div>
        </div>

        <!-- Tỉ lệ lấp phòng tháng này -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-warning text-uppercase mb-1">Tỉ Lệ Lấp Phòng Tháng Này</div>
                    <div class="h3 mb-0">{{ $occupancyRate }}%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hàng thứ hai thống kê -->
    <div class="row mb-4">
        <!-- Đặt phòng hôm nay -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-secondary text-uppercase mb-1">Đặt Phòng Hôm Nay</div>
                    <div class="h3 mb-0">{{ $todayBookings }}</div>
                </div>
            </div>
        </div>

        <!-- Đặt phòng tháng này -->
        <div class="col-md-3 mb-3">
            <div class="card border-left-dark shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-dark text-uppercase mb-1">Đặt Phòng Tháng Này</div>
                    <div class="h3 mb-0">{{ $monthlyBookings }}</div>
                </div>
            </div>
        </div>

        <!-- Doanh thu tổng cộng -->
        <div class="col-md-6 mb-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-danger text-uppercase mb-1">Doanh Thu Tổng Cộng</div>
                    <div class="h3 mb-0">{{ number_format($totalRevenue, 0, ',', '.') }} đ</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ -->
    <div class="row">
        <!-- Biểu đồ doanh thu -->
        <div class="col-lg-6 mb-4">
            <div class="card card-admin shadow-sm">
                <div class="card-header-admin py-3">
                    <h6 class="m-0">Doanh thu 7 ngày gần nhất</h6>
                </div>
                <div class="card-body">
                    <div class="admin-chart-box admin-chart-box--sm">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ tỉ lệ lấp phòng -->
        <div class="col-lg-6 mb-4">
            <div class="card card-admin shadow-sm">
                <div class="card-header-admin py-3">
                    <h6 class="m-0">Tỉ lệ lấp phòng 7 ngày gần nhất</h6>
                </div>
                <div class="card-body">
                    <div class="admin-chart-box admin-chart-box--sm">
                        <canvas id="occupancyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script để vẽ biểu đồ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    // Biểu đồ doanh thu
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($revenueChart['labels']) !!},
            datasets: [{
                label: 'Doanh Thu (đ)',
                data: {!! json_encode($revenueChart['data']) !!},
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#4e73df',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + ' đ';
                        }
                    }
                }
            }
        }
    });

    // Biểu đồ tỉ lệ lấp phòng
    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    new Chart(occupancyCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($occupancyChart['labels']) !!},
            datasets: [{
                label: 'Tỉ Lệ Lấp Phòng (%)',
                data: {!! json_encode($occupancyChart['data']) !!},
                backgroundColor: '#36b9cc',
                borderColor: '#36b9cc',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
</script>
@endsection
