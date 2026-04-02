@extends('layouts.admin')

@section('title', 'Bảng điều khiển')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Bảng điều khiển</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.statistics.export') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-download me-1"></i>Xuất báo cáo</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Thêm đơn</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <!-- Doanh thu hôm nay -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-admin shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Doanh thu hôm nay</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($todayRevenue, 0, ',', '.') }} ₫</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doanh thu tháng -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-admin shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Doanh thu tháng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($monthlyRevenue, 0, ',', '.') }} ₫</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tỉ lệ lấp phòng -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-admin shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Lấp phòng hôm nay</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $occupancyRate }}%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $occupancyRate }}%" aria-valuenow="{{ $occupancyRate }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bed fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tổng doanh thu -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-admin shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Tổng doanh thu</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalRevenue, 0, ',', '.') }} ₫</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3">
        <!-- Doanh thu Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card card-admin shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold">Biểu đồ doanh thu 7 ngày</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top phòng doanh thu -->
        <div class="col-xl-4 col-lg-5">
            <div class="card card-admin shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold">Top 5 phòng doanh thu</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="topRoomsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Biểu đồ doanh thu
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: @json($revenueChart['labels']),
            datasets: [{
                label: 'Doanh thu',
                data: @json($revenueChart['data']),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + ' ₫';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toLocaleString('vi-VN') + ' ₫';
                        }
                    }
                }
            }
        }
    });

    // Biểu đồ tròn top phòng
    @if($topRoomsByRevenue->isNotEmpty())
    const topRoomsCtx = document.getElementById('topRoomsChart').getContext('2d');
    const topRoomsChart = new Chart(topRoomsCtx, {
        type: 'doughnut',
        data: {
            labels: @json($topRoomsByRevenue->pluck('name')),
            datasets: [{
                data: @json($topRoomsByRevenue->pluck('total_revenue')),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB', 
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed.toLocaleString('vi-VN') + ' ₫';
                        }
                    }
                }
            }
        }
    });
    @endif
});
</script>
@endsection
