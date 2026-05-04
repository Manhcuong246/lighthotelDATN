@extends('layouts.admin')

@section('title', 'Tạo hóa đơn')

@section('content')
@php
    $nights = max(1, \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out));
    $servicesAmount = (float) $booking->bookingServices->sum(function ($s) {
        return (float) $s->price * (int) $s->quantity;
    });
    $surchargesAmount = (float) $booking->surcharges->sum(function ($s) {
        return (float) $s->amount;
    });
    $couponDiscount = (float) ($booking->discount_amount ?? 0);
    $roomsSum = (float) $booking->bookingRooms->sum('subtotal');
    $roomPreview = $roomsSum > 0
        ? $roomsSum
        : max(0, (float) $booking->total_price - $servicesAmount - $surchargesAmount + $couponDiscount);
    $bookingTotal = (float) $booking->total_price;
    $roomLabel = $booking->rooms->isNotEmpty()
        ? $booking->rooms->pluck('name')->filter()->implode(', ')
        : ($booking->room?->name ?? '—');
@endphp
<div class="container-fluid px-3 px-lg-4">
    <div class="mb-4">
        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-secondary btn-admin-icon rounded-2 mb-2" title="Quay lại đơn"><i class="bi bi-arrow-left"></i></a>
        <h1 class="h2 fw-bold mb-0">Tạo hóa đơn — Đơn #{{ $booking->id }}</h1>
        <p class="text-muted small mb-0">Hóa đơn sẽ liệt kê <strong>từng phòng</strong> (nếu có), <strong>dịch vụ đặt kèm</strong>, <strong>phụ thu phát sinh</strong> và <strong>giảm giá đặt phòng</strong>. Có thể thêm giảm giá / thuế riêng trên hóa đơn.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-3">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-uppercase small text-muted mb-3">Thông tin đơn</h6>
                    <dl class="row small mb-0">
                        <dt class="col-sm-4">Khách</dt>
                        <dd class="col-sm-8">{{ $booking->user?->full_name ?? '—' }} <span class="text-muted">({{ $booking->user?->email }})</span></dd>
                        <dt class="col-sm-4">Nhận / trả</dt>
                        <dd class="col-sm-8">{{ $booking->check_in?->format('d/m/Y') }} → {{ $booking->check_out?->format('d/m/Y') }} <span class="text-muted">({{ $nights }} đêm)</span></dd>
                        <dt class="col-sm-4">Phòng</dt>
                        <dd class="col-sm-8">{{ $roomLabel }}</dd>
                    </dl>
                </div>
            </div>

            @if($booking->bookingRooms->isNotEmpty())
            <div class="card border-0 shadow-sm rounded-3 mt-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-uppercase small text-muted mb-3">Chi tiết lưu trú (theo phòng)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Phòng</th><th class="text-center">Đêm</th><th class="text-end">Thành tiền</th></tr>
                            </thead>
                            <tbody>
                                @foreach($booking->bookingRooms as $br)
                                    <tr>
                                        <td>{{ $br->room?->name ?? '#' . $br->room_id }}</td>
                                        <td class="text-center">{{ $br->nights }}</td>
                                        <td class="text-end fw-semibold">{{ number_format((float) $br->subtotal, 0, ',', '.') }} ₫</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="card border-0 shadow-sm rounded-3 mt-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-uppercase small text-muted mb-3">Dịch vụ kèm đơn</h6>
                    @if($booking->bookingServices->isEmpty())
                        <p class="text-muted small mb-0">Không có dịch vụ đặt kèm.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Dịch vụ</th><th class="text-end">SL</th><th class="text-end">Thành tiền</th></tr></thead>
                                <tbody>
                                    @foreach($booking->bookingServices as $bs)
                                        @php $line = (float) $bs->price * (int) $bs->quantity; @endphp
                                        <tr>
                                            <td>{{ $bs->service?->name ?? '#' . $bs->service_id }}</td>
                                            <td class="text-end">{{ $bs->quantity }}</td>
                                            <td class="text-end fw-semibold">@include('shared.partials.money-customer-flow', ['amount' => $line])</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 mt-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-uppercase small text-muted mb-3">Phụ thu / phát sinh</h6>
                    @if($booking->surcharges->isEmpty())
                        <p class="text-muted small mb-0">Không có phụ thu ghi nhận.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Mô tả</th><th class="text-end">Số tiền</th></tr></thead>
                                <tbody>
                                    @foreach($booking->surcharges as $sc)
                                        <tr>
                                            <td>{{ $sc->reason }}</td>
                                            <td class="text-end fw-semibold">@include('shared.partials.money-customer-flow', ['amount' => (float) $sc->amount])</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 border-primary border-opacity-25">
                <div class="card-header bg-primary text-white py-3 rounded-top-3">
                    <span class="fw-bold">Phát hành hóa đơn</span>
                </div>
                <div class="card-body p-4">
                    <ul class="list-unstyled small mb-4">
                        <li class="d-flex justify-content-between py-1"><span>Tiền lưu trú (ước tính)</span><strong>{{ number_format($roomPreview, 0, ',', '.') }} ₫</strong></li>
                        <li class="d-flex justify-content-between py-1"><span>Dịch vụ đặt kèm</span><strong>{{ number_format($servicesAmount, 0, ',', '.') }} ₫</strong></li>
                        <li class="d-flex justify-content-between py-1"><span>Phụ thu phát sinh</span><strong>@include('shared.partials.money-customer-flow', ['amount' => (float) $surchargesAmount])</strong></li>
                        @if($couponDiscount > 0)
                            <li class="d-flex justify-content-between py-1"><span>Giảm khi đặt phòng</span><strong>@include('shared.partials.money-customer-flow', ['amount' => -1 * (float) $couponDiscount])</strong></li>
                        @endif
                        <li class="d-flex justify-content-between py-2 border-top mt-2 fw-bold"><span>Tổng đơn (thanh toán)</span><span>{{ number_format($bookingTotal, 0, ',', '.') }} ₫</span></li>
                    </ul>
                    <p class="small text-muted">Tổng hóa đơn sau khi tạo = <strong>{{ number_format($bookingTotal, 0, ',', '.') }} ₫</strong> − giảm trên HĐ + thuế (nếu nhập bên dưới).</p>

                    <form action="{{ route('admin.invoices.store', $booking) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="discount_amount">Giảm giá thêm (VNĐ)</label>
                            <input type="number" name="discount_amount" id="discount_amount" class="form-control form-control-sm" min="0" step="1000" value="{{ old('discount_amount', 0) }}" placeholder="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="tax_amount">Thuế &amp; phí (VNĐ)</label>
                            <input type="number" name="tax_amount" id="tax_amount" class="form-control form-control-sm" min="0" step="1000" value="{{ old('tax_amount', 0) }}" placeholder="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="notes">Ghi chú hóa đơn</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control form-control-sm" maxlength="1000" placeholder="Tùy chọn">{{ old('notes') }}</textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary rounded-2"><i class="bi bi-receipt me-1"></i> Tạo hóa đơn</button>
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline-secondary btn-sm rounded-2">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
