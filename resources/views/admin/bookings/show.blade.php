@extends('layouts.admin')

@section('title', 'Chi tiết đặt phòng')
@push('styles')
<style>
    .abs-booking-detail {
        --abs-surface: #ffffff;
        --abs-surface-muted: #f8fafc;
        --abs-border: #e2e8f0;
        --abs-text: #0f172a;
        --abs-muted: #64748b;
        --abs-accent: #0f766e;
        --abs-accent-2: #0ea5e9;
        --abs-ring: rgba(15, 118, 110, 0.2);
        --abs-money-plus: #047857;
        --abs-money-minus: #b91c1c;
        --abs-money-warn: #b45309;
        --abs-radius: 12px;
        --abs-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 8px 24px rgba(15, 23, 42, 0.04);
        color: var(--abs-text);
    }
    .abs-booking-detail .abs-panel {
        background: var(--abs-surface);
        border: 1px solid var(--abs-border);
        border-radius: var(--abs-radius);
        box-shadow: var(--abs-shadow);
    }
    .abs-booking-detail .abs-panel-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--abs-border);
        background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
        border-radius: var(--abs-radius) var(--abs-radius) 0 0;
    }
    .abs-booking-detail .abs-panel-body {
        padding: 1.25rem;
    }
    .abs-booking-detail .abs-eyebrow {
        font-size: 0.6875rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-weight: 700;
        color: var(--abs-muted);
        margin-bottom: 0.35rem;
    }
    .abs-booking-detail .abs-page-title {
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--abs-text);
    }
    .abs-booking-detail .abs-jump {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        padding: 0.25rem 0 0.25rem;
    }
    .abs-booking-detail .abs-jump a {
        font-size: 0.8125rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        background: var(--abs-surface-muted);
        border: 1px solid var(--abs-border);
        color: var(--abs-muted);
        text-decoration: none;
        font-weight: 600;
        transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .abs-booking-detail .abs-jump a:hover {
        color: var(--abs-accent);
        border-color: var(--abs-ring);
        background: rgba(15, 118, 110, 0.06);
    }
    .abs-booking-detail .abs-sticky-rail {
        position: sticky;
        top: calc(var(--navbar-height, 56px) + 1rem);
    }
    @media (max-width: 1199.98px) {
        .abs-booking-detail .abs-sticky-rail { position: static; }
    }
    .abs-booking-detail .abs-kpi-strip {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 0.65rem;
    }
    .abs-booking-detail .abs-kpi {
        border: 1px solid var(--abs-border);
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
        background: var(--abs-surface-muted);
    }
    .abs-booking-detail .abs-kpi .abs-kpi-label {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--abs-muted);
        font-weight: 600;
        margin-bottom: 0.2rem;
    }
    .abs-booking-detail .abs-kpi .abs-kpi-value {
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.25;
    }
    .abs-booking-detail .abs-kpi-due .abs-kpi-value { color: var(--abs-money-minus); }
    .abs-booking-detail .abs-kpi-paid .abs-kpi-value { color: var(--abs-money-plus); }
    .abs-booking-detail .abs-money-line {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.45rem 0;
        border-bottom: 1px dashed rgba(148, 163, 184, 0.45);
        font-size: 0.9rem;
    }
    .abs-booking-detail .abs-money-line:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .abs-booking-detail .abs-guest-card {
        background: linear-gradient(135deg, #f0fdfa 0%, #fff 48%);
        border: 1px solid rgba(15, 118, 110, 0.18);
        border-radius: 10px;
        padding: 1rem 1.1rem;
    }
    .abs-booking-detail .abs-guest-card .abs-guest-name {
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 0.25rem;
    }
    .abs-booking-detail .abs-field-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: var(--abs-muted);
        margin-bottom: 0.35rem;
    }
    .abs-booking-detail .booking-room-card {
        border-radius: 10px;
        border: 1px solid rgba(15, 23, 42, 0.06);
        background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
        border-left: 4px solid var(--abs-accent);
    }
    .abs-booking-detail .booking-guest-chip {
        border-radius: 8px;
        background: var(--abs-surface);
        border: 1px solid var(--abs-border);
        padding: 0.5rem 0.65rem;
        font-size: 0.8125rem;
    }
    .abs-booking-detail .booking-rooms-thead th {
        font-size: 0.68rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--abs-muted);
        font-weight: 700;
        border-bottom-width: 2px !important;
        white-space: nowrap;
        background: #f8fafc !important;
    }
    .abs-booking-detail .abs-fees-zone {
        border: 1px dashed rgba(14, 165, 233, 0.35);
        border-radius: 10px;
        padding: 1rem;
        background: rgba(14, 165, 233, 0.04);
    }
    .abs-booking-detail .abs-fees-form-zone {
        border: none;
        border-top: 1px solid var(--abs-border);
        border-radius: 0;
        padding: 1rem 0 0;
        margin-top: 1rem;
        background: transparent;
    }
    .abs-booking-detail .abs-compact-room {
        font-size: 0.8125rem;
        padding: 0.55rem 0;
        border-bottom: 1px solid var(--abs-border);
    }
    .abs-booking-detail .abs-compact-room:last-child { border-bottom: none; padding-bottom: 0; }
    .abs-booking-detail .scroll-target { scroll-margin-top: 88px; }
    .abs-booking-detail .abs-pay-ledger { font-size: 0.78rem; }
    .abs-booking-detail .abs-pay-ledger li:last-child { border-bottom: none !important; }
</style>
@endpush

@section('content')
@php
    $statusColors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger',
    ];
    $statusLabels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];
    $roomTotal = $booking->bookingRooms->sum('subtotal');
    $serviceTotal = $booking->bookingServices->sum(fn($bs) => (float) $bs->price * (int) $bs->quantity);
    $surchargeTotal = (float) $booking->surcharges->sum(fn ($s) => (float) $s->amount);
    $discountAmount = $booking->discount_amount ?? 0;
    $invoiceSubtotal = max(0, $roomTotal + $serviceTotal + $surchargeTotal - $discountAmount);
    $storedBookingTotal = (float) ($booking->total_price ?? 0);
    $totalDrift = round($storedBookingTotal - $invoiceSubtotal, 2);
    // Đã thu: chỉ giao dịch status=paid (tiền thực vào quỹ). Không gộp pending/failed.
    $paidCollected = (float) $booking->payments
        ->where('status', 'paid')
        ->sum(fn ($p) => (float) $p->amount);
    $pendingLedgerSum = (float) $booking->payments->where('status', 'pending')->sum(fn ($p) => (float) $p->amount);
    $failedLedgerSum = (float) $booking->payments->where('status', 'failed')->sum(fn ($p) => (float) $p->amount);
    $amountDue = max(0, round($invoiceSubtotal - $paidCollected, 2));
    $overpaid = max(0, round($paidCollected - $invoiceSubtotal, 2));
@endphp
<div class="abs-booking-detail pb-5">
    <div class="container-fluid px-3 px-lg-4">
        <header class="pt-3 pb-3 mb-2 border-bottom" style="border-color: var(--abs-border, #e2e8f0) !important;">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill mb-3 d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span class="d-none d-sm-inline">Danh sách đặt phòng</span>
                <span class="d-inline d-sm-none">Quay lại</span>
            </a>
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="abs-page-title mb-1">Đơn #{{ $booking->id }}</h1>
                    <p class="small text-muted mb-0">{{ $booking->created_at?->format('d/m/Y H:i') ?? '—' }} · {{ $booking->bookingRooms->count() }} phòng</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge rounded-pill px-3 py-2 fs-6 fw-semibold bg-{{ $statusColors[$booking->status] ?? 'secondary' }}">
                        {{ $statusLabels[$booking->status] ?? '—' }}
                    </span>
                    @if($booking->isPaidAndCheckedOutForInvoice())
                        <a href="{{ route('admin.bookings.invoice', $booking) }}" class="btn btn-sm btn-dark rounded-pill no-print" target="_blank" rel="noopener">
                            <i class="bi bi-receipt-cutoff me-1"></i>Biên lai
                        </a>
                    @endif
                </div>
            </div>
            <nav class="abs-jump mt-3 d-none d-md-flex" aria-label="Điều hướng nhanh">
                <a href="#abs-overview">Tổng quan</a>
                <a href="#abs-rooms">Phòng &amp; khách</a>
                <a href="#abs-fees">Thu phí &amp; dịch vụ</a>
                <a href="#abs-activity">Hoạt động</a>
                <a href="#abs-billing">Số tiền</a>
                <a href="#abs-system">HT</a>
            </nav>
        </header>

        <div class="row g-4">
            <!-- Cột chính -->
            <div class="col-xl-8 order-2 order-xl-1">

                @php
                    $canExtendStay = ! in_array($booking->status, ['cancelled', 'completed', 'cancel_requested'], true)
                        && $booking->actual_check_out === null
                        && $booking->bookingRooms->isNotEmpty();
                    $minExtendCheckOut = $booking->check_out ? $booking->check_out->copy()->addDay()->format('Y-m-d') : null;
                @endphp
                <div id="abs-overview" class="abs-panel mb-4 scroll-target">
                    <div class="abs-panel-header">
                        <h2 class="h6 fw-bold mb-0 text-dark">Tổng quan</h2>
                    </div>
                    <div class="abs-panel-body">
                        <div class="row g-4 align-items-start">
                                <div class="col-lg-6">
                                    <p class="abs-eyebrow mb-2">Khách hàng</p>
                                    <div class="abs-guest-card">
                                        <div class="abs-guest-name">{{ $booking->user?->full_name ?? '—' }}</div>
                                        <div class="small mb-1 text-break">{{ $booking->user?->email ?? '—' }}</div>
                                        <div class="small text-muted">{{ $booking->user?->phone ?? '—' }}</div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <p class="abs-eyebrow mb-2">Lưu trú hiện tại</p>
                                    <div class="row g-2 small mb-2">
                                        <div class="col-6">
                                            <span class="text-muted d-block">Nhận phòng (trên đơn)</span>
                                            <span class="fw-semibold text-dark">{{ $booking->check_in?->format('d/m/Y') ?? '—' }}</span>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted d-block">Trả phòng (dự kiến)</span>
                                            <span class="fw-semibold text-dark">{{ $booking->check_out?->format('d/m/Y') ?? '—' }}</span>
                                        </div>
                                        @if($booking->actual_check_in)
                                            <div class="col-12">
                                                <span class="text-muted d-block">Check-in thực tế</span>
                                                <span class="fw-semibold text-dark">{{ $booking->actual_check_in->format('d/m/Y H:i') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <p class="small text-muted mb-3">
                                        Tạm tính: <strong class="text-dark">{{ number_format($invoiceSubtotal, 0, ',', '.') }} ₫</strong>
                                    </p>

                                    @if($errors->has('extend'))
                                        <div class="alert alert-danger py-2 small mb-3">{{ $errors->first('extend') }}</div>
                                    @endif

                                    @if($canExtendStay && $minExtendCheckOut)
                                        <div class="border rounded-3 p-3" style="background: rgba(15,118,110,0.06); border-color: rgba(15,118,110,0.2) !important;">
                                            <p class="abs-eyebrow mb-2">Gia hạn thêm đêm</p>
                                            <p class="small text-muted mb-2">
                                                Ngày trả &gt; {{ $booking->check_out?->format('d/m/Y') }}. Tính theo đêm &amp; phụ phí — ghi vào đơn.
                                            </p>
                                            <p class="small text-warning mb-3 mb-md-2">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Chưa gán phòng: chưa khóa lịch đêm thêm.
                                            </p>
                                            <form action="{{ route('admin.bookings.extend-stay', $booking) }}" method="POST" id="extendStayForm">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="extend_new_check_out" class="abs-field-label">Ngày trả phòng sau khi gia hạn</label>
                                                    <input type="date" name="new_check_out" id="extend_new_check_out" class="form-control form-control-sm rounded-3 border-secondary-subtle" min="{{ $minExtendCheckOut }}" required value="{{ old('new_check_out') }}">
                                                </div>
                                                <div id="extend-quote-box" class="small mb-3 d-none border rounded-2 p-2 bg-white"></div>
                                                <button type="submit" class="btn btn-sm rounded-pill px-3" style="background: var(--abs-accent); border-color: var(--abs-accent); color: #fff;">
                                                    <i class="bi bi-calendar-plus me-1"></i>Xác nhận gia hạn
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Không gia hạn được.</p>
                                    @endif

                                    @if($booking->discount_amount > 0)
                                        <p class="small text-danger mb-0 mt-3">
                                            <i class="bi bi-tag-fill me-1"></i>Đã giảm {{ number_format($booking->discount_amount, 0, ',', '.') }} ₫ (đã trừ trong tổng dự kiến).
                                        </p>
                                    @endif
                                </div>
                            </div>
                    </div>
                </div>

                <div id="abs-rooms" class="abs-panel mb-4 scroll-target">
                    <div class="abs-panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h2 class="h6 fw-bold mb-0 text-dark">Phòng trong đơn</h2>
                        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border fw-semibold">{{ $booking->bookingRooms->count() }} phòng</span>
                    </div>
                    <div class="abs-panel-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-top">
                                <thead class="booking-rooms-thead">
                                    <tr>
                                        <th class="ps-3" style="min-width: 150px;">Phòng</th>
                                        <th class="text-center" style="width: 6.5rem;">Đặt chỗ</th>
                                        <th style="min-width: 220px;">Khách lưu trú</th>
                                        <th class="text-end" style="min-width: 6rem;">Giá / đêm</th>
                                        <th class="text-end pe-3" style="min-width: 6.5rem;">Thành tiền</th>
                                        @if(auth()->user() && auth()->user()->role === 'admin' && $booking->status !== 'cancelled' && $booking->status !== 'completed')
                                            <th class="text-center border-start" style="width: 4rem;">Đổi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($booking->bookingRooms as $br)
                                        @php
                                            $persons = $booking->guestLineItemsForBookingRoom($br);
                                            $occ = $booking->occupancyDisplayCountsForBookingRoom($br);
                                            $rn = $br->room?->room_number;
                                            $rtName = $br->room?->roomType?->name;
                                            $childParts = [];
                                            if ($occ['children_6_11'] > 0) {
                                                $childParts[] = $occ['children_6_11'].' trẻ 6–11t';
                                            }
                                            if ($occ['children_0_5'] > 0) {
                                                $childParts[] = $occ['children_0_5'].' trẻ 0–5t';
                                            }
                                        @endphp
                                        <tr class="border-bottom">
                                            <td class="ps-3 py-3">
                                                <div class="booking-room-card px-3 py-2">
                                                    @if($br->room)
                                                        <div class="fw-bold text-dark lh-sm">{{ $rn ?: '—' }}</div>
                                                        @if($rtName)
                                                            <span class="badge mt-1 text-dark border fw-normal" style="background: rgba(15,118,110,0.12); border-color: rgba(15,118,110,0.25) !important;">{{ $rtName }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-warning fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Chưa gán phòng</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center py-3">
                                                <div class="d-inline-flex flex-column align-items-center gap-1 small">
                                                    <span class="rounded-pill bg-light border px-2 py-1 text-nowrap">
                                                        <i class="bi bi-person-fill text-secondary"></i>
                                                        <strong class="text-dark">{{ (int) $occ['adults'] }}</strong>
                                                        <span class="text-muted">NL</span>
                                                    </span>
                                                    @if(count($childParts))
                                                        <span class="rounded-pill bg-light border px-2 py-1 text-muted text-wrap text-start" style="max-width: 10rem;">
                                                            <i class="bi bi-person-hearts"></i>
                                                            {{ implode(' · ', $childParts) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">Không kèm trẻ</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                @if($persons->isNotEmpty())
                                                    <div class="d-flex flex-column gap-2">
                                                        @foreach($persons as $p)
                                                            @php
                                                                $st = $p->status ?? $p->checkin_status ?? null;
                                                                $rep = (bool) ($p->is_representative ?? false);
                                                            @endphp
                                                            <div class="booking-guest-chip mb-0">
                                                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                                                    <div class="min-w-0 flex-grow-1">
                                                                        <span class="fw-semibold text-dark d-block text-break">{{ $p->name }}</span>
                                                                        <span class="text-muted" style="font-size: 0.75rem;">{{ \App\Models\BookingGuest::typeLabel($p->type ?? 'adult') }}</span>
                                                                    </div>
                                                                    <div class="d-flex flex-wrap gap-1 justify-content-end flex-shrink-0">
                                                                        @if($rep)
                                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">Đại diện</span>
                                                                        @endif
                                                                        @if($st === 'checked_in')
                                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Đã check-in</span>
                                                                        @elseif($st === 'pending' || $st === null)
                                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">Chờ check-in</span>
                                                                        @else
                                                                            <span class="badge bg-light text-dark border">{{ $st }}</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                @if($p->cccd)
                                                                    <div class="mt-2 pt-2 border-top border-light d-flex align-items-center gap-1 text-muted" style="font-size: 0.75rem;">
                                                                        <i class="bi bi-person-vcard flex-shrink-0"></i>
                                                                        <span class="font-monospace">{{ $p->cccd }}</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="rounded-3 bg-light border border-dashed px-3 py-2 small text-muted mb-0">
                                                        <i class="bi bi-info-circle me-1"></i>Chưa khai báo tên — chỉ có số lượng ở cột Đặt chỗ.
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-end py-3 text-nowrap">
                                                <span class="text-muted small d-block">× {{ (int) $br->nights }} đêm</span>
                                                <span class="text-dark">{{ number_format($br->price_per_night, 0, ',', '.') }} ₫</span>
                                            </td>
                                            <td class="text-end pe-3 py-3 text-nowrap">
                                                <span class="fw-bold fs-6" style="color: var(--abs-money-plus);">{{ number_format($br->subtotal, 0, ',', '.') }} ₫</span>
                                            </td>
                                            @if(auth()->user() && auth()->user()->role === 'admin' && $booking->status !== 'cancelled' && $booking->status !== 'completed')
                                                <td class="text-center align-middle border-start py-3">
                                                    @include('admin.bookings.partials.room-change-modal', ['booking' => $booking, 'bookingRoom' => $br])
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @include('admin.bookings.partials.room-change-history', ['booking' => $booking])

                <div id="abs-fees" class="abs-panel mb-4 scroll-target">
                    <div class="abs-panel-header">
                        <h2 class="h6 fw-bold mb-0 text-dark">Thu phí &amp; dịch vụ</h2>
                    </div>
                    <div class="abs-panel-body">
                        <div class="abs-fees-zone mb-4">
                            <p class="abs-eyebrow mb-3">Đã ghi nhận</p>
                            <div class="row g-3">
                                <div class="col-12">
                                    <p class="small fw-semibold text-dark mb-2">Dịch vụ danh mục</p>
                                    @if($booking->bookingServices->isNotEmpty())
                                        <div class="table-responsive rounded-3 border bg-white">
                                            <table class="table table-sm mb-0 align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-3">Tên</th>
                                                        <th class="text-end">SL</th>
                                                        <th class="text-end">Đơn giá</th>
                                                        <th class="text-end pe-3">Thành tiền</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($booking->bookingServices as $bs)
                                                        @php $line = (float) $bs->price * (int) $bs->quantity; @endphp
                                                        <tr>
                                                            <td class="ps-3">{{ $bs->service?->name ?? 'Dịch vụ #' . $bs->service_id }}</td>
                                                            <td class="text-end">{{ $bs->quantity }}</td>
                                                            <td class="text-end text-muted">{{ number_format((float) $bs->price, 0, ',', '.') }} ₫</td>
                                                            <td class="text-end pe-3 fw-semibold">@include('shared.partials.money-customer-flow', ['amount' => $line])</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="small text-muted mb-0">Chưa có dịch vụ trong danh mục.</p>
                                    @endif
                                </div>
                                @if($booking->surcharges && $booking->surcharges->isNotEmpty())
                                    <div class="col-12">
                                        <p class="small fw-semibold text-dark mb-2">Phụ phí &amp; điều chỉnh</p>
                                        <div class="table-responsive rounded-3 border bg-white">
                                            <table class="table table-sm mb-0 align-middle">
                                                <thead style="background: rgba(15,118,110,0.08);">
                                                    <tr>
                                                        <th class="ps-3">Nội dung</th>
                                                        <th class="text-end">Ngày lập</th>
                                                        <th class="text-end pe-3">Số tiền</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($booking->surcharges as $surcharge)
                                                        @php $surAmt = (float) $surcharge->amount; @endphp
                                                        <tr>
                                                            <td class="ps-3">
                                                                <div class="fw-semibold text-dark">{{ $surcharge->reason }}</div>
                                                                @if($surcharge->service)
                                                                    <div class="small text-muted mt-1">Liên quan dịch vụ: {{ $surcharge->service->name }}</div>
                                                                @endif
                                                            </td>
                                                            <td class="text-end text-muted text-nowrap">{{ $surcharge->created_at->format('d/m/Y H:i') }}</td>
                                                            <td class="text-end pe-3 fw-semibold text-nowrap">
                                                                @include('shared.partials.money-customer-flow', ['amount' => $surAmt])
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($booking->status !== 'cancelled' && ! $booking->actual_check_out)
                            <div class="abs-fees-form-zone">
                                <p class="abs-eyebrow mb-1">Thêm</p>
                                <form action="{{ route('admin.bookings.storeExtras', $booking) }}" method="POST" class="abs-extras-form abs-extras-form-skin">
                                    @csrf
                                    @if($services->isNotEmpty())
                                        <p class="abs-extras-section-title">Dịch vụ</p>
                                        @include('admin.bookings.partials.booking-catalog-service-lines', ['services' => $services, 'catalogNotice' => $bookingSvcCatalogNotice ?? null])
                                        <div class="my-3 border-top border-secondary-subtle opacity-75"></div>
                                    @else
                                        <p class="small text-muted mb-3">Danh mục trống — <a href="{{ route('admin.services.create') }}">thêm dịch vụ</a> hoặc nhập phụ phí.</p>
                                    @endif
                                    <p class="abs-extras-section-title">Phụ phí</p>
                                    @include('admin.bookings.partials.surcharge-form-fields', ['suffix' => 'extras'])
                                    <div class="d-flex flex-wrap gap-2 align-items-center mt-3 pt-1">
                                        <button type="submit" class="btn btn-primary rounded-pill px-4 btn-sm">
                                            <i class="bi bi-check-lg me-1"></i>Lưu thay đổi
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @elseif($booking->status === 'cancelled')
                            <p class="small text-muted mb-0 fst-italic">Đơn đã hủy — không thêm dịch vụ/phụ phí.</p>
                        @else
                            <p class="small text-muted mb-0 fst-italic">Đơn đã checkout — không thêm dịch vụ/phụ phí.</p>
                        @endif
                    </div>
                </div>

                <div id="abs-system" class="abs-panel mb-4 scroll-target">
                    <div class="abs-panel-header">
                        <h2 class="h6 fw-bold mb-0 text-dark">Trạng thái &amp; TT</h2>
                    </div>
                    <div class="abs-panel-body">
                        <p class="small text-muted mb-3">Chỉ đọc — đổi qua thao tác đơn.</p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="abs-field-label">Tiến trình đơn</label>
                                <input type="text" class="form-control form-control-sm bg-light border-secondary-subtle" value="{{ $booking->status }}" disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="abs-field-label">Thanh toán</label>
                                <input type="text" class="form-control form-control-sm bg-light border-secondary-subtle" value="{{ $booking->payment_status ?? 'pending' }}" disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="abs-field-label">PTTT</label>
                                <input type="text" class="form-control form-control-sm bg-light border-secondary-subtle" value="{{ $booking->payment_method ?? '—' }}" disabled>
                            </div>
                            @if(!empty($latestPayment))
                                <div class="col-12 small text-muted border-top pt-3 mt-1">
                                    Giao dịch gần nhất: <code>{{ $latestPayment->transaction_id ?? '—' }}</code>
                                    · {{ number_format((float) ($latestPayment->amount ?? 0), 0, ',', '.') }} ₫
                                    · {{ $latestPayment->method }} / {{ $latestPayment->status }}
                                    @if($latestPayment->paid_at)
                                        · {{ $latestPayment->paid_at->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                            @endif
                        </div>
                        <hr class="my-4 text-secondary opacity-25">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                            <div class="d-flex flex-wrap gap-2">
                                @if($booking->invoice)
                                    <a href="{{ route('admin.invoices.show', $booking->invoice) }}" class="btn btn-outline-secondary btn-sm rounded-pill" title="Xem hóa đơn">
                                        <i class="bi bi-receipt-cutoff me-1"></i>Hóa đơn
                                    </a>
                                @endif
                            </div>
                            <div class="small text-muted text-md-end">
                                ID #{{ $booking->id }} · Tạo {{ $booking->created_at?->format('d/m/Y') ?? '—' }}
                                @if(auth()->user() && auth()->user()->role === 'admin')
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill ms-2" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Xóa đơn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div id="abs-activity" class="abs-panel mb-4 scroll-target">
                    <div class="abs-panel-header py-2 d-flex align-items-center gap-2 flex-wrap">
                        <i class="bi bi-activity" style="color: var(--abs-accent);"></i>
                        <h2 class="h6 fw-bold mb-0 text-dark">Hoạt động đơn</h2>
                        @if(isset($bookingActivity) && $bookingActivity->isNotEmpty())
                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border">{{ $bookingActivity->count() }} mục</span>
                        @endif
                    </div>
                    <div class="abs-panel-body border-top pt-3">
                        @forelse($bookingActivity ?? collect() as $ev)
                            <div class="{{ $loop->last ? '' : 'pb-3 mb-3 border-bottom border-light' }}">
                                <div class="d-flex flex-wrap gap-3 align-items-start">
                                    <div class="flex-shrink-0 small text-muted" style="min-width: 5.5rem;">
                                        <div class="fw-semibold text-secondary">{{ $ev['at']->format('d/m/Y') }}</div>
                                        <div style="font-size: 0.72rem;">{{ $ev['at']->format('H:i:s') }}</div>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                                            <span class="badge rounded-pill {{ $ev['badge_class'] ?? 'bg-light text-dark border' }}">{{ $ev['badge'] ?? '—' }}</span>
                                            <span class="fw-semibold text-dark">{{ $ev['title'] }}</span>
                                        </div>
                                        @if(!empty($ev['detail']))
                                            <div class="small text-muted mb-1">{{ $ev['detail'] }}</div>
                                        @endif
                                        @if(!empty($ev['actor']))
                                            <div class="small text-muted mb-0"><i class="bi bi-person-fill me-1 opacity-75"></i>{{ $ev['actor'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="small text-muted mb-0">Chưa có hoạt động.</p>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- Sidebar tài chính -->
            <div class="col-xl-4 order-1 order-xl-2">
                <aside id="abs-billing" class="abs-sticky-rail scroll-target">
                    <div class="abs-panel mb-4">
                        <div class="abs-panel-header">
                            <h2 class="h6 fw-bold mb-0 text-dark">Số tiền</h2>
                        </div>
                        <div class="abs-panel-body">
                            <div class="text-center pb-3 mb-3 border-bottom" style="border-color: var(--abs-border);">
                                <div class="abs-eyebrow mb-1">Tổng dự kiến</div>
                                <div class="fs-3 fw-bold text-dark lh-sm">{{ number_format($invoiceSubtotal, 0, ',', '.') }} <span class="fs-5 text-muted">₫</span></div>
                            </div>
                            @if(abs($totalDrift) > 0.009)
                                <div class="alert alert-warning py-2 px-3 small mb-3">
                                    Tổng lưu trên đơn lệch {{ number_format(abs($totalDrift), 0, ',', '.') }} ₫ so với chi tiết dòng tiền (phòng + DV + phụ phí - giảm). Vui lòng kiểm tra lại các thao tác trước đó.
                                </div>
                            @endif
                            <div class="abs-kpi-strip mb-3">
                                <div class="abs-kpi">
                                    <div class="abs-kpi-label">Tiền phòng</div>
                                    <div class="abs-kpi-value">{{ number_format($roomTotal, 0, ',', '.') }} ₫</div>
                                </div>
                                <div class="abs-kpi">
                                    <div class="abs-kpi-label">Dịch vụ</div>
                                    <div class="abs-kpi-value">@include('shared.partials.money-customer-flow', ['amount' => $serviceTotal])</div>
                                </div>
                                @if(abs($surchargeTotal) > 0.009)
                                <div class="abs-kpi">
                                    <div class="abs-kpi-label">Phụ phí</div>
                                    <div class="abs-kpi-value">@include('shared.partials.money-customer-flow', ['amount' => $surchargeTotal])</div>
                                </div>
                                @endif
                            </div>
                            <div class="abs-money-line text-muted"><span>Cộng sau giảm</span><span class="fw-semibold text-dark">{{ number_format($invoiceSubtotal, 0, ',', '.') }} ₫</span></div>
                            @if($discountAmount > 0)
                                <div class="abs-money-line text-muted"><span>Giảm giá</span><span class="fw-semibold">@include('shared.partials.money-customer-flow', ['amount' => -1 * (float) $discountAmount])</span></div>
                            @endif
                            <div class="abs-money-line">
                                <span class="fw-semibold lh-money-paid">Đã thanh toán</span>
                                <span class="fw-bold">@include('shared.partials.money-paid', ['amount' => $paidCollected])</span>
                            </div>
                            @if($pendingLedgerSum > 0.009)
                                <div class="abs-money-line">
                                    <span class="text-muted">Chờ TT <span class="fw-normal small">(pending)</span></span>
                                    <span class="fw-semibold" style="color: var(--abs-money-warn);">{{ number_format($pendingLedgerSum, 0, ',', '.') }} ₫</span>
                                </div>
                            @endif
                            @if($failedLedgerSum > 0.009)
                                <div class="abs-money-line">
                                    <span class="text-muted">Giao dịch thất bại</span>
                                    <span class="fw-semibold text-secondary">{{ number_format($failedLedgerSum, 0, ',', '.') }} ₫</span>
                                </div>
                            @endif
                            <div class="abs-money-line pb-2">
                                <span class="fw-semibold">Còn phải thu</span>
                                <span class="fs-5 fw-bold">@include('shared.partials.money-debt-due', ['amount' => $amountDue, 'class' => 'fs-5'])</span>
                            </div>
                            @if($overpaid > 0.009)
                                <div class="alert alert-info py-2 px-3 small mb-3">
                                    Thu dư <strong>{{ number_format($overpaid, 0, ',', '.') }} ₫</strong> — kiểm tra hoàn / bù.
                                </div>
                            @elseif(in_array((string) ($booking->payment_status ?? ''), ['paid', 'partial'], true) && $amountDue > 0.009)
                                <div class="alert alert-warning py-2 px-3 small mb-3">
                                    «{{ $booking->payment_status }}» nhưng còn <strong>{{ number_format($amountDue, 0, ',', '.') }} ₫</strong> — phụ phí/gia hạn sau TT?
                                </div>
                            @endif
                            <div class="pb-3 mb-3 border-bottom" style="border-bottom-style: solid !important;">
                                <p class="small fw-semibold text-secondary mb-2">Sổ giao dịch</p>
                                @forelse($booking->payments as $payRow)
                                    @php
                                        $ps = (string) $payRow->status;
                                        $badgeClass = match ($ps) {
                                            'paid' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                            'pending' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                            'failed' => 'bg-secondary-subtle text-secondary-emphasis border',
                                            'refunded' => 'bg-info-subtle text-info-emphasis border',
                                            default => 'bg-light text-dark border',
                                        };
                                    @endphp
                                    <div class="abs-pay-ledger d-flex flex-wrap justify-content-between gap-2 py-2 border-bottom border-light align-items-start">
                                        <div class="min-w-0">
                                            <span class="badge rounded-pill {{ $badgeClass }}">{{ $ps }}</span>
                                            <span class="text-muted ms-1">{{ $payRow->method ?? '—' }}</span>
                                            @if($payRow->paid_at)
                                                <div class="text-muted mt-1" style="font-size:0.72rem;">{{ $payRow->paid_at->format('d/m/Y H:i') }}</div>
                                            @endif
                                            @if($payRow->transaction_id)
                                                <div class="text-muted text-truncate mt-0" style="font-size:0.72rem;max-width:14rem;" title="{{ $payRow->transaction_id }}">{{ $payRow->transaction_id }}</div>
                                            @endif
                                        </div>
                                        <div class="text-end fw-semibold text-nowrap">{{ number_format((float) $payRow->amount, 0, ',', '.') }} ₫</div>
                                    </div>
                                @empty
                                    <p class="small text-muted mb-0">Chưa có bản ghi thanh toán.</p>
                                @endforelse
                            </div>
                            <p class="small text-muted mb-1"><strong>Đã thu</strong> chỉ cộng giao dịch <code>paid</code>. Tiền đã <code>paid</code> không bị hệ thống tự nhân lên khi thêm dịch vụ / phụ phí / gia hạn — <code>total_price</code> tăng và «còn phải thu» là phần chưa có phiếu <code>paid</code> mới.</p>
                            <p class="small text-muted mb-0"><code>pending</code> là mức chờ thanh toán (chưa vào quỹ). Không có phân bổ đã thu theo từng dòng phòng/dịch vụ trong DB.</p>
                        </div>
                    </div>

                    @if($booking->actual_check_in || $booking->actual_check_out)
                        <div class="abs-panel mb-4">
                            <div class="abs-panel-header py-3">
                                <h2 class="h6 fw-bold mb-0 text-dark">Check-in / Check-out thực tế</h2>
                            </div>
                            <div class="abs-panel-body small">
                                @if($booking->actual_check_in)
                                    <div class="mb-2"><span class="text-muted">Nhận:</span> <strong>{{ $booking->actual_check_in->format('d/m H:i') }}</strong></div>
                                @endif
                                @if($booking->actual_check_out)
                                    <div class="mb-2"><span class="text-muted">Trả:</span> <strong>{{ $booking->actual_check_out->format('d/m H:i') }}</strong></div>
                                    <div><span class="text-muted">Người trả:</span> <strong>{{ optional(optional($booking->logs->where('new_status', 'completed')->first())->user)->full_name ?? 'Hệ thống' }}</strong></div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="abs-panel">
                        <div class="abs-panel-header py-3">
                            <h2 class="h6 fw-bold mb-0 text-dark">Tóm tắt phòng</h2>
                        </div>
                        <div class="abs-panel-body">
                            @if($booking->bookingRooms->isNotEmpty())
                                @foreach($booking->bookingRooms as $br)
                                    <div class="abs-compact-room">
                                        <div class="fw-semibold">{{ $br->room?->displayLabel() ?? 'Chưa gán phòng' }}</div>
                                        <div class="text-muted">{{ $br->room?->roomType?->name ?? '—' }} · {{ (int) $br->nights }} đêm</div>
                                        <div class="mt-1"><span class="text-muted">Thành tiền:</span> <strong style="color: var(--abs-money-plus);">{{ number_format($br->subtotal, 0, ',', '.') }} ₫</strong></div>
                                    </div>
                                @endforeach
                            @else
                                <p class="small text-muted mb-0">Không có phòng.</p>
                            @endif
                        </div>
                    </div>

                    <a href="#abs-fees" class="btn btn-outline-secondary btn-sm w-100 mt-3 rounded-pill d-xl-none">
                        <i class="bi bi-tools me-1"></i>Xuống phần thu phí
                    </a>
                </aside>
            </div>
        </div>
    </div>
</div>

@if(auth()->user() && auth()->user()->role === 'admin')
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-3 border-0">
            <div class="modal-header bg-danger text-white border-0 rounded-top-3">
                <h5 class="modal-title fw-bold">⚠️ Xác nhận xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xóa đơn #{{ $booking->id }}? <strong>Không thể hoàn tác.</strong>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary rounded-2 btn-admin-icon" data-bs-dismiss="modal" title="Hủy"><i class="bi bi-x-lg"></i></button>
                <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded-2 btn-admin-icon" title="Xóa đơn"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@if(!empty($canExtendStay) && !empty($minExtendCheckOut))
@push('scripts')
<script>
(function () {
    var inp = document.getElementById('extend_new_check_out');
    var box = document.getElementById('extend-quote-box');
    if (!inp || !box) return;

    var quoteUrl = @json(route('admin.bookings.extend-quote', $booking));
    var fmt = function (n) {
        try {
            return new Intl.NumberFormat('vi-VN').format(Math.round(Number(n))) + ' ₫';
        } catch (e) {
            return Math.round(Number(n)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' ₫';
        }
    };

    var timer;
    function renderQuote(data) {
        if (!data.ok) {
            box.classList.remove('d-none');
            box.innerHTML = '<span class="text-danger">' + (data.message || 'Không tính được giá.') + '</span>';
            return;
        }
        box.classList.remove('d-none');
        var warn = data.has_availability_conflict
            ? '<div class="alert alert-warning py-1 px-2 mb-2 small">Một hoặc nhiều phòng đã có đặt chỗ trùng các đêm gia hạn — không thể xác nhận.</div>'
            : '';
        var lines = (data.lines || []).map(function (ln) {
            var occ = ln.adults + ' NL';
            if (ln.children_6_11) occ += ', ' + ln.children_6_11 + ' trẻ 6–11';
            if (ln.children_0_5) occ += ', ' + ln.children_0_5 + ' trẻ 0–5';
            var blk = ln.availability_blocked ? ' <span class="badge bg-danger ms-1">Trùng lịch</span>' : '';
            return '<li class="mb-1"><span class="fw-semibold">' + (ln.label || ('Phòng #' + ln.booking_room_id)) + '</span>' + blk + '<br>'
                + '<span class="text-muted">' + occ + ' · +' + ln.extension_nights + ' đêm</span> → <strong>' + fmt(ln.extension_amount) + '</strong>'
                + '</li>';
        }).join('');
        box.innerHTML = warn
            + '<div class="fw-semibold mb-1">Thêm: +' + data.extension_nights + ' đêm · Phòng: +' + fmt(data.extension_room_total) + '</div>'
            + '<div class="text-muted mb-2">Tổng dự kiến sau gia hạn: <strong class="text-dark">' + fmt(data.new_grand_total) + '</strong></div>'
            + '<ul class="mb-0 ps-3">' + lines + '</ul>';
    }

    function fetchQuote() {
        var v = inp.value;
        if (!v) {
            box.classList.add('d-none');
            box.innerHTML = '';
            return;
        }
        box.classList.remove('d-none');
        box.innerHTML = '<span class="text-muted">Đang tính giá…</span>';
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch(quoteUrl + '?new_check_out=' + encodeURIComponent(v), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); }).then(renderQuote).catch(function () {
                box.innerHTML = '<span class="text-danger">Không lấy được báo giá.</span>';
            });
        }, 350);
    }

    inp.addEventListener('change', fetchQuote);
    inp.addEventListener('input', fetchQuote);
})();
</script>
@endpush
@endif

@endsection
