@extends('layouts.admin')

@section('title', 'Bảng điều khiển - Admin')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Bảng điều khiển</h1>
          <div class="text-muted small">
@if(auth()->user()->isAdmin())
    Tổng quan doanh thu, tình trạng phòng và hiệu suất vận hành.
@else
    Tổng quan tình trạng phòng hôm nay.
@endif
</div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if(auth()->user()->isAdmin() && isset($roomTypes))
            <form method="get" action="{{ route('admin.dashboard') }}" class="d-flex flex-wrap align-items-center gap-2">
                <label class="small text-muted mb-0 text-nowrap">Loại phòng</label>
                <select name="room_type_id" class="form-select form-select-sm" style="min-width: 200px;" onchange="this.form.submit()">
                    <option value="">Tất cả</option>
                    @foreach($roomTypes as $rt)
                        <option value="{{ $rt->id }}" @selected((int) ($roomTypeFilterId ?? 0) === (int) $rt->id)>{{ $rt->name }}</option>
                    @endforeach
                </select>
            </form>
            @endif
            <a href="{{ route('admin.statistics.export', array_filter(['room_type_id' => $roomTypeFilterId ?? null])) }}" class="btn btn-outline-primary btn-sm btn-admin-icon rounded-2" title="Xuất báo cáo">
                <i class="bi bi-download"></i>
            </a>
        </div>
    </div>

    @if(auth()->user()->isAdmin() && ($roomTypeFilterId ?? null))
    <p class="small text-muted mb-3">Đang lọc theo <strong>một loại phòng</strong>. Doanh thu trên thẻ là tổng thành tiền dòng đặt (đơn đã thanh toán); biểu đồ cùng phạm vi.</p>
    @endif

    <!-- Stats Cards -->
<div class="row g-3 g-md-4 mb-4">

@if(auth()->user()->isAdmin())

    <!-- ADMIN: Hiển thị doanh thu -->

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100 border-0">
            <div class="card-body p-3 p-md-4">
                <div class="small text-muted text-uppercase">
                    Doanh thu hôm nay
                </div>
                <div class="h5 fw-bold">
                    {{ number_format($todayRevenue, 0, ',', '.') }} ₫
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100 border-0">
            <div class="card-body">
                <div class="small text-muted text-uppercase">
                    Doanh thu tháng này
                </div>
                <div class="h5 fw-bold">
                    {{ number_format($monthlyRevenue, 0, ',', '.') }} ₫
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100 border-0">
            <div class="card-body">
                <div class="small text-muted text-uppercase">
                    Tỉ lệ lấp phòng
                </div>
                <div class="h5 fw-bold">
                    {{ $occupancyRate }}%
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100 border-0">
            <div class="card-body">
                <div class="small text-muted text-uppercase">
                    Tổng doanh thu
                </div>
                <div class="h5 fw-bold">
                    {{ number_format($totalRevenue, 0, ',', '.') }} ₫
                </div>
            </div>
        </div>
    </div>

@else

    <!-- STAFF: Chỉ hiển thị phòng -->

    <div class="col-md-4">
        <div class="card stat-card border-0">
            <div class="card-body">

                <div class="small text-muted text-uppercase">
                    Phòng trống
                </div>

                <div class="h4 fw-bold text-success">
                    {{ $roomsAvailable }}
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card border-0">
            <div class="card-body">

                <div class="small text-muted text-uppercase">
                    Có lịch hôm nay
                </div>

                <div class="h4 fw-bold text-warning">
                    {{ $roomsBooked }}
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card border-0">
            <div class="card-body">

                <div class="small text-muted text-uppercase">
                    Bảo trì
                </div>

                <div class="h4 fw-bold text-secondary">
                    {{ $roomsMaintenance }}
                </div>

            </div>
        </div>
    </div>

@endif

</div>
   @if(auth()->user()->isAdmin())

<!-- Charts and Tables Row -->
<div class="row g-3 g-md-4 align-items-stretch">
        <div class="col-12 col-md-6 col-xl-6">
            <div class="card card-admin h-100 dash-card">
                <div class="card-body p-3 p-md-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0">Top 5 loại phòng doanh thu cao nhất</h2>
                        <span class="badge bg-light text-muted border">Theo loại</span>
                    </div>
                    @if($topRoomTypesByRevenue->isNotEmpty())
                    <div class="row align-items-center flex-grow-1">
                        <div class="col-md-6">
                            <div class="dash-chart" style="position: relative;">
                                <canvas id="topRoomTypesRevenueChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0 dash-toplist">
                                @foreach($topRoomTypesByRevenue as $i => $r)
                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-truncate" style="max-width: 70%;">{{ $r->name }}</span>
                                    <strong class="text-success">{{ number_format($r->total_revenue, 0, ',', '.') }} ₫</strong>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @else
                    <p class="text-center text-muted py-5 mb-0">Chưa có dữ liệu doanh thu theo loại phòng.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-6">
            <div class="card card-admin h-100 dash-card">
                <div class="card-body p-3 p-md-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0">Tình trạng phòng</h2>
                        <span class="badge bg-light text-muted border">Đêm nay</span>
                    </div>
                    @php
                        $totalRoomsForBar = max(1, $totalRooms);
                        $pctAvailable = round(($roomsAvailable / $totalRoomsForBar) * 100);
                        $pctBooked = round(($roomsBooked / $totalRoomsForBar) * 100);
                        $pctMaintenance = round(($roomsMaintenance / $totalRoomsForBar) * 100);
                    @endphp
                    <div class="chart-pie pt-2 pb-2 flex-grow-1 d-flex flex-column justify-content-center">
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
            <div class="card card-admin h-100 dash-card">
                <div class="card-body p-3 p-md-4 d-flex flex-column">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h6 fw-bold mb-0">Doanh thu</h2>
                        <div class="d-flex flex-wrap align-items-end gap-2">
                            <div>
                                <div class="small text-muted fw-semibold mb-1">Từ</div>
                                <input type="date" class="form-control form-control-sm" id="revStart">
                            </div>
                            <div>
                                <div class="small text-muted fw-semibold mb-1">Đến</div>
                                <input type="date" class="form-control form-control-sm" id="revEnd">
                            </div>
                            <button type="button" class="btn btn-sm btn-primary rounded-2" id="revApply">
                                <i class="bi bi-funnel me-1"></i>Áp dụng
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2" id="revReset" title="Về 7 ngày gần nhất">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-area dash-chart">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                    <div class="small text-muted mt-2" id="revHint"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-6">
            <!-- Occupancy Overview -->
            <div class="card card-admin h-100 dash-card">
                <div class="card-body p-3 p-md-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0">Tỉ lệ lấp phòng (7 ngày)</h2>
                        <span class="badge bg-light text-muted border">%</span>
                    </div>
                    <div class="chart-area dash-chart">
                        <canvas id="occupancyRateChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@endif

<style>
    /* Dashboard polish (local) */
    .stat-card { border: 1px solid rgba(15, 23, 42, 0.06); }
    .chart-area canvas { max-height: 100%; }
    .dash-card { border: 1px solid rgba(15, 23, 42, 0.06); }
    .dash-chart { height: 240px; }
    .dash-toplist { max-height: 240px; overflow: auto; padding-right: 0.25rem; }
</style>

@if(auth()->user()->isAdmin())

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Biểu đồ tròn: Top loại phòng theo doanh thu (đơn đã thanh toán)
    @if($topRoomTypesByRevenue->isNotEmpty())
    const topRoomTypesCtx = document.getElementById('topRoomTypesRevenueChart');
    if (topRoomTypesCtx) {
        const palette = ['#4361ee', '#3b82f6', '#0ea5e9', '#06b6d4', '#14b8a6', '#8b5cf6', '#a855f7'];
        const n = {!! json_encode($topRoomTypesByRevenue->count()) !!};
        new Chart(topRoomTypesCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($topRoomTypesByRevenue->pluck('name')->values()) !!},
                datasets: [{
                    data: {!! json_encode($topRoomTypesByRevenue->pluck('total_revenue')->values()) !!},
                    backgroundColor: palette.slice(0, Math.min(n, palette.length)),
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

    // Bộ lọc thời gian CHỈ áp dụng cho biểu đồ doanh thu
    const revStart = document.getElementById('revStart');
    const revEnd = document.getElementById('revEnd');
    const revHint = document.getElementById('revHint');
    const fmtDate = (d) => d.toISOString().slice(0, 10);

    function setRevHint(start, end) {
        if (revHint) revHint.textContent = `Đang xem: ${start} → ${end}`;
    }

    async function loadRevenueChart(start, end) {
        setRevHint(start, end);
        const url = new URL(@json(route('admin.dashboard.revenue-chart')), window.location.origin);
        url.searchParams.set('start', start);
        url.searchParams.set('end', end);
        const rt = @json($roomTypeFilterId ?? null);
        if (rt) {
            url.searchParams.set('room_type_id', String(rt));
        }

        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const json = await res.json();

        revenueChart.data.labels = json.labels || [];
        revenueChart.data.datasets[0].data = json.data || [];
        revenueChart.options.scales.y.suggestedMax = json.suggestedMax || 1000000;
        revenueChart.update();
    }

    function setDefaultRevenueRange() {
        const end = new Date();
        const start = new Date();
        start.setDate(start.getDate() - 6);
        const s = fmtDate(start);
        const e = fmtDate(end);
        revStart.value = s;
        revEnd.value = e;
        loadRevenueChart(s, e).catch(() => {});
    }

    document.getElementById('revApply')?.addEventListener('click', () => {
        if (!revStart.value || !revEnd.value) return;
        loadRevenueChart(revStart.value, revEnd.value).catch(err => {
            console.error(err);
            alert('Không tải được dữ liệu doanh thu: ' + err.message);
        });
    });
    document.getElementById('revReset')?.addEventListener('click', setDefaultRevenueRange);

    // init with 7 days
    setDefaultRevenueRange();

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

@endif
@endsection