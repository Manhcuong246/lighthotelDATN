@extends('layouts.app')

@section('title', 'Chi tiết Booking #' . $booking->id)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check me-2"></i>
                        Chi tiết Booking #{{ $booking->id }}
                    </h5>
                    <div>
                        <span class="badge bg-{{ $booking->status === 'booked' ? 'success' : ($booking->status === 'cancelled' ? 'danger' : 'warning') }}">
                            {{ $booking->status === 'booked' ? 'Đã đặt' : ($booking->status === 'cancelled' ? 'Đã hủy' : 'Khác') }}
                        </span>
                        <span class="badge bg-{{ $booking->payment_status === 'paid' ? 'info' : 'secondary' }}">
                            {{ $booking->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Booking Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Thông tin đặt phòng</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <p class="text-uppercase small fw-bold text-muted mb-1">👤 Khách hàng</p>
                                    <p class="mb-0 fw-bold">{{ $booking->user?->full_name ?? '—' }}</p>
                                    <small class="text-muted">{{ $booking->user?->email ?? '—' }}</small>
                                </div>
                                <div class="col-md-3">
                                    <p class="text-uppercase small fw-bold text-muted mb-1">📅 Check-in</p>
                                    <p class="mb-0 fw-bold">{{ \Carbon\Carbon::parse($booking->check_in_date ?? $booking->check_in)->format('d/m/Y H:i') }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="text-uppercase small fw-bold text-muted mb-1">📅 Check-out</p>
                                    <p class="mb-0 fw-bold">{{ \Carbon\Carbon::parse($booking->check_out_date ?? $booking->check_out)->format('d/m/Y H:i') }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="text-uppercase small fw-bold text-muted mb-1">👥 Số khách</p>
                                    <span class="badge bg-secondary px-2 py-1">{{ $booking->guests ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Thông tin thanh toán</h6>
                            <div class="bg-light p-3 rounded">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tổng tiền phòng:</span>
                                    <strong>{{ number_format($booking->total_price, 0, ',', '.') }} ₫</strong>
                                </div>
                                @if($booking->discount_amount > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Giảm giá:</span>
                                    <strong class="text-success">-{{ number_format($booking->discount_amount, 0, ',', '.') }} ₫</strong>
                                </div>
                                @endif
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold">Thành tiền:</span>
                                    <strong class="text-primary fs-5">{{ number_format($booking->total_price - ($booking->discount_amount ?? 0), 0, ',', '.') }} ₫</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Room Information -->
                    @if($booking->rooms)
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Thông tin phòng</h6>
                        <div class="row">
                            @foreach($booking->rooms as $room)
                            <div class="col-md-6 mb-3">
                                <div class="card border">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $room->name }}</h6>
                                        <p class="text-muted small">{{ $room->roomType?->name }}</p>
                                        <div class="d-flex justify-content-between">
                                            <span>Giá/đêm:</span>
                                            <strong>{{ number_format($room->pivot->price_per_night ?? 0, 0, ',', '.') }} ₫</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Cancellation Information -->
                    @if($booking->status === 'cancelled')
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">
                            <i class="fas fa-times-circle me-2"></i>
                            Thông tin hủy booking
                        </h6>
                        <p class="mb-2"><strong>Thời gian hủy:</strong> {{ \Carbon\Carbon::parse($booking->cancelled_at)->format('d/m/Y H:i') }}</p>
                        <p class="mb-0"><strong>Lý do:</strong> {{ $booking->cancellation_reason ?? 'Không có' }}</p>
                        
                        @if($booking->refundLogs->isNotEmpty())
                            <div class="mt-3">
                                <strong>Thông tin hoàn tiền:</strong>
                                @foreach($booking->refundLogs as $refundLog)
                                <div class="mt-2 p-2 bg-light rounded">
                                    <div class="d-flex justify-content-between">
                                        <span>Số tiền hoàn:</span>
                                        <strong class="text-success">{{ $refundLog->formatted_refund_amount }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Loại hoàn:</span>
                                        <strong>{{ $refundLog->refund_type_label }}</strong>
                                    </div>
                                    @if($refundLog->reason)
                                    <p class="mb-0 mt-2"><small>Lý do: {{ $refundLog->reason }}</small></p>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        @if($booking->status === 'booked')
                            <a href="{{ route('bookings.cancel', $booking->id) }}" class="btn btn-danger me-2">
                                <i class="fas fa-times me-2"></i>
                                Hủy Booking
                            </a>
                        @endif
                        
                        <a href="{{ route('home') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Quay lại trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancellation Policy Modal -->
<div class="modal fade" id="cancellationPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chính sách hủy phòng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="policy-content">
                    <div class="policy-item mb-3">
                        <h6 class="text-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Hủy trước hơn 24 giờ
                        </h6>
                        <p class="mb-0">Hoàn lại 100% số tiền đặt phòng.</p>
                    </div>
                    <div class="policy-item mb-3">
                        <h6 class="text-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Hủy trong vòng 24 giờ
                        </h6>
                        <p class="mb-0">Hoàn lại 50% số tiền đặt phòng.</p>
                    </div>
                    <div class="policy-item">
                        <h6 class="text-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            Hủy sau thời gian nhận phòng
                        </h6>
                        <p class="mb-0">Không hoàn tiền.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
// Show cancellation policy modal
document.addEventListener('DOMContentLoaded', function() {
    const policyBtn = document.createElement('button');
    policyBtn.className = 'btn btn-sm btn-outline-info me-2';
    policyBtn.innerHTML = '<i class="fas fa-info-circle me-1"></i>Chính sách hủy';
    policyBtn.setAttribute('data-bs-toggle', 'modal');
    policyBtn.setAttribute('data-bs-target', '#cancellationPolicyModal');
    
    // Add button to header if booking is still active
    const cancelBtn = document.querySelector('a[href*="cancel"]');
    if (cancelBtn) {
        cancelBtn.parentNode.insertBefore(policyBtn, cancelBtn);
    }
});
</script>
@endsection
