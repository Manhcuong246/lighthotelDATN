@extends('layouts.admin')

@section('title', 'Hướng dẫn thanh toán - Đơn #' . $booking->id)

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0">🏦 Hướng Dẫn Thanh Toán</h4>
                    <p class="text-muted mb-0">Đơn đặt phòng #{{ $booking->id }}</p>
                </div>
                <a href="{{ route('admin.bookings.create-multi') }}" class="btn btn-outline-secondary btn-sm btn-admin-icon" title="Quay lại"><i class="bi bi-arrow-left"></i></a>
            </div>

            @if(session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            <!-- Payment Status Alert -->
            @if(!empty($vnpayPayUrl))
            <div class="alert alert-info">
                <div class="d-flex align-items-start">
                    <i class="bi bi-credit-card-2-front fs-4 me-3"></i>
                    <div>
                        <h6 class="alert-heading mb-1">VNPay — chờ khách hoàn tất thanh toán</h6>
                        <p class="mb-0 small">Đơn đã được tạo và giữ chỗ theo luồng admin. Phiên cổng VNPay (~{{ (int) config('vnpay.transaction_expire_minutes', 15) }} phút) bắt đầu khi khách <strong>mở link thanh toán</strong> (QR hoặc nút).</p>
                    </div>
                </div>
            </div>

            {{-- QR mở trang VNPay — khách quét bằng điện thoại --}}
            <div class="card shadow-lg border-0 mb-4 border-info" style="border-width: 2px !important;">
                <div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #084298 100%);">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-qr-code-scan me-2"></i>Quét mã thanh toán VNPay</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted mb-3">Khách mở camera và quét mã — điện thoại sẽ mở trang thanh toán an toàn (link có chữ ký). Hoặc sao chép link gửi Zalo cho khách.</p>
                    <div class="d-inline-block p-3 bg-white border rounded-3 shadow-sm">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&amp;format=png&amp;margin=8&amp;data={{ rawurlencode($vnpayPayUrl) }}"
                             alt="QR thanh toán VNPay"
                             width="280"
                             height="280"
                             class="img-fluid d-block mx-auto"
                             style="max-width: 280px;">
                    </div>
                    <p class="small text-muted mt-3 mb-2">Số tiền: <strong class="text-danger">{{ number_format($booking->total_price) }}đ</strong></p>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                        <button type="button" class="btn btn-primary btn-sm" onclick="copyToClipboard(@json($vnpayPayUrl))">
                            <i class="bi bi-link-45deg me-1"></i>Sao chép link thanh toán
                        </button>
                        <a href="{{ $vnpayPayUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Mở trang thanh toán
                        </a>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-light border mb-4">
                <p class="mb-2 fw-semibold">Không có link VNPay kèm đơn này.</p>
                <p class="mb-0 small text-muted">
                    Luồng đặt trên website chỉ ghi nhận đơn và giữ chỗ sau khi VNPay thành công.
                    Đối với tiền mặt / xác nhận thủ công khác: dùng mục cập nhật trạng thái và thanh toán trên <a href="{{ route('admin.bookings.show', $booking) }}" class="fw-semibold">trang chi tiết đơn</a>.
                </p>
            </div>
            @endif

            <!-- Customer Info -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold">👤 Thông Tin Khách Hàng</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Khách:</strong> {{ $booking->user->full_name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>Email:</strong> {{ $booking->user->email ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><strong>SĐT:</strong> {{ $booking->user->phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Summary -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold">📋 Chi Tiết Đơn</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Phòng</th>
                                <th>Số đêm</th>
                                <th class="text-end">Giá/đêm</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->bookingRooms as $bookingRoom)
                            <tr>
                                <td>Phòng {{ $bookingRoom->room->room_number ?? 'N/A' }}</td>
                                <td>{{ $bookingRoom->nights }} đêm</td>
                                <td class="text-end">{{ number_format($bookingRoom->price_per_night) }}đ</td>
                                <td class="text-end">{{ number_format($bookingRoom->subtotal) }}đ</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-group-divider">
                            @if($booking->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="text-end"><strong>Giảm giá:</strong></td>
                                <td class="text-end fs-6 fw-semibold">@include('shared.partials.money-customer-flow', ['amount' => -1 * (float) $booking->discount_amount])</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end fs-5"><strong>Tổng cộng (phải thanh toán):</strong></td>
                                <td class="text-end fs-5 fw-bold">@include('shared.partials.money-debt-due', ['amount' => (float) $booking->total_price, 'class' => 'fs-5'])</td>
                            </tr>
                            @php
                                $calculatedTotal = $booking->bookingRooms->sum('subtotal') - ($booking->discount_amount ?? 0);
                            @endphp
                            @if($calculatedTotal != $booking->total_price)
                            <tr>
                                <td colspan="4" class="text-center text-warning">
                                    <small><i class="bi bi-exclamation-triangle"></i> 
                                    Tính toán lại: {{ number_format($calculatedTotal) }}đ</small>
                                </td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Đã sao chép: ' + text);
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>
@endsection
