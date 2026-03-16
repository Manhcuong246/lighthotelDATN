@extends('layouts.app')

@section('title', 'Thanh toán đặt cọc')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Booking Info Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Thanh toán đặt cọc</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Quý khách cần thanh toán 30% tổng giá trị đơn đặt phòng để xác nhận.</strong>
                            <br>Số tiền còn lại sẽ thanh toán trực tiếp tại khách sạn.
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="bg-light rounded p-4 mb-4">
                        <h6 class="fw-bold mb-3">📋 Thông tin đơn đặt phòng</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Mã đơn hàng</p>
                                <p class="fw-bold">#{{ $booking->id }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Trạng thái</p>
                                <span class="badge bg-warning text-dark">{{ $booking->status }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Phòng</p>
                                <p class="fw-semibold">{{ $booking->roomType->name ?? 'N/A' }}</p>
                                @if($booking->room)
                                    <small class="text-muted">Số: {{ $booking->room->name }}</small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted small">Thời gian</p>
                                <p class="fw-semibold">{{ $booking->check_in->format('d/m/Y') }} - {{ $booking->check_out->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">💰 Chi tiết thanh toán</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Tổng giá trị đơn:</td>
                                    <td class="text-end fw-bold">{{ number_format($booking->total_price, 0, ',', '.') }} ₫</td>
                                </tr>
                                <tr class="border-top">
                                    <td class="text-muted">
                                        <strong>💳 Số tiền cần thanh toán (30%):</strong>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-primary fw-bold fs-4">{{ number_format($depositAmount, 0, ',', '.') }} ₫</span>
                                    </td>
                                </tr>
                                <tr class="border-top">
                                    <td class="text-muted">Số tiền còn lại (70%):</td>
                                    <td class="text-end fw-semibold">{{ number_format($remainingAmount, 0, ',', '.') }} ₫</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form action="{{ route('payments.process', $booking) }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Chọn phương thức thanh toán:</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="card h-100 cursor-pointer">
                                        <input type="radio" name="payment_method" value="vnpay" class="card-check-input" required>
                                        <div class="card-body text-center">
                                            <i class="bi bi-bank fs-1 text-primary mb-2"></i>
                                            <h6 class="card-title mb-0">VNPay</h6>
                                            <small class="text-muted">Chuyển khoản ngân hàng</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="card h-100 cursor-pointer">
                                        <input type="radio" name="payment_method" value="momo" class="card-check-input" required>
                                        <div class="card-body text-center">
                                            <i class="bi bi-wallet2 fs-1 text-danger mb-2"></i>
                                            <h6 class="card-title mb-0">MoMo</h6>
                                            <small class="text-muted">Ví điện tử</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="card h-100 cursor-pointer">
                                        <input type="radio" name="payment_method" value="bank_transfer" class="card-check-input" required>
                                        <div class="card-body text-center">
                                            <i class="bi bi-cash-stack fs-1 text-success mb-2"></i>
                                            <h6 class="card-title mb-0">Chuyển khoản</h6>
                                            <small class="text-muted">Trực tiếp</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Lưu ý:</strong> Đơn đặt phòng sẽ tự động hủy sau 24 giờ nếu không hoàn tất thanh toán.
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('account.bookings') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Quay lại
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-credit-card"></i> Thanh toán ngay
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cursor-pointer {
    cursor: pointer;
}
.card-check-input:checked + .card-body {
    background-color: #e7f1ff;
    border-color: #0d6efd;
}
.card:has(.card-check-input:checked) {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
@endsection
