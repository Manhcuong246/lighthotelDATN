@extends('layouts.app')

@section('title', 'Hủy Booking #' . $booking->id)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-times-circle me-2"></i>
                        Xác nhận hủy booking
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Booking Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Thông tin booking</h6>
                            <p class="mb-1"><strong>Mã booking:</strong> #{{ $booking->id }}</p>
                            <p class="mb-1"><strong>Phòng:</strong> {{ $booking->room?->name }}</p>
                            <p class="mb-1"><strong>Loại phòng:</strong> {{ $booking->room?->roomType?->name }}</p>
                            <p class="mb-1"><strong>Ngày nhận phòng:</strong> {{ \Carbon\Carbon::parse($booking->check_in_date)->format('d/m/Y H:i') }}</p>
                            <p class="mb-1"><strong>Ngày trả phòng:</strong> {{ \Carbon\Carbon::parse($booking->check_out_date)->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Thông tin thanh toán</h6>
                            <p class="mb-1"><strong>Tổng tiền:</strong> <span class="text-primary">{{ number_format($booking->total_price, 0, ',', '.') }} ₫</span></p>
                            <p class="mb-1"><strong>Trạng thái:</strong> 
                                <span class="badge bg-{{ $booking->status === 'booked' ? 'success' : 'warning' }}">
                                    {{ $booking->status === 'booked' ? 'Đã đặt' : 'Khác' }}
                                </span>
                            </p>
                            <p class="mb-1"><strong>Thanh toán:</strong> 
                                <span class="badge bg-{{ $booking->payment_status === 'paid' ? 'info' : 'secondary' }}">
                                    {{ $booking->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Cancellation Policy -->
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            Chính sách hủy phòng
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Thời gian hiện tại:</strong> {{ $policy['current_time'] }}</p>
                                <p class="mb-2"><strong>Thời gian nhận phòng:</strong> {{ $policy['check_in_time'] }}</p>
                                <p class="mb-2"><strong>Còn lại:</strong> {{ $policy['hours_until_check_in'] }} giờ</p>
                            </div>
                            <div class="col-md-6">
                                <div class="policy-details">
                                    <p class="mb-2"><strong>Chính sách áp dụng:</strong></p>
                                    <p class="text-muted small">{{ $policy['policy_text'] }}</p>
                                    
                                    @if($policy['can_cancel'])
                                        <div class="mt-3 p-3 bg-light rounded">
                                            <h6 class="text-success">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Số tiền được hoàn:
                                            </h6>
                                            <h4 class="text-success">
                                                {{ number_format($policy['refund_amount'], 0, ',', '.') }} ₫
                                                <small class="text-muted">({{ $policy['refund_percentage'] }}%)</small>
                                            </h4>
                                        </div>
                                    @else
                                        <div class="mt-3 p-3 bg-warning bg-opacity-10 border border-warning rounded">
                                            <h6 class="text-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Không hoàn tiền
                                            </h6>
                                            <p class="text-warning small mb-0">{{ $policy['policy_text'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Form -->
                    @if($policy['can_cancel'])
                        <form id="cancelForm" method="POST" action="{{ route('bookings.cancel', $booking->id) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="reason" class="form-label">
                                    <strong>Lý do hủy (tùy chọn):</strong>
                                </label>
                                <textarea 
                                    class="form-control" 
                                    id="reason" 
                                    name="reason" 
                                    rows="3" 
                                    placeholder="Vui lòng nhập lý do hủy booking..."
                                    maxlength="500"
                                ></textarea>
                                <div class="form-text">Tối đa 500 ký tự</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Quay lại
                                </a>
                                <button type="submit" class="btn btn-danger" id="cancelBtn">
                                    <i class="fas fa-times me-2"></i>
                                    Xác nhận hủy booking
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center">
                            <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Quay lại chi tiết booking
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Đang xử lý...</span>
                </div>
                <h5>Đang xử lý yêu cầu hủy...</h5>
                <p class="text-muted">Vui lòng đợi trong giây lát.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cancelForm = document.getElementById('cancelForm');
    const cancelBtn = document.getElementById('cancelBtn');
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

    if (cancelForm) {
        cancelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading modal
            loadingModal.show();
            
            // Disable button
            cancelBtn.disabled = true;
            cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
            
            // Submit via AJAX
            fetch(cancelForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    reason: document.getElementById('reason').value
                })
            })
            .then(response => response.json())
            .then(data => {
                loadingModal.hide();
                
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Hủy booking thành công!',
                        html: `
                            <p>${data.message}</p>
                            <p><strong>Số tiền hoàn:</strong> ${number_format(data.refund_amount, 0, ',', '.')} ₫</p>
                        `,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = data.redirect_url;
                        }
                    });
                } else {
                    // Show error
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: data.message,
                        confirmButtonColor: '#d33'
                    });
                    
                    // Reset button
                    cancelBtn.disabled = false;
                    cancelBtn.innerHTML = '<i class="fas fa-times me-2"></i>Xác nhận hủy booking';
                }
            })
            .catch(error => {
                loadingModal.hide();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi hệ thống!',
                    text: 'Đã có lỗi xảy ra. Vui lòng thử lại.',
                    confirmButtonColor: '#d33'
                });
                
                // Reset button
                cancelBtn.disabled = false;
                cancelBtn.innerHTML = '<i class="fas fa-times me-2"></i>Xác nhận hủy booking';
            });
        });
    }
});

// Helper function for number formatting
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(/[^0-9+-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (thousands_sep) {
        var re = /(-?\d+)(\d{3})/;
        while (re.test(s[0])) {
            s[0] = s[0].replace(re, '$1' + thousands_sep + '$2');
        }
    }
    if ((dec || 0) && s.length > 1) {
        s[1] = s[1] || '';
        s[1] = (dec + s[1]).substr(0, prec + 1);
    } else if (prec) {
        s.push(dec + Array(prec + 1).join('0'));
    }
    return s.join(dec);
}
</script>
@endsection
