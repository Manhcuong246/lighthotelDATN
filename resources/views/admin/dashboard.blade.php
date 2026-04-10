@extends('layouts.admin')

@section('title', 'Bảng điều khiển - Admin')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Bảng điều khiển</h1>
            <div class="text-muted small">Theo dõi doanh thu, tình trạng phòng và hiệu suất theo khoảng thời gian.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.statistics.export') }}" class="btn btn-outline-primary btn-sm btn-admin-icon rounded-2" title="Xuất báo cáo"><i class="bi bi-download"></i></a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary btn-sm btn-admin-icon rounded-2" title="Thêm đơn"><i class="bi bi-plus-lg"></i></a>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-7">
                    <label class="form-label small fw-bold mb-1">Khoảng thời gian</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 js-range" data-range="7d">7 ngày</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 js-range" data-range="30d">30 ngày</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 js-range" data-range="this_month">Tháng này</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 js-range" data-range="last_month">Tháng trước</button>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="dashStart" class="form-label small fw-bold mb-1">Từ ngày</label>
                            <input id="dashStart" type="date" class="form-control form-control-sm" value="{{ ($rangeStart ?? now()->subDays(6))->toDateString() }}">
                        </div>
                        <div class="col-6">
                            <label for="dashEnd" class="form-label small fw-bold mb-1">Đến ngày</label>
                            <input id="dashEnd" type="date" class="form-control form-control-sm" value="{{ ($rangeEnd ?? now())->toDateString() }}">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm rounded-2 w-100" id="dashApply">
                                <i class="bi bi-funnel me-1"></i>Áp dụng
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-2" id="dashReset" title="Về mặc định">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                        <div class="col-12">
                            <div id="dashHint" class="small text-muted"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100 border-left-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="fs-6 fw-bold text-uppercase text-primary mb-1">Doanh thu (khoảng chọn)</div>
                            <div class="h5 mb-0 fw-bold text-gray-800" id="kpiRevenueRange">{{ number_format($todayRevenue, 0, ',', '.') }} ₫</div>
                            <div class="small text-muted" id="kpiRevenueRangeSub">Mặc định 7 ngày gần nhất</div>
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
                            <div class="fs-6 fw-bold text-uppercase text-success mb-1">Doanh thu tháng này</div>
                            <div class="h5 mb-0 fw-bold text-gray-800" id="kpiRevenueMonth">{{ number_format($monthlyRevenue, 0, ',', '.') }} ₫</div>
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
                            <div class="fs-6 fw-bold text-uppercase text-danger mb-1">Đơn tạo trong khoảng chọn</div>
                            <div class="h5 mb-0 fw-bold text-gray-800" id="kpiBookingsRange">{{ number_format($totalBookings, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-danger-light text-danger-dark">
                                <i class="bi bi-journal-check"></i>
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
                    <h6 class="m-0 fw-bold">Top phòng theo doanh thu (khoảng chọn)</h6>
                </div>
                <div class="card-body">
                    @if($topRoomsByRevenue->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60%">Phòng</th>
                                    <th class="text-end">Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody id="topRoomsTableBody">
                                @foreach($topRoomsByRevenue as $r)
                                <tr>
                                    <td class="text-truncate" style="max-width: 340px;">{{ $r->name }}</td>
                                    <td class="text-end fw-semibold text-success">{{ number_format($r->total_revenue, 0, ',', '.') }} ₫</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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
                    <h6 class="m-0 fw-bold">Doanh thu theo ngày</h6>
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
                    <h6 class="m-0 fw-bold">Tỉ lệ lấp phòng theo ngày</h6>
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
    const fmtVND = (n) => new Intl.NumberFormat('vi-VN').format(Math.round(Number(n) || 0)) + ' ₫';
    const fmtInt = (n) => new Intl.NumberFormat('vi-VN').format(Math.round(Number(n) || 0));

    // Biểu đồ doanh thu theo ngày
    const revenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
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
                legend: { display: false }
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

    // Biểu đồ tỉ lệ lấp phòng theo ngày
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
                legend: { display: false }
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

    const startInput = document.getElementById('dashStart');
    const endInput = document.getElementById('dashEnd');
    const hintEl = document.getElementById('dashHint');

    const kpiRevenueRange = document.getElementById('kpiRevenueRange');
    const kpiRevenueRangeSub = document.getElementById('kpiRevenueRangeSub');
    const kpiBookingsRange = document.getElementById('kpiBookingsRange');

    function setHint(start, end) {
        if (hintEl) hintEl.textContent = `Đang xem: ${start} → ${end}`;
        if (kpiRevenueRangeSub) kpiRevenueRangeSub.textContent = `Khoảng: ${start} → ${end}`;
    }

    async function loadDashboardData(start, end) {
        setHint(start, end);

        const url = new URL(@json(route('admin.dashboard.data')), window.location.origin);
        url.searchParams.set('start', start);
        url.searchParams.set('end', end);

        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const json = await res.json();

        const kpis = json.kpis || {};
        if (kpiRevenueRange) kpiRevenueRange.textContent = fmtVND(kpis.revenue);
        if (kpiBookingsRange) kpiBookingsRange.textContent = fmtInt(kpis.bookings);

        const revenue = (json.charts && json.charts.revenue) ? json.charts.revenue : null;
        const occupancy = (json.charts && json.charts.occupancy) ? json.charts.occupancy : null;

        if (revenue) {
            revenueChart.data.labels = revenue.labels || [];
            revenueChart.data.datasets[0].data = revenue.data || [];
            revenueChart.options.scales.y.suggestedMax = revenue.suggestedMax || 1000000;
            revenueChart.update();
        }

        if (occupancy) {
            occupancyLineChart.data.labels = occupancy.labels || [];
            occupancyLineChart.data.datasets[0].data = occupancy.data || [];
            occupancyLineChart.update();
        }

        const rows = Array.isArray(json.top_rooms) ? json.top_rooms : [];
        const tbody = document.getElementById('topRoomsTableBody');
        if (tbody) {
            if (rows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted py-3">Chưa có dữ liệu trong khoảng này.</td></tr>`;
            } else {
                tbody.innerHTML = rows.map(r => {
                    const name = (r && r.name) ? String(r.name) : '—';
                    const amt = (r && r.total_revenue != null) ? r.total_revenue : 0;
                    return `<tr>
                        <td class="text-truncate" style="max-width: 340px;">${escapeHtml(name)}</td>
                        <td class="text-end fw-semibold text-success">${fmtVND(amt)}</td>
                    </tr>`;
                }).join('');
            }
        }
    }

    function escapeHtml(str) {
        return str
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function applyCurrentRange() {
        const start = startInput.value;
        const end = endInput.value;
        if (!start || !end) return;
        loadDashboardData(start, end).catch(err => {
            console.error(err);
            alert('Không tải được dữ liệu dashboard: ' + err.message);
        });
    }

    function setRangePreset(kind) {
        const today = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const toDateStr = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

        const end = new Date(today);
        let start = new Date(today);

        if (kind === '7d') start.setDate(start.getDate() - 6);
        if (kind === '30d') start.setDate(start.getDate() - 29);
        if (kind === 'this_month') start = new Date(today.getFullYear(), today.getMonth(), 1);
        if (kind === 'last_month') {
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const endLast = new Date(today.getFullYear(), today.getMonth(), 0);
            endInput.value = toDateStr(endLast);
            startInput.value = toDateStr(start);
            applyCurrentRange();
            return;
        }

        startInput.value = toDateStr(start);
        endInput.value = toDateStr(end);
        applyCurrentRange();
    }

    document.getElementById('dashApply')?.addEventListener('click', applyCurrentRange);
    document.getElementById('dashReset')?.addEventListener('click', () => setRangePreset('7d'));
    document.querySelectorAll('.js-range').forEach(btn => {
        btn.addEventListener('click', () => setRangePreset(btn.dataset.range));
    });

    // initial hint
    setHint(startInput.value, endInput.value);
});
</script>
@endsection