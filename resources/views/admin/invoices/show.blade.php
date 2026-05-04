@extends('layouts.admin')

@section('title', 'Hóa đơn ' . $invoice->invoice_number)

@section('content')
@php
    $lp = $invoice->booking?->latestPayment;
@endphp
<div class="container-fluid px-3 px-lg-4">
    <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary btn-admin-icon rounded-2 mb-2" title="Danh sách"><i class="bi bi-arrow-left"></i></a>
            <h1 class="h2 fw-bold mb-0">Hóa đơn <code>{{ $invoice->invoice_number }}</code></h1>
            <p class="text-muted small mb-0">Đơn #{{ $invoice->booking_id }} — {{ $invoice->issued_at?->format('d/m/Y H:i') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.print', $invoice) }}" class="btn btn-sm btn-outline-dark rounded-2" target="_blank" rel="noopener"><i class="bi bi-printer me-1"></i> In</a>
            <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary rounded-2"><i class="bi bi-pencil me-1"></i> Sửa</a>
            <a href="{{ route('admin.bookings.show', $invoice->booking_id) }}" class="btn btn-sm btn-primary rounded-2">Mở đơn</a>
        </div>
    </div>

    @if(session('info'))<div class="alert alert-info rounded-3">{{ session('info') }}</div>@endif

    <div class="row g-3">
        <div class="col-lg-8">
            @if($hotel ?? null)
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="h5 fw-bold text-primary mb-1">{{ $hotel->name }}</h2>
                            @if($hotel->address)<p class="small text-muted mb-1">{{ $hotel->address }}</p>@endif
                            <p class="small text-muted mb-0">
                                @if($hotel->phone)<span>ĐT: {{ $hotel->phone }}</span>@endif
                                @if($hotel->email)<span class="ms-2">Email: {{ $hotel->email }}</span>@endif
                            </p>
                        </div>
                        <div class="text-md-end small">
                            <span class="badge bg-dark">Hóa đơn thanh toán lưu trú</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3 p-md-4">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Khách hàng &amp; đặt phòng</h6>
                    <div class="row g-3 small">
                        <div class="col-md-6">
                            <p class="mb-1"><span class="text-muted">Khách:</span> <strong>{{ $invoice->booking?->user?->full_name ?? '—' }}</strong></p>
                            <p class="mb-1"><span class="text-muted">Email:</span> {{ $invoice->booking?->user?->email ?? '—' }}</p>
                            <p class="mb-0"><span class="text-muted">SĐT:</span> {{ $invoice->booking?->user?->phone ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><span class="text-muted">Nhận / trả:</span>
                                <strong>{{ $invoice->booking?->check_in?->format('d/m/Y') ?? '—' }}</strong>
                                → <strong>{{ $invoice->booking?->check_out?->format('d/m/Y') ?? '—' }}</strong>
                            </p>
                            <p class="mb-1"><span class="text-muted">PTTT đơn:</span> {{ $invoice->booking?->payment_method ?? '—' }}</p>
                            <p class="mb-0"><span class="text-muted">Thanh toán:</span>
                                @if($lp)
                                    <span class="badge bg-secondary">{{ $lp->status }}</span>
                                    @if($lp->transaction_id)<code class="small ms-1">{{ $lp->transaction_id }}</code>@endif
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-md-4">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Chi tiết &amp; tổng hợp (một bảng)</h6>
                    <p class="small text-muted mb-2">Khi bạn <strong>lưu dịch vụ / phụ phí</strong> trên đơn đặt, hóa đơn (nếu đã có) được <strong>cập nhật tự động</strong> theo đơn.</p>
                    <div class="table-responsive rounded-2 border">
                        @include('admin.invoices.partials.items-table', ['invoice' => $invoice, 'withSummaryFooter' => true])
                    </div>
                    @if($invoice->notes)
                        <p class="small text-muted border-top pt-3 mt-3 mb-0"><strong>Ghi chú:</strong> {{ $invoice->notes }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Thanh toán hóa đơn</h6>
                    <p class="small text-muted mb-3">Tổng tiền xem ở <strong>cuối bảng bên trái</strong>.</p>
                    <p class="small mb-3">
                        <span class="text-muted">Còn phải thu:</span><br>
                        <span class="fs-5 fw-bold">{{ number_format((float) $invoice->remaining_amount, 2, ',', '.') }} ₫</span>
                    </p>

                    @if((float) $invoice->remaining_amount > 0.009)
                        <form action="{{ route('admin.invoices.markAsPaid', $invoice) }}" method="POST" class="mt-3 border-top pt-3">
                            @csrf
                            <label class="form-label">Ghi nhận thanh toán (VNĐ)</label>
                            <input type="number" name="amount" class="form-control form-control-sm mb-2" min="0.01" max="{{ number_format((float) $invoice->remaining_amount, 2, '.', '') }}" step="0.01" value="{{ old('amount', number_format((float) $invoice->remaining_amount, 2, '.', '')) }}" required>
                            <button type="submit" class="btn btn-success btn-sm w-100 rounded-2">Cập nhật đã thu</button>
                        </form>
                    @endif
                </div>
            </div>

            @if(auth()->user()?->isAdmin())
            <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST" class="mt-3" onsubmit="return confirm('Xóa hóa đơn này?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-2">Xóa hóa đơn</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
