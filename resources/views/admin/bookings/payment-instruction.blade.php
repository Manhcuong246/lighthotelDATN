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
                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Quay lại đơn
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <!-- Payment Status Alert -->
            <div class="alert alert-warning">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clock-history fs-4 me-3"></i>
                    <div>
                        <h6 class="alert-heading mb-1">⏳ Đang chờ thanh toán</h6>
                        <p class="mb-0">Khách cần chuyển khoản theo thông tin bên dưới. Sau khi nhận được tiền, hãy click "Xác nhận đã nhận tiền".</p>
                    </div>
                </div>
            </div>

            <!-- Bank Transfer Info Card -->
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bank me-2"></i>Thông Tin Chuyển Khoản</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="120" class="text-muted">Ngân hàng:</td>
                                    <td class="fw-bold">{{ $hotelInfo->bank_name ?? 'Vietcombank' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Số tài khoản:</td>
                                    <td class="fw-bold fs-5 text-primary">
                                        {{ $hotelInfo->bank_account ?? '1234567890' }}
                                        <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('{{ $hotelInfo->bank_account ?? '1234567890' }}')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Chủ TK:</td>
                                    <td class="fw-bold">{{ $hotelInfo->bank_account_name ?? 'KHÁCH SẠN LIGHTHOTEL' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="120" class="text-muted">Số tiền:</td>
                                    <td class="fw-bold fs-4 text-danger">{{ number_format($booking->total_price) }}đ</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nội dung CK:</td>
                                    <td>
                                        <span class="fw-bold text-primary">BOOKING_{{ $booking->id }}</span>
                                        <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('BOOKING_{{ $booking->id }}')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- QR Code (if available) -->
                    @if($hotelInfo && $hotelInfo->bank_account)
                    <div class="text-center">
                        <p class="text-muted mb-2">Quét mã QR để chuyển khoản nhanh:</p>
                        <div class="d-inline-block p-3 bg-white border rounded">
                            <img src="https://api.vietqr.io/image/{{ $hotelInfo->bank_name ?? '970436' }}-{{ $hotelInfo->bank_account ?? '1234567890' }}-compact2.png?amount={{ $booking->total_price }}&addInfo=BOOKING_{{ $booking->id }}&accountName={{ urlencode($hotelInfo->bank_account_name ?? 'KHÁCH SẠN LIGHTHOTEL') }}"
                                 alt="QR Code" style="max-width: 250px;" class="img-fluid">
                        </div>
                    </div>
                    @endif
                </div>
            </div>

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
                                <td class="text-end text-success">-{{ number_format($booking->discount_amount) }}đ</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end fs-5"><strong>Tổng cộng:</strong></td>
                                <td class="text-end fs-5 fw-bold text-danger">{{ number_format($booking->total_price) }}đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="card shadow-lg border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-check-circle me-2"></i>Xác Nhận Thanh Toán</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1">Khi đã nhận được tiền chuyển khoản từ khách:</p>
                            <ul class="mb-0 text-muted small">
                                <li>Đơn đặt phòng sẽ được đánh dấu là "Đã thanh toán"</li>
                                <li>Tạo record thanh toán trong hệ thống</li>
                                <li>Khách có thể check-in bình thường</li>
                            </ul>
                        </div>
                        <form action="{{ route('admin.bookings.confirm-payment', $booking) }}" method="POST" class="ms-4">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Bạn có chắc đã nhận được tiền chuyển khoản?')">
                                <i class="bi bi-check-lg me-2"></i>
                                Xác nhận đã nhận tiền
                            </button>
                        </form>
                    </div>
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
