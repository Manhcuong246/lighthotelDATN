@extends('layouts.admin')

@section('title', 'Chi tiết thanh toán #' . $payment->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Chi tiết thanh toán #{{ $payment->id }}</h1>
        <div class="d-flex flex-wrap gap-2">
            @if($payment->booking_id)
                <a href="{{ route('admin.bookings.show', $payment->booking_id) }}#payment-booking-settings" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil-square me-1"></i>Sửa trên đơn đặt phòng
                </a>
            @endif
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-admin-icon" title="Quay lại"><i class="bi bi-arrow-left"></i></a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin thanh toán</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mã giao dịch</label>
                            <p class="form-control-plaintext">{{ $payment->transaction_id ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày tạo</label>
                            <p class="form-control-plaintext">{{ $payment->created_at ? (is_string($payment->created_at) ? \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i:s') : $payment->created_at->format('d/m/Y H:i:s')) : '—' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <p class="form-control-plaintext">
                                @if($payment->status === 'pending')
                                    <span class="badge bg-warning text-dark">Chờ thanh toán</span>
                                @elseif($payment->status === 'paid')
                                    <span class="badge bg-success">Đã thanh toán</span>
                                @elseif($payment->status === 'failed')
                                    <span class="badge bg-danger">Thất bại</span>
                                @elseif($payment->status === 'refunded')
                                    <span class="badge bg-info text-dark">Đã hoàn tiền</span>
                                @else
                                    <span class="badge bg-secondary">{{ $payment->status }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày thanh toán</label>
                            <p class="form-control-plaintext">
                                {{ $payment->paid_at
                                    ? (is_string($payment->paid_at)
                                        ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i:s')
                                        : $payment->paid_at->format('d/m/Y H:i:s'))
                                    : '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Số tiền</label>
                            <p class="form-control-plaintext h5 text-success fw-bold">{{ number_format($payment->amount, 0, ',', '.') }} VNĐ</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phương thức thanh toán</label>
                            <p class="form-control-plaintext">
                                @if($payment->method === 'credit_card')
                                    <span class="badge bg-info">Thẻ tín dụng</span>
                                @elseif($payment->method === 'bank_transfer')
                                    <span class="badge bg-primary">Chuyển khoản ngân hàng</span>
                                @elseif($payment->method === 'cash')
                                    <span class="badge bg-secondary">Tiền mặt</span>
                                @elseif($payment->method === 'vnpay')
                                    <span class="badge bg-dark">VNPay</span>
                                @else
                                    {{ $payment->method }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cập nhật lần cuối</label>
                            <p class="form-control-plaintext">{{ $payment->updated_at ? (is_string($payment->updated_at) ? \Carbon\Carbon::parse($payment->updated_at)->format('d/m/Y H:i:s') : $payment->updated_at->format('d/m/Y H:i:s')) : '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mã thanh toán</label>
                            <p class="form-control-plaintext"><code>#{{ $payment->id }}</code></p>
                        </div>
                    </div>

                    @if($payment->notes)
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Ghi chú</label>
                            <p class="form-control-plaintext">{{ $payment->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($payment->booking)
        <div class="col-lg-4">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin đặt phòng</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Mã đặt phòng:</strong> 
                        <a href="{{ route('admin.bookings.show', $payment->booking) }}">#{{ $payment->booking->id }}</a>
                    </p>
                    
                    @if($payment->booking->user)
                    <p class="mb-2">
                        <strong>Khách hàng:</strong><br>
                        {{ $payment->booking->user->full_name }}<br>
                        <small class="text-muted">{{ $payment->booking->user->email }}</small>
                    </p>
                    @endif

                    @if($payment->booking->room)
                    <p class="mb-2">
                        <strong>Phòng:</strong> {{ $payment->booking->room->name }}
                    </p>
                    @endif

                    @if($payment->booking->check_in && $payment->booking->check_out)
                    <p class="mb-2">
                        <strong>Ngày nhận/trả:</strong><br>
                        {{ (is_string($payment->booking->check_in) ? \Carbon\Carbon::parse($payment->booking->check_in)->format('d/m/Y') : $payment->booking->check_in->format('d/m/Y')) }} - {{ (is_string($payment->booking->check_out) ? \Carbon\Carbon::parse($payment->booking->check_out)->format('d/m/Y') : $payment->booking->check_out->format('d/m/Y')) }}
                    </p>
                    @endif

                    @if($payment->booking->total_price)
                    <p class="mb-0">
                        <strong>Tổng tiền:</strong><br>
                        <span class="h6 text-success">{{ number_format($payment->booking->total_price, 0, ',', '.') }} VNĐ</span>
                    </p>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
