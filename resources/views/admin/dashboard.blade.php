@extends('layouts.admin')

@section('title', 'Bảng điều khiển - Admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Bảng điều khiển Admin</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="bi bi-download me-1"></i> Xuất báo cáo</button>
            <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Thêm mới</button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="fs-6 fw-bold text-uppercase text-primary mb-1">Tổng phòng</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">24</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-primary-light text-primary-dark">
                                <i class="bi bi-door-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="fs-6 fw-bold text-uppercase text-success mb-1">Đơn đặt phòng</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">124</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-success-light text-success-dark">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="fs-6 fw-bold text-uppercase text-warning mb-1">Khách hàng</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">58</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-warning-light text-warning-dark">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="fs-6 fw-bold text-uppercase text-danger mb-1">Doanh thu</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">42.500.000 ₫</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-danger-light text-danger-dark">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-xl-8 col-lg-7">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h6 class="m-0 fw-bold">Đơn đặt phòng gần đây</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Khách hàng</th>
                                    <th>Phòng</th>
                                    <th>Ngày nhận</th>
                                    <th>Ngày trả</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#BK001</td>
                                    <td>Nguyễn Văn A</td>
                                    <td>Phòng Deluxe</td>
                                    <td>05/02/2026</td>
                                    <td>07/02/2026</td>
                                    <td><span class="badge bg-success">Hoàn tất</span></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                        <a href="#" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#BK002</td>
                                    <td>Trần Thị B</td>
                                    <td>Phòng Family</td>
                                    <td>10/02/2026</td>
                                    <td>12/02/2026</td>
                                    <td><span class="badge bg-warning text-dark">Đang xử lý</span></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                        <a href="#" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#BK003</td>
                                    <td>Lê Văn C</td>
                                    <td>Phòng Suite</td>
                                    <td>15/02/2026</td>
                                    <td>18/02/2026</td>
                                    <td><span class="badge bg-info">Chờ xác nhận</span></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                        <a href="#" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#BK004</td>
                                    <td>Phạm Thị D</td>
                                    <td>Phòng Superior</td>
                                    <td>20/02/2026</td>
                                    <td>22/02/2026</td>
                                    <td><span class="badge bg-success">Hoàn tất</span></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                        <a href="#" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings Overview and Room Status -->
        <div class="col-xl-4 col-lg-5">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h6 class="m-0 fw-bold">Tình trạng phòng</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <div class="d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Phòng trống</span>
                                <span class="badge bg-success">18</span>
                            </div>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 75%"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Đã đặt</span>
                                <span class="badge bg-warning text-dark">4</span>
                            </div>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 17%"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Bảo trì</span>
                                <span class="badge bg-secondary">2</span>
                            </div>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-secondary" role="progressbar" style="width: 8%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="me-2"><i class="fas fa-circle text-success"></i> Trống</span>
                        <span class="me-2"><i class="fas fa-circle text-warning"></i> Đã đặt</span>
                        <span class="me-2"><i class="fas fa-circle text-secondary"></i> Bảo trì</span>
                    </div>
                </div>
            </div>

            <!-- Revenue Overview -->
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h6 class="m-0 fw-bold">Doanh thu theo tháng</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyRevenueChart').getContext('2d');
    const monthlyRevenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6'],
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: [32000000, 42500000, 38000000, 45000000, 52000000, 48000000],
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
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' ₫';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection