@extends('layouts.app')

@section('title', 'Xác nhận hủy đặt phòng #' . $booking->id)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-danger bg-gradient text-white py-4 rounded-top-4">
                    <h4 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Xác nhận hủy đặt phòng
                    </h4>
                </div>
                <div class="card-body p-4">
                    {{-- Thông tin booking --}}
                    <div class="mb-4">
                        <h5 class="text-muted mb-3">Thông tin đặt phòng</h5>
                        <div class="bg-light p-3 rounded-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted">Mã đặt phòng</small>
                                    <p class="fw-bold mb-0">#{{ $booking->id }}</p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Ngày nhận phòng</small>
                                    <p class="fw-bold mb-0">{{ $preview['check_in_date'] }}</p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Tổng tiền</small>
                                    <p class="fw-bold text-primary mb-0">
                                        {{ number_format($preview['total_price'], 0, ',', '.') }} VNĐ
                                    </p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Thời gian còn lại</small>
                                    <p class="fw-bold {{ $preview['hours_before_checkin'] > 24 ? 'text-success' : 'text-warning' }} mb-0">
                                        {{ ceil($preview['hours_before_checkin']) }} giờ
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Thông tin hoàn tiền --}}
                    <div class="mb-4">
                        <h5 class="text-muted mb-3">Thông tin hoàn tiền</h5>
                        <div class="alert 
                            {{ $preview['refund_preview']['type'] === 'full' ? 'alert-success' : '' }}
                            {{ $preview['refund_preview']['type'] === 'partial' ? 'alert-warning' : '' }}
                            {{ $preview['refund_preview']['type'] === 'none' ? 'alert-danger' : '' }}
                            border-0">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    @if($preview['refund_preview']['type'] === 'full')
                                        <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                                    @elseif($preview['refund_preview']['type'] === 'partial')
                                        <i class="bi bi-exclamation-circle-fill fs-4 text-warning"></i>
                                    @else
                                        <i class="bi bi-x-circle-fill fs-4 text-danger"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="alert-heading fw-bold mb-2">
                                        @if($preview['refund_preview']['type'] === 'full')
                                            Hoàn tiền 100%
                                        @elseif($preview['refund_preview']['type'] === 'partial')
                                            Hoàn tiền 50%
                                        @else
                                            Không hoàn tiền
                                        @endif
                                    </h6>
                                    <p class="mb-0">
                                        {{ $preview['refund_preview']['message'] }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Chi tiết hoàn tiền --}}
                        <div class="table-responsive">
                            <table class="table table-borderless table-sm">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Tổng tiền đặt phòng:</td>
                                        <td class="text-end fw-bold">
                                            {{ number_format($preview['total_price'], 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Số tiền hoàn lại:</td>
                                        <td class="text-end fw-bold 
                                            {{ $preview['refund_preview']['amount'] > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($preview['refund_preview']['amount'], 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                    @if($preview['refund_preview']['amount'] < $preview['total_price'])
                                    <tr class="border-top">
                                        <td class="text-muted">Phí hủy phòng:</td>
                                        <td class="text-end fw-bold text-danger">
                                            {{ number_format($preview['total_price'] - $preview['refund_preview']['amount'], 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Form xác nhận --}}
                    <form action="{{ route('bookings.cancel.post', $booking) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="reason" class="form-label">Lý do hủy (tùy chọn)</label>
                            <textarea name="reason" id="reason" rows="3" class="form-control"
                                placeholder="Vui lòng cho biết lý do bạn muốn hủy đặt phòng..."></textarea>
                        </div>

                        <div class="alert alert-light border">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Sau khi hủy, đơn đặt phòng sẽ không thể khôi phục. 
                                Số tiền hoàn lại sẽ được chuyển về tài khoản của bạn trong vòng 5-7 ngày làm việc.
                            </small>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-secondary flex-fill">
                                <i class="bi bi-arrow-left me-1"></i>
                                Quay lại
                            </a>
                            <button type="submit" class="btn btn-danger flex-fill"
                                onclick="return confirm('Bạn có chắc chắn muốn hủy đặt phòng này?')">
                                <i class="bi bi-x-circle me-1"></i>
                                Xác nhận hủy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
