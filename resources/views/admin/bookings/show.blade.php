@extends('layouts.admin')

@section('title', 'Chi tiết đặt phòng')

@push('styles')
<style>
    .booking-room-card {
        border-radius: 0.5rem;
        border: 1px solid rgba(0, 0, 0, 0.06);
        background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
        border-left: 3px solid var(--bs-primary);
    }
    .booking-guest-chip {
        border-radius: 0.45rem;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0.5rem 0.65rem;
        font-size: 0.8125rem;
    }
    .booking-guest-chip:last-child { margin-bottom: 0 !important; }
    .booking-rooms-thead th {
        font-size: 0.7rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 600;
        border-bottom-width: 2px;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <!-- Header -->
    <div class="mb-4">
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary btn-admin-icon rounded-2 mb-3" title="Quay lại danh sách"><i class="bi bi-arrow-left"></i></a>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h2 fw-bold mb-0">📋 Đơn #{{ $booking->id }}</h1>
            <div class="d-flex align-items-center gap-2">
                @if($booking->isPaidAndCheckedOutForInvoice())
                <a href="{{ route('admin.bookings.invoice', $booking) }}" class="btn btn-sm btn-outline-dark no-print" target="_blank" rel="noopener">
                    <i class="bi bi-receipt-cutoff me-1"></i>Biên lai
                </a>
                @endif
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
            @endphp
            <span class="badge bg-{{ $statusColors[$booking->status] ?? 'secondary' }} px-4 py-2 fs-6">
                {{ $statusLabels[$booking->status] ?? '—' }}
            </span>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <strong>✅ Thành công!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <strong>❌ Lỗi!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        <!-- Main Content - Compact Layout -->
        <div class="col-12">
            <!-- Comprehensive Info Card -->
            <div class="card border-0 rounded-3 shadow-sm">
                <div class="card-body p-4">
                    <!-- Top Row: Customer + Booking Info (editable) -->
                    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" id="bookingInfoForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="{{ $booking->status }}">
                        <div class="row g-3 mb-4 align-items-end">
                            <div class="col-md-3">
                                <p class="text-uppercase small fw-bold text-muted mb-1">Khách hàng</p>
                                <p class="mb-0 fw-bold text-primary">{{ $booking->user?->full_name ?? '—' }}</p>
                                <small class="text-muted d-block">{{ $booking->user?->email ?? '—' }}</small>
                                <small class="text-muted">{{ $booking->user?->phone ?? '—' }}</small>
                            </div>
                            <div class="col-md-2">
                                <label for="check_in" class="text-uppercase small fw-bold text-muted mb-1 d-block">Nhận</label>
                                <input type="date" class="form-control form-control-sm" id="check_in" name="check_in"
                                       value="{{ $booking->check_in?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="check_out" class="text-uppercase small fw-bold text-muted mb-1 d-block">Trả</label>
                                <input type="date" class="form-control form-control-sm" id="check_out" name="check_out"
                                       value="{{ $booking->check_out?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="total_price" class="text-uppercase small fw-bold text-muted mb-1 d-block">Tổng</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm" id="total_price" name="total_price"
                                           min="0" step="1000" value="{{ $booking->total_price }}">
                                    <span class="input-group-text">₫</span>
                                </div>
                                @if($booking->discount_amount > 0)
                                    <small class="text-danger">Giảm: {{ number_format($booking->discount_amount, 0, ',', '.') }} ₫</small>
                                @endif
                            </div>
                            <div class="col-md-1 text-center">
                                <p class="text-uppercase small fw-bold text-muted mb-1">Phòng</p>
                                <span class="badge bg-primary px-3 py-2">{{ $booking->rooms->count() }}</span>
                            </div>
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-sm btn-outline-primary rounded-2">
                                    <i class="bi bi-check-lg me-1"></i>Lưu ngày &amp; tổng
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Room List Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <p class="text-uppercase small fw-bold text-muted mb-0">Phòng trong đơn</p>
                                <span class="small text-muted">{{ $booking->bookingRooms->count() }} phòng</span>
                            </div>
                            <div class="table-responsive rounded-3 border shadow-sm bg-white">
                                <table class="table table-hover mb-0 align-top booking-rooms-table">
                                    <thead class="table-light booking-rooms-thead">
                                        <tr>
                                            <th class="ps-3" style="min-width: 160px;">Phòng</th>
                                            <th class="text-center" style="width: 7rem;" title="Ưu tiên đếm từ danh sách khách; chưa khai báo tên thì theo số đặt trên đơn">Đặt chỗ</th>
                                            <th style="min-width: 260px;">Khách lưu trú</th>
                                            <th class="text-end" style="min-width: 6.5rem;">Giá / đêm</th>
                                            <th class="text-end pe-3" style="min-width: 7rem;">Thành tiền</th>
                                            @if(auth()->user() && auth()->user()->role === 'admin' && $booking->status !== 'cancelled' && $booking->status !== 'completed')
                                                <th class="text-center border-start bg-light" style="width: 4.5rem;">Đổi</th>
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
                                                        <div class="fw-bold fs-6 text-dark lh-sm">{{ $rn ?: '—' }}</div>
                                                        @if($rtName)
                                                            <span class="badge rounded-pill mt-1 bg-primary-subtle text-primary border border-primary-subtle fw-normal">{{ $rtName }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-warning fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Chưa gán phòng</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center py-3">
                                                <div class="d-inline-flex flex-column align-items-center gap-1 small">
                                                    <span class="rounded-pill bg-light border px-2 py-1 text-nowrap" title="Người lớn">
                                                        <i class="bi bi-person-fill text-secondary"></i>
                                                        <strong class="text-dark">{{ (int) $occ['adults'] }}</strong>
                                                        <span class="text-muted">NL</span>
                                                    </span>
                                                    @if(count($childParts))
                                                        <span class="rounded-pill bg-light border px-2 py-1 text-muted text-wrap text-start" style="max-width: 11rem;">
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
                                                                            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary-subtle">Đại diện</span>
                                                                        @endif
                                                                        @if($st === 'checked_in')
                                                                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success-subtle">Đã check-in</span>
                                                                        @elseif($st === 'pending' || $st === null)
                                                                            <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">Chờ check-in</span>
                                                                        @else
                                                                            <span class="badge rounded-pill bg-light text-dark border">{{ $st }}</span>
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
                                                        <i class="bi bi-info-circle me-1"></i>Chưa khai báo tên khách — chỉ có số lượng đặt chỗ ở cột bên trái.
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-end py-3 text-nowrap">
                                                <span class="text-muted small d-block">× {{ (int) $br->nights }} đêm</span>
                                                <span class="text-dark">{{ number_format($br->price_per_night, 0, ',', '.') }} ₫</span>
                                            </td>
                                            <td class="text-end pe-3 py-3 text-nowrap">
                                                <span class="fw-bold text-success fs-6">{{ number_format($br->subtotal, 0, ',', '.') }} ₫</span>
                                            </td>
                                            @if(auth()->user() && auth()->user()->role === 'admin' && $booking->status !== 'cancelled' && $booking->status !== 'completed')
                                            <td class="text-center align-middle border-start bg-light py-3">
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

                    <!-- Lịch sử đổi phòng -->
                    @include('admin.bookings.partials.room-change-history', ['booking' => $booking])

                    <div id="booking-extras" class="row mt-3 pt-3 border-top" style="scroll-margin-top: 5rem;">
                        <div class="col-12">
                            <p class="text-uppercase small fw-bold text-muted mb-2">Dịch vụ &amp; phụ phí</p>

                            @if($booking->bookingServices->isNotEmpty())
                            <div class="table-responsive rounded-2 border bg-white mb-3">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Tên dịch vụ</th>
                                            <th class="text-end">SL</th>
                                            <th class="text-end">Đơn giá</th>
                                            <th class="text-end pe-3">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($booking->bookingServices as $bs)
                                        @php
                                            $line = (float) $bs->price * (int) $bs->quantity;
                                        @endphp
                                        <tr>
                                            <td class="ps-3">{{ $bs->service?->name ?? 'Dịch vụ #' . $bs->service_id }}</td>
                                            <td class="text-end">{{ $bs->quantity }}</td>
                                            <td class="text-end text-muted">{{ number_format((float) $bs->price, 0, ',', '.') }} ₫</td>
                                            <td class="text-end pe-3 fw-semibold">{{ number_format($line, 0, ',', '.') }} ₫</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                                <p class="small text-muted mb-2">Chưa có dịch vụ danh mục.</p>
                            @endif

                            @if($booking->surcharges && $booking->surcharges->isNotEmpty())
                            <p class="text-uppercase small fw-bold text-muted mb-2">Phụ phí đã ghi</p>
                            <div class="table-responsive rounded-2 border border-danger bg-white mb-3">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-danger text-danger">
                                        <tr>
                                            <th class="ps-3">Nội dung</th>
                                            <th class="text-end">Ngày giờ lập</th>
                                            <th class="text-end pe-3">Số tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($booking->surcharges as $surcharge)
                                        <tr>
                                            <td class="ps-3 text-dark">
                                                <div class="fw-semibold">{{ $surcharge->reason }}</div>
                                                @if($surcharge->service)
                                                    <div class="small text-muted mt-1">(cũ: {{ $surcharge->service->name }})</div>
                                                @endif
                                            </td>
                                            <td class="text-end text-muted">{{ $surcharge->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="text-end pe-3 fw-semibold text-danger">+ {{ number_format($surcharge->amount, 0, ',', '.') }} ₫</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

                            @if($booking->status !== 'cancelled')
                            <div id="booking-extras-form" class="border rounded-3 p-3 bg-light">
                                <form action="{{ route('admin.bookings.storeExtras', $booking) }}" method="POST">
                                    @csrf
                                    @if($services->isNotEmpty())
                                        <p class="small fw-bold text-muted mb-1">Dịch vụ danh mục</p>
                                        <p class="small text-muted mb-2">Muốn lưu dịch vụ kèm: <strong>phải chọn tên dịch vụ</strong> trong ô xổ (không để dòng mặc định). Chỉ thêm phụ phí thì bỏ qua phần này.</p>
                                        @include('admin.bookings.partials.booking-catalog-service-lines', ['services' => $services])
                                    @else
                                        <p class="small text-muted mb-3">Chưa có dịch vụ trong danh mục — chỉ thêm phụ phí bên dưới, hoặc <a href="{{ route('admin.services.create') }}">tạo dịch vụ</a>.</p>
                                    @endif
                                    <hr class="my-3">
                                    <p class="small fw-bold text-muted mb-2">Phụ phí</p>
                                    @include('admin.bookings.partials.surcharge-form-fields', ['suffix' => 'extras'])
                                    <button type="submit" class="btn btn-primary btn-sm rounded-2 mt-3">
                                        <i class="bi bi-check-lg me-1"></i>Lưu dịch vụ &amp; phụ phí
                                    </button>
                                </form>
                            </div>
                            @endif
                        </div>
                    </div>

                    @php
                        $roomTotal = $booking->bookingRooms->sum('subtotal');
                        $serviceTotal = $booking->bookingServices->sum(fn($bs) => (float) $bs->price * (int) $bs->quantity);
                        $discountAmount = $booking->discount_amount ?? 0;
                        $invoiceSubtotal = max(0, $roomTotal + $serviceTotal - $discountAmount);
                        $paidAmount = $booking->payments->sum('amount');
                        $depositAmount = in_array($booking->payment_status, ['partial', 'paid'], true) ? $paidAmount : 0;
                        $amountDue = max(0, $invoiceSubtotal - $depositAmount);
                    @endphp

                    <div class="row mt-4 pt-4 border-top">
                        <div class="col-12">
                            <h5 class="mb-3">Chi tiết hóa đơn</h5>
                        </div>
                        <div class="col-lg-6">
                            <div class="card border rounded-3 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="small fw-bold text-uppercase text-muted mb-3">Thông tin phòng</h6>
                                    @if($booking->bookingRooms->isNotEmpty())
                                        <div class="list-group list-group-flush">
                                            @foreach($booking->bookingRooms as $br)
                                                @php
                                                    $roomPersons = $booking->guestLineItemsForBookingRoom($br);
                                                @endphp
                                                <div class="list-group-item px-0 py-2 border-0">
                                                    <div class="fw-semibold">{{ $br->room?->displayLabel() ?? 'Chưa gán phòng' }}</div>
                                                    <div class="small text-muted">{{ $br->room?->roomType?->name ?? 'Loại phòng không xác định' }}</div>
                                                    <div class="small text-muted">{{ $br->nights ?? $booking->nights }} đêm · {{ number_format($br->price_per_night, 0, ',', '.') }} ₫/đêm</div>
                                                    <div class="small">Thành tiền: <strong>{{ number_format($br->subtotal, 0, ',', '.') }} ₫</strong></div>
                                                    @if($roomPersons->isNotEmpty())
                                                        <div class="small mt-2 pt-2 border-top">
                                                            <strong class="text-muted">Khách:</strong>
                                                            <ul class="mb-0 ps-3 mt-1">
                                                                @foreach($roomPersons as $rp)
                                                                    <li>
                                                                        {{ $rp->name }}
                                                                        — {{ \App\Models\BookingGuest::typeLabel($rp->type ?? 'adult') }}
                                                                        @if($rp->cccd)<span class="text-muted">(CCCD {{ $rp->cccd }})</span>@endif
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="mb-0 small text-muted">Không có dữ liệu phòng.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card border rounded-3 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="small fw-bold text-uppercase text-muted mb-3">Tóm tắt hóa đơn</h6>
                                    <dl class="row mb-0">
                                        <dt class="col-7 text-muted">Tiền phòng</dt>
                                        <dd class="col-5 text-end">{{ number_format($roomTotal, 0, ',', '.') }} ₫</dd>

                                        <dt class="col-7 text-muted">Dịch vụ</dt>
                                        <dd class="col-5 text-end">{{ number_format($serviceTotal, 0, ',', '.') }} ₫</dd>

                                        @if($discountAmount > 0)
                                            <dt class="col-7 text-muted">Giảm giá</dt>
                                            <dd class="col-5 text-end text-danger">- {{ number_format($discountAmount, 0, ',', '.') }} ₫</dd>
                                        @endif

                                        <dt class="col-7 fw-semibold">Tổng trước cọc</dt>
                                        <dd class="col-5 text-end fw-semibold">{{ number_format($invoiceSubtotal, 0, ',', '.') }} ₫</dd>

                                        <dt class="col-7 text-muted">Đã cọc / thanh toán</dt>
                                        <dd class="col-5 text-end text-success">{{ number_format($depositAmount, 0, ',', '.') }} ₫</dd>

                                        <dt class="col-7 fw-semibold">Còn nợ</dt>
                                        <dd class="col-5 text-end fw-bold">{{ number_format($amountDue, 0, ',', '.') }} ₫</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="card border rounded-3 shadow-sm">
                                <div class="card-body">
                                    <h6 class="small fw-bold text-uppercase text-muted mb-3">Chi tiết dịch vụ</h6>
                                    @if($booking->bookingServices->isNotEmpty())
                                        <div class="table-responsive rounded-2 border bg-white">
                                            <table class="table table-sm mb-0 align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-3">Dịch vụ</th>
                                                        <th class="text-end">SL</th>
                                                        <th class="text-end">Đơn giá</th>
                                                        <th class="text-end pe-3">Thành tiền</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($booking->bookingServices as $bs)
                                                        @php $lineTotal = (float) $bs->price * (int) $bs->quantity; @endphp
                                                        <tr>
                                                            <td class="ps-3">{{ $bs->service?->name ?? 'Dịch vụ #' . $bs->service_id }}</td>
                                                            <td class="text-end">{{ $bs->quantity }}</td>
                                                            <td class="text-end text-muted">{{ number_format((float) $bs->price, 0, ',', '.') }} ₫</td>
                                                            <td class="text-end pe-3">{{ number_format($lineTotal, 0, ',', '.') }} ₫</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="mb-0 small text-muted">Chưa có dịch vụ được gán.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @php
                        $hotelInfo = \App\Models\HotelInfo::first();
                        $payment = $booking->payment;
                    @endphp
                    @if($hotelInfo && $hotelInfo->bank_id && $hotelInfo->bank_account && $payment && $payment->method === 'bank_transfer' && in_array($payment->status, ['pending', 'partial']))
                    <!-- QR Code Payment Section -->
                    <div class="row mt-4 pt-3 border-top">
                        <div class="col-12">
                            <h6 class="fw-bold mb-3">QR thanh toán</h6>
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    @php
                                        $qrUrl = "https://img.vietqr.io/image/{$hotelInfo->bank_id}-{$hotelInfo->bank_account}-print.png?amount={$payment->amount}&addInfo=BOOKING{$booking->id}&accountName=" . urlencode($hotelInfo->bank_account_name);
                                    @endphp
                                    <img src="{{ $qrUrl }}" alt="QR Code Thanh toán" class="img-fluid border rounded" style="max-width: 200px;">
                                </div>
                                <div class="col-md-8">
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-2"><strong>Ngân hàng:</strong> {{ strtoupper($hotelInfo->bank_id) }}</p>
                                        <p class="mb-2"><strong>Số tài khoản:</strong> {{ $hotelInfo->bank_account }}</p>
                                        <p class="mb-2"><strong>Chủ tài khoản:</strong> {{ $hotelInfo->bank_account_name }}</p>
                                        <p class="mb-2"><strong>Số tiền:</strong> <span class="text-success fw-bold">{{ number_format($payment->amount, 0, ',', '.') }} ₫</span></p>
                                        <p class="mb-0"><strong>Nội dung CK:</strong> <code>BOOKING{{ $booking->id }}</code></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($booking->actual_check_in || $booking->actual_check_out)
                    <!-- Actual Times Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="bg-light p-3 rounded-2">
                                <div class="row g-3">
                                    <div class="col-auto">
                                        <small class="text-uppercase fw-bold text-muted">CI / CO</small>
                                    </div>
                                    @if($booking->actual_check_in)
                                    <div class="col-auto">
                                        <small class="text-muted">Nhận:</small>
                                        <strong>{{ $booking->actual_check_in->format('d/m H:i') }}</strong>
                                    </div>
                                    @endif
                                    @if($booking->actual_check_out)
                                    <div class="col-auto">
                                        <small class="text-muted">Trả:</small>
                                        <strong>{{ $booking->actual_check_out->format('d/m H:i') }}</strong>
                                    </div>
                                    <div class="col-auto">
                                        <small class="text-muted">Người check-out:</small>
                                        <strong>{{ optional($booking->logs->where('new_status', 'completed')->first()->user)->full_name ?? 'Hệ thống' }}</strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Trạng thái đơn & thanh toán (chỉnh thủ công) — neo #payment-booking-settings từ màn Thanh toán -->
                    <div id="payment-booking-settings" class="row g-3 mt-2 pt-3 border-top" style="scroll-margin-top: 5rem;">
                        <div class="col-12">
                            <h6 class="fw-bold mb-2">Trạng thái &amp; thanh toán</h6>
                            <p class="small text-muted mb-3">Tiến trình đơn khác ghi nhận tiền. Hoàn tiền: không sửa.</p>
                            @php
                                $paymentLocked = in_array((string) $booking->payment_status, ['refunded', 'partial_refunded'], true);
                                $isCancelled = $booking->status === 'cancelled';
                            @endphp
                            @if($paymentLocked)
                                <div class="alert alert-warning mb-0 small py-2">Đã hoàn tiền — khóa chỉnh sửa.</div>
                            @else
                            @if($booking->status === 'confirmed' && $booking->payment_status === 'pending')
                                <div class="alert alert-warning py-2 mb-3 small">Đơn đã xác nhận nhưng chưa ghi nhận thanh toán — chỉnh cho khớp.</div>
                            @endif
                            <form action="{{ route('admin.bookings.update-payment-settings', $booking) }}" method="POST" class="row g-3 align-items-end">
                                @csrf
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Tiến trình đơn</label>
                                    @if($isCancelled)
                                        <input type="hidden" name="booking_status" value="cancelled">
                                        <input type="text" class="form-control form-control-sm bg-light" value="Đã hủy" disabled>
                                    @else
                                        <select name="booking_status" class="form-select form-select-sm" required>
                                            <option value="pending" @selected($booking->status === 'pending')>Chờ xác nhận</option>
                                            <option value="confirmed" @selected($booking->status === 'confirmed')>Đã xác nhận</option>
                                            <option value="completed" @selected($booking->status === 'completed')>Hoàn thành</option>
                                            <option value="cancelled" @selected($booking->status === 'cancelled')>Hủy</option>
                                        </select>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Thanh toán (sổ)</label>
                                    <select name="payment_status" class="form-select form-select-sm" required>
                                        <option value="pending" @selected($booking->payment_status === 'pending')>Chưa thu</option>
                                        <option value="paid" @selected($booking->payment_status === 'paid')>Đã thu</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">PTTT</label>
                                    <select name="payment_method" class="form-select form-select-sm" required>
                                        <option value="cash" @selected($booking->payment_method === 'cash')>Tiền mặt</option>
                                        <option value="vnpay" @selected($booking->payment_method === 'vnpay')>VNPay</option>
                                        <option value="bank_transfer" @selected($booking->payment_method === 'bank_transfer')>Chuyển khoản</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-outline-primary btn-sm w-100 rounded-2">Lưu trạng thái &amp; thanh toán</button>
                                </div>
                                @if(!empty($latestPayment))
                                <div class="col-12 small text-muted border-top pt-2">
                                    TT gần nhất: <code>{{ $latestPayment->transaction_id ?? '—' }}</code>
                                    · {{ number_format((float) ($latestPayment->amount ?? 0), 0, ',', '.') }} ₫
                                    · {{ $latestPayment->method }} / {{ $latestPayment->status }}
                                    @if($latestPayment->paid_at)
                                        · {{ $latestPayment->paid_at->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                                @endif
                            </form>
                            @endif
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex flex-wrap gap-2">
                                @if($booking->invoice)
                                <a href="{{ route('admin.invoices.show', $booking->invoice) }}" class="btn btn-outline-secondary btn-sm rounded-2 btn-admin-icon" title="Xem hóa đơn"><i class="bi bi-receipt-cutoff"></i></a>
                                @elseif($booking->isPaidAndCheckedOutForInvoice())
                                <a href="{{ route('admin.invoices.create', $booking) }}" class="btn btn-outline-secondary btn-sm rounded-2 btn-admin-icon" title="Tạo hóa đơn"><i class="bi bi-receipt"></i></a>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Info and Actions -->
                            <div class="d-flex align-items-center justify-content-between">
                                <small class="text-muted">
                                    ID: #{{ $booking->id }} |
                                    Tạo: {{ $booking->created_at?->format('d/m/Y') ?? '—' }}
                                </small>
                                @if(auth()->user() && auth()->user()->role === 'admin')
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-2 btn-admin-icon" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Xóa đơn"><i class="bi bi-trash"></i></button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Section - Collapsible -->
        <div class="col-12">
            <div class="card border-0 rounded-3 shadow-sm">
                <div class="card-header bg-light border-0 rounded-top-3 py-2">
                    <h6 class="mb-0 fw-bold">
                        <button class="btn btn-link p-0 text-decoration-none fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#historyCollapse">
                            📝 Lịch sử thay đổi
                        </button>
                    </h6>
                </div>
                <div class="collapse" id="historyCollapse">
                    <div class="card-body py-3">
                        @if($booking->logs && $booking->logs->count())
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($booking->logs as $log)
                                    <div class="d-flex flex-column gap-1 bg-light px-3 py-2 rounded-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-light text-dark small">{{ ucfirst($log->old_status) }}</span>
                                            <span class="text-muted small">→</span>
                                            <span class="badge bg-primary small">{{ ucfirst($log->new_status) }}</span>
                                            <small class="text-muted">{{ $log->changed_at?->format('d/m H:i') ?? '—' }}</small>
                                        </div>
                                        <div class="small text-muted">Người thực hiện: {{ $log->user?->full_name ?? 'Hệ thống' }}</div>
                                        @if($log->notes)
                                            <div class="small text-muted">{{ $log->notes }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center py-3 mb-0">📭 Chưa có lịch sử thay đổi</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
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

<style>
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12) !important;
    }
    .rounded-2 { border-radius: 8px !important; }
    .rounded-3 { border-radius: 12px !important; }
    .sticky-top { position: sticky; z-index: 100; }
</style>
@endsection
