@extends('layouts.admin')

@section('title', 'Bảng điều khiển - Admin')

@section('content')
@php
    $inv = $inventoryStack ?? [];
@endphp
<div class="container-fluid px-3 px-lg-4 lh-admin-dash">
    <header class="mb-3 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <h1 class="h4 fw-bold mb-0 text-slate-900">Bảng điều khiển</h1>
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.statistics.export') }}"
               class="btn btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-2 px-3 py-2 shrink-0">
                <i class="bi bi-download" aria-hidden="true"></i> Xuất CSV
            </a>
        @endif
    </header>

    @if(auth()->user()->isAdmin())
    <section class="mb-4" aria-label="Chỉ số chính">
        <h2 class="h6 fw-semibold text-slate-700 mb-3">KPI</h2>
        <div class="row g-3 lh-kpi-strip">
        <div class="col-6 col-lg-3">
            <div class="card lh-kpi h-100 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">Doanh thu hôm nay</div>
                    <div class="lh-kpi-value">{{ number_format($todayRevenue, 0, ',', '.') }} <span class="lh-kpi-unit">₫</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card lh-kpi h-100 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">Doanh thu tháng này</div>
                    <div class="lh-kpi-value">{{ number_format($monthlyRevenue, 0, ',', '.') }} <span class="lh-kpi-unit">₫</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card lh-kpi h-100 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">RevPAR <span class="text-muted fw-normal">(MTD)</span></div>
                    <div class="lh-kpi-value">{{ number_format($dashKpis['revpar_mtd'] ?? 0, 0, ',', '.') }} <span class="lh-kpi-unit">₫</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card lh-kpi h-100 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">ADR <span class="text-muted fw-normal">(tháng)</span></div>
                    <div class="lh-kpi-value">
                        @if(($dashKpis['adr_month'] ?? null) !== null)
                            {{ number_format($dashKpis['adr_month'], 0, ',', '.') }} <span class="lh-kpi-unit">₫</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card lh-kpi h-100 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">Lấp đầy đêm nay</div>
                    <div class="lh-kpi-value">{{ $occupancyRate }}<span class="lh-kpi-unit">%</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card lh-kpi h-100 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">Lấp đầy cả tháng</div>
                    <div class="lh-kpi-value">{{ $monthlyOccupancyRate }}<span class="lh-kpi-unit">%</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card lh-kpi h-100 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">Tổng doanh thu</div>
                    <div class="lh-kpi-value">{{ number_format($totalRevenue, 0, ',', '.') }} <span class="lh-kpi-unit">₫</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card lh-kpi lh-kpi-accent h-100 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label text-primary-emphasis">Vận hành hôm nay</div>
                    <div class="d-flex justify-content-between gap-2 small">
                        <span class="text-muted">Nhận phòng</span>
                        <strong>{{ $dashKpis['arrivals_today'] ?? 0 }}</strong>
                    </div>
                    <div class="d-flex justify-content-between gap-2 small mt-1">
                        <span class="text-muted">Đang lưu trú</span>
                        <strong>{{ $dashKpis['stays_in_house'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </section>

    {{-- Xu hướng --}}
    <section class="mb-4" aria-label="Biểu đồ xu hướng">
        <h2 class="h6 fw-semibold text-slate-700 mb-3">Biểu đồ</h2>
        <div class="row g-3 g-md-4 lh-dash-chart-row">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 rounded-top-3">
                    <h3 class="h6 fw-bold mb-0">Doanh thu (paid)</h3>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="lh-dash-chart-toolbar rounded-3 px-3 py-2 mb-3">
                        <div class="d-flex flex-wrap align-items-end gap-2 gap-md-3">
                            <div class="flex-grow-1 flex-md-grow-0 lh-rt-detail-date">
                                <label class="form-label lh-dash-toolbar-label text-muted mb-1" for="revStart">Từ</label>
                                <input type="date" class="form-control form-control-sm rounded-3 lh-rt-detail-date-input" id="revStart">
                            </div>
                            <div class="flex-grow-1 flex-md-grow-0 lh-rt-detail-date">
                                <label class="form-label lh-dash-toolbar-label text-muted mb-1" for="revEnd">Đến</label>
                                <input type="date" class="form-control form-control-sm rounded-3 lh-rt-detail-date-input" id="revEnd">
                            </div>
                            <div class="d-flex flex-column justify-content-end">
                                <span class="form-label lh-dash-toolbar-label mb-1 lh-rt-detail-label-spacer" aria-hidden="true">&nbsp;</span>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-primary rounded-3 px-3 lh-rt-detail-btn" id="revApply">Áp dụng</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 lh-rt-detail-btn" id="revReset" title="7 ngày gần nhất" aria-label="Đặt lại 7 ngày"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="position-relative lh-chart-tall">
                        <canvas id="monthlyRevenueChart" aria-label="Biểu đồ doanh thu"></canvas>
                    </div>
                    <p class="small text-muted mt-2 mb-0" id="revHint"></p>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 lh-chart-card-accent">
                <div class="card-header bg-transparent border-0 pt-4 px-3 px-md-4 pb-0">
                    <h3 class="h6 fw-bold mb-0">Lấp đầy 7 ngày</h3>
                </div>
                <div class="card-body p-3 pt-2 p-md-4 d-flex flex-column flex-grow-1">
                    <div class="position-relative lh-chart-side flex-grow-1">
                        <canvas id="occupancyRateChart" aria-label="Biểu đồ lấp phòng"></canvas>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </section>

    <section class="mb-4" aria-label="Doanh thu theo loại phòng">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 fw-semibold text-slate-700 mb-0">Top loại phòng</h2>
            <div class="small">
                <a href="{{ route('admin.roomtypes.index') }}" class="link-secondary">Loại phòng</a>
                <span class="text-muted px-1">·</span>
                <a href="{{ route('admin.rooms.index') }}" class="link-secondary">Phòng</a>
            </div>
        </div>
        @include('admin.partials.dashboard-room-revenue-ranking')
    </section>

    <section class="mb-4" aria-label="Chi tiết loại phòng">
        <h2 class="h6 fw-semibold text-slate-700 mb-3">Chi tiết loại phòng</h2>
        @include('admin.partials.dashboard-room-type-detail')
    </section>

    {{-- Tồn phòng đêm nay (toàn KS) --}}
    <section class="mb-5" aria-label="Tồn phòng">
        <h2 class="h6 fw-semibold text-slate-700 mb-3">Tồn đêm nay</h2>
        <div class="row g-3 g-md-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                        <div>
                            <p class="small text-muted mb-0">{{ $inv['total'] ?? $totalRooms }} phòng · trống / có lịch / bảo trì</p>
                        </div>
                        <span class="badge rounded-pill text-bg-light text-dark border align-self-start">Tổng {{ $inv['total'] ?? $totalRooms }}</span>
                    </div>
                    @if(($inv['total'] ?? 0) > 0)
                        <div class="progress lh-stack-progress rounded-pill mb-3" role="progressbar" aria-label="Tỉ lệ tồn phòng">
                            <div class="progress-bar bg-success" style="width: {{ $inv['pct_available'] ?? 0 }}%"
                                 title="Trống: {{ $inv['rooms_available'] ?? $roomsAvailable }}"></div>
                            <div class="progress-bar bg-warning text-dark" style="width: {{ $inv['pct_booked'] ?? 0 }}%"
                                 title="Có lịch đêm nay: {{ $inv['rooms_booked'] ?? $roomsBooked }}"></div>
                            <div class="progress-bar bg-secondary" style="width: {{ $inv['pct_maintenance'] ?? 0 }}%"
                                 title="Bảo trì: {{ $inv['rooms_maintenance'] ?? $roomsMaintenance }}"></div>
                        </div>
                        <div class="row row-cols-1 row-cols-md-3 g-2 small">
                            <div class="col d-flex justify-content-between align-items-center border rounded-3 px-3 py-2">
                                <span><i class="bi bi-circle-fill text-success me-1"></i> Trống</span>
                                <strong>{{ $inv['rooms_available'] ?? $roomsAvailable }}</strong>
                                <span class="text-muted">({{ $inv['pct_available'] ?? 0 }}%)</span>
                            </div>
                            <div class="col d-flex justify-content-between align-items-center border rounded-3 px-3 py-2">
                                <span><i class="bi bi-circle-fill text-warning me-1"></i> Có lịch đêm nay</span>
                                <strong>{{ $inv['rooms_booked'] ?? $roomsBooked }}</strong>
                                <span class="text-muted">({{ $inv['pct_booked'] ?? 0 }}%)</span>
                            </div>
                            <div class="col d-flex justify-content-between align-items-center border rounded-3 px-3 py-2">
                                <span><i class="bi bi-circle-fill text-secondary me-1"></i> Bảo trì</span>
                                <strong>{{ $inv['rooms_maintenance'] ?? $roomsMaintenance }}</strong>
                                <span class="text-muted">({{ $inv['pct_maintenance'] ?? 0 }}%)</span>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Chưa khai báo phòng.</p>
                    @endif
                </div>
            </div>
        </div>
        </div>
    </section>

    @else
    {{-- Staff: simplified --}}
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">Phòng trống</div>
                    <div class="h4 fw-bold text-success mb-0">{{ $roomsAvailable }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">Có lịch đêm nay</div>
                    <div class="h4 fw-bold text-warning mb-0">{{ $roomsBooked }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <div class="lh-kpi-label">Bảo trì</div>
                    <div class="h4 fw-bold text-secondary mb-0">{{ $roomsMaintenance }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.lh-admin-dash { --lh-slate: #0f172a; }
.lh-kpi { background: linear-gradient(180deg, #fff 0%, #f8fafc 100%); border: 1px solid rgba(15, 23, 42, 0.06) !important; }
.lh-kpi-accent { background: #eff6ff; border-color: rgba(37, 99, 235, 0.15) !important; }
.lh-kpi-label { font-size: 0.7rem; letter-spacing: .04em; text-transform: uppercase; color: #64748b; font-weight: 600; }
.lh-kpi-value { font-size: 1.35rem; font-weight: 700; color: #0f172a; line-height: 1.2; margin-top: .35rem; }
.lh-kpi-unit { font-size: .85rem; font-weight: 600; color: #64748b; }
.lh-chart-tall { height: 300px; }
.lh-chart-mid { height: 260px; min-height: 220px; }
.lh-dash-select { min-width: 200px; }
.lh-dash-control {
    border: 1px solid rgba(15, 23, 42, 0.07) !important;
    background: linear-gradient(180deg, #fafbfc 0%, #fff 40%);
}
.lh-dash-label {
    font-size: 0.72rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #64748b;
    font-weight: 600;
}
.lh-dash-context { color: #475569; }
.lh-dash-badge-scope { font-weight: 600; padding: 0.4em 0.85em; vertical-align: middle; }
.lh-dash-chart-toolbar {
    background: #f1f5f9;
    border: 1px solid rgba(15, 23, 42, 0.06);
}
.lh-dash-toolbar-label { letter-spacing: .06em; }
.lh-stack-progress { height: 1.25rem; background: #e2e8f0; }
.lh-top-list { max-height: 140px; overflow: auto; }
.lh-fs-8 { font-size: 0.7rem; }
.lh-metric-tile { background: linear-gradient(180deg, #fafbfc 0%, #fff 100%); }
.lh-metric-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 600; }
.lh-metric-value { font-size: 1.1rem; font-weight: 700; color: #0f172a; line-height: 1.25; margin-top: .35rem; }
.lh-metric-foot { font-size: 0.65rem; color: #94a3b8; margin-top: .35rem; line-height: 1.3; }
.lh-chart-side { min-height: 200px; }
.lh-chart-donut { height: 220px; }
.lh-dash-deep-dive { border: 1px solid rgba(37, 99, 235, 0.1) !important; background: linear-gradient(145deg, #fff 0%, #f8fafc 55%, #fff 100%); }
.lh-dash-deep-dive-revenue { border: 1px solid rgba(37, 99, 235, 0.12) !important; }
.lh-dash-deep-dive-inventory { border: 1px solid rgba(13, 148, 136, 0.14) !important; background: linear-gradient(165deg, #f8fafc 0%, #fff 50%); }
.lh-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
.lh-inv-summary-table { max-width: 28rem; }
.lh-chart-card-accent { border: 1px solid rgba(13, 148, 136, 0.12) !important; background: linear-gradient(180deg, #f0fdfa 0%, #fff 72%); }
.lh-chart-pie-wrap { height: min(320px, 70vw); max-width: 420px; }

/* Thanh filter dashboard: căn đáy một hàng, label đồng cao, tránh nút “trôi” lên */
.lh-rt-detail-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.5rem 0.75rem;
}
@media (min-width: 768px) {
    .lh-rt-detail-toolbar { gap: 0.5rem 1rem; }
}
.lh-rt-detail-toolbar .lh-rt-detail-room {
    flex: 1 1 12rem;
    min-width: min(100%, 12rem);
    max-width: 22rem;
}
.lh-rt-detail-toolbar .lh-rt-detail-date {
    flex: 0 1 auto;
}
.lh-rt-detail-toolbar .lh-rt-detail-actions {
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}
.lh-rt-detail-label-spacer {
    visibility: hidden;
    white-space: nowrap;
    line-height: 1.2;
    min-height: 1.25rem;
    padding-top: 0.125rem;
    margin-bottom: 0.25rem !important;
}
.lh-rt-detail-date-input {
    width: 100%;
    min-width: 10.25rem;
    max-width: 11.5rem;
}
.lh-rt-detail-btn {
    min-height: calc(1.5em + 0.375rem + 2px);
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}
</style>

@if(auth()->user()->isAdmin())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    (function() {
        const panel = document.getElementById('lhRoomTypePiePanel');
        const canvas = document.getElementById('roomTypeRevenuePieChart');
        if (!panel || !canvas) return;

        const rankingUrl = panel.dataset.rankingUrl;
        const inpStart = panel.querySelector('.lh-pie-start');
        const inpEnd = panel.querySelector('.lh-pie-end');
        const hintEl = panel.querySelector('.lh-pie-range-hint');
        const emptyEl = panel.querySelector('.lh-pie-empty');
        if (!rankingUrl) return;

        const fmtMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + ' ₫';
        const fmtDate = (d) => d.toISOString().slice(0, 10);
        const PIE_COLORS = ['#2563eb', '#0d9488', '#d97706', '#7c3aed', '#db2777'];

        let pieChart = null;

        function buildUrl() {
            const endVal = (inpEnd && inpEnd.value) ? inpEnd.value : fmtDate(new Date());
            const url = new URL(rankingUrl, window.location.origin);
            url.searchParams.set('end', endVal);
            if (inpStart && inpStart.value) {
                url.searchParams.set('start', inpStart.value);
            }
            return url.toString();
        }

        function setHint(json) {
            if (!hintEl) return;
            hintEl.textContent = json.all_time
                ? ('→ ' + json.end + ' (từ đầu)')
                : (json.start + ' → ' + json.end);
        }

        function defaultAllTimeToToday() {
            if (inpStart) inpStart.value = '';
            if (inpEnd) inpEnd.value = fmtDate(new Date());
        }

        async function loadPie() {
            const res = await fetch(buildUrl(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            const labels = json.labels || [];
            const data = json.data || [];
            const sum = data.reduce((a, b) => a + Number(b), 0);

            setHint(json);

            if (pieChart) {
                pieChart.destroy();
                pieChart = null;
            }

            const showEmpty = labels.length === 0 || sum <= 0;
            if (emptyEl) {
                emptyEl.classList.toggle('d-none', !showEmpty);
            }
            canvas.style.display = showEmpty ? 'none' : 'block';

            if (showEmpty) {
                return;
            }

            pieChart = new Chart(canvas.getContext('2d'), {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data,
                        backgroundColor: labels.map((_, i) => PIE_COLORS[i % PIE_COLORS.length]),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 14, font: { size: 11 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    const v = Number(ctx.raw) || 0;
                                    const pct = sum > 0 ? ((v / sum) * 100).toFixed(1) : '0';
                                    return ' ' + fmtMoney(v) + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        panel.querySelector('.lh-pie-apply')?.addEventListener('click', () => {
            loadPie().catch((err) => {
                console.error(err);
                alert('Không tải được dữ liệu: ' + err.message);
            });
        });
        panel.querySelector('.lh-pie-reset')?.addEventListener('click', () => {
            defaultAllTimeToToday();
            loadPie().catch(() => {});
        });

        defaultAllTimeToToday();
        loadPie().catch(() => {});
    })();

    (function() {
        const panel = document.getElementById('lhRoomTypeDetailPanel');
        const canvas = document.getElementById('roomTypeDetailRevenueChart');
        const sel = document.getElementById('lhRtDetailSelect');
        const rtStart = document.getElementById('lhRtDetailStart');
        const rtEnd = document.getElementById('lhRtDetailEnd');
        const metricsEl = document.getElementById('lhRtDetailMetrics');
        const hintEl = document.getElementById('lhRtDetailHint');
        const placeholder = document.getElementById('lhRtDetailPlaceholder');
        const wrap = document.getElementById('lhRtDetailChartWrap');
        const meta = document.getElementById('lhRtDetailMeta');
        const editLink = document.getElementById('lhRtDetailEdit');
        if (!panel || !canvas || !sel || !metricsEl) return;

        const detailUrl = panel.dataset.detailUrl;
        if (!detailUrl) return;

        const fmtMoney = (n) => new Intl.NumberFormat('vi-VN').format(Math.round(Number(n) || 0)) + ' ₫';
        const fmtPct = (x) => (Number(x) || 0).toFixed(1) + '%';
        let rtChart = null;

        function defaultRange() {
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - 6);
            const iso = (d) => d.toISOString().slice(0, 10);
            if (rtStart) rtStart.value = iso(start);
            if (rtEnd) rtEnd.value = iso(end);
        }

        function buildMeta(rt) {
            const parts = [];
            parts.push(`${rt.rooms_sellable} KD / ${rt.rooms_total} phòng (${rt.rooms_maintenance} bảo trì)`);
            if (rt.beds) parts.push(String(rt.beds) + ' giường');
            if (rt.baths) parts.push(String(rt.baths) + ' WC');
            const cap = [];
            if (rt.standard_capacity) cap.push('chuẩn ' + rt.standard_capacity);
            if (rt.capacity) cap.push('max ' + rt.capacity);
            if (rt.adult_capacity || rt.child_capacity) {
                cap.push((rt.adult_capacity || 0) + ' NL' + (rt.child_capacity ? '/' + rt.child_capacity + ' trẻ' : ''));
            }
            if (cap.length) parts.push(cap.join(', '));
            return parts.join(' · ');
        }

        function renderMetrics(rt, kp) {
            const adr = kp.adr_mtd != null ? fmtMoney(kp.adr_mtd) : '—';
            metricsEl.innerHTML = `
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="border rounded-3 p-2 lh-metric-tile h-100">
                        <div class="lh-metric-label">Giá niêm yết</div>
                        <div class="lh-metric-value">${fmtMoney(rt.price)}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="border rounded-3 p-2 lh-metric-tile h-100">
                        <div class="lh-metric-label">Lấp đầy hôm nay</div>
                        <div class="lh-metric-value">${fmtPct(kp.occupancy_today_pct)}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="border rounded-3 p-2 lh-metric-tile h-100">
                        <div class="lh-metric-label">Lấp đầy tháng</div>
                        <div class="lh-metric-value">${fmtPct(kp.occupancy_month_pct)}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="border rounded-3 p-2 lh-metric-tile h-100">
                        <div class="lh-metric-label">Doanh thu hôm nay</div>
                        <div class="lh-metric-value">${fmtMoney(kp.revenue_today)}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="border rounded-3 p-2 lh-metric-tile h-100">
                        <div class="lh-metric-label">Doanh thu tháng (MTD)</div>
                        <div class="lh-metric-value">${fmtMoney(kp.revenue_mtd)}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="border rounded-3 p-2 lh-metric-tile h-100">
                        <div class="lh-metric-label">ADR tháng</div>
                        <div class="lh-metric-value">${adr}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="border rounded-3 p-2 lh-metric-tile h-100">
                        <div class="lh-metric-label">Đêm bán (paid, MTD)</div>
                        <div class="lh-metric-value">${Number(kp.room_nights_paid_mtd || 0).toLocaleString('vi-VN')}</div>
                    </div>
                </div>`;
        }

        async function loadRoomTypeDetail() {
            const id = sel.value;
            if (!id) {
                if (placeholder) placeholder.classList.remove('d-none');
                if (wrap) wrap.classList.add('d-none');
                if (meta) { meta.classList.add('d-none'); meta.textContent = ''; }
                metricsEl.innerHTML = '';
                if (hintEl) hintEl.textContent = '';
                if (editLink) { editLink.classList.add('d-none'); editLink.href = '#'; }
                if (rtChart) { rtChart.destroy(); rtChart = null; }
                return;
            }

            if (!rtStart?.value || !rtEnd?.value) defaultRange();

            const url = new URL(detailUrl, window.location.origin);
            url.searchParams.set('room_type_id', id);
            url.searchParams.set('start', rtStart.value);
            url.searchParams.set('end', rtEnd.value);

            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();

            const rt = json.room_type;
            const kp = json.kpis || {};
            const rc = json.revenue_chart || {};

            if (placeholder) placeholder.classList.add('d-none');
            if (wrap) wrap.classList.remove('d-none');
            if (meta) {
                meta.textContent = rt.name + ' — ' + buildMeta(rt);
                meta.classList.remove('d-none');
            }
            renderMetrics(rt, kp);
            if (hintEl && json.range) {
                hintEl.textContent = 'Biểu đồ paid: ' + json.range.start + ' → ' + json.range.end;
            }
            if (editLink && rt.edit_url) {
                editLink.href = rt.edit_url;
                editLink.classList.remove('d-none');
            }

            if (rtChart) {
                rtChart.destroy();
                rtChart = null;
            }

            const labels = rc.labels || [];
            const data = rc.data || [];
            const suggestedMax = rc.suggestedMax || 1000000;

            rtChart = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Doanh thu',
                        data,
                        borderColor: '#0d9488',
                        backgroundColor: 'rgba(13, 148, 136, 0.08)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax,
                            ticks: {
                                callback: (v) => new Intl.NumberFormat('vi-VN').format(v)
                            }
                        }
                    }
                }
            });
        }

        sel.addEventListener('change', () => {
            loadRoomTypeDetail().catch((err) => {
                console.error(err);
                alert('Không tải được chi tiết loại phòng: ' + err.message);
            });
        });
        document.getElementById('lhRtDetailApply')?.addEventListener('click', () => {
            if (!sel.value) return;
            loadRoomTypeDetail().catch((err) => {
                console.error(err);
                alert('Không tải được chi tiết loại phòng: ' + err.message);
            });
        });

        defaultRange();
        const firstOpt = sel.querySelector('option[value]:not([value=""])');
        if (firstOpt) {
            sel.value = firstOpt.value;
            loadRoomTypeDetail().catch(() => {});
        }
    })();

    const revCanvas = document.getElementById('monthlyRevenueChart');
    if (revCanvas) {
        const revenueChart = new Chart(revCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($revenueChart['labels']),
                datasets: [{
                    label: 'Doanh thu',
                    data: @json($revenueChart['data']),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5
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
                            callback: (v) => new Intl.NumberFormat('vi-VN').format(v)
                        }
                    }
                }
            }
        });

        const revStart = document.getElementById('revStart');
        const revEnd = document.getElementById('revEnd');
        const revHint = document.getElementById('revHint');
        const fmtDate = (d) => d.toISOString().slice(0, 10);

        function setRevHint(start, end) {
            if (revHint) revHint.textContent = start + ' → ' + end;
        }

        async function loadRevenueChart(start, end) {
            setRevHint(start, end);
            const url = new URL(@json(route('admin.dashboard.revenue-chart')), window.location.origin);
            url.searchParams.set('start', start);
            url.searchParams.set('end', end);
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
            if (revStart) revStart.value = s;
            if (revEnd) revEnd.value = e;
            loadRevenueChart(s, e).catch(() => {});
        }

        document.getElementById('revApply')?.addEventListener('click', () => {
            if (!revStart?.value || !revEnd?.value) return;
            loadRevenueChart(revStart.value, revEnd.value).catch((err) => {
                console.error(err);
                alert('Không tải được dữ liệu doanh thu: ' + err.message);
            });
        });
        document.getElementById('revReset')?.addEventListener('click', setDefaultRevenueRange);
        setDefaultRevenueRange();
    }

    const occCanvas = document.getElementById('occupancyRateChart');
    if (occCanvas) {
        new Chart(occCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($occupancyChart['labels'] ?? []),
                datasets: [{
                    label: 'Lấp đầy (%)',
                    data: @json($occupancyChart['data'] ?? []),
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13, 148, 136, 0.12)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: (v) => v + '%' }
                    }
                }
            }
        });
    }
});
</script>
@endif
@endsection
