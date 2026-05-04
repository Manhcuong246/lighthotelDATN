@extends('layouts.admin')

@section('title', 'Chi tiết hoàn tiền #' . $refundRequest->booking_id)

@section('content')
<div class="mb-4 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
        <a href="{{ route('admin.refunds.index') }}" class="btn btn-sm btn-outline-secondary btn-admin-icon me-3" title="Danh sách"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold pe-3 border-end">Chi tiết hoàn tiền #{{ $refundRequest->booking_id }}</h4>
        <span class="ms-3 badge {{ $refundRequest->status === 'pending_refund' ? 'bg-warning text-dark' : ($refundRequest->status === 'refunded' ? 'bg-success' : 'bg-danger') }}">
            @if($refundRequest->status === 'pending_refund') Đang chờ xử lý
            @elseif($refundRequest->status === 'refunded') Đã hoàn tiền
            @elseif($refundRequest->status === 'rejected') Đã từ chối
            @endif
        </span>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- User Input Column -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom align-items-center d-flex">
                <i class="bi bi-person-circle fs-5 text-primary me-2"></i>
                <h6 class="mb-0 fw-bold">Thông tin khách hàng & Tài khoản</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted small text-uppercase fw-semibold">Khách hàng</p>
                        <p class="mb-0 fw-bold">{{ $refundRequest->user->full_name }}</p>
                        <p class="mb-0 small text-muted">{{ $refundRequest->user->email }}</p>
                        <p class="mb-0 small text-muted">{{ $refundRequest->user->phone }}</p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted small text-uppercase fw-semibold">Booking liên quan</p>
                        <p class="mb-0 fw-bold"><a href="{{ route('admin.bookings.show', $refundRequest->booking_id) }}">Đơn #{{ $refundRequest->booking_id }}</a></p>
                        <p class="mb-0 small text-muted">Tổng đơn: {{ number_format($refundRequest->booking->total_price, 0, ',', '.') }} ₫</p>
                    </div>
                    
                    <div class="col-12"><hr class="my-2 border-light"></div>
                    
                    <div class="col-sm-6">
                        <p class="mb-1 text-muted small text-uppercase fw-semibold">Số tiền hoàn ({{ $refundRequest->refund_percentage }}%)</p>
                        <p class="mb-0 fw-bold text-success fs-4">{{ number_format($refundRequest->refund_amount, 0, ',', '.') }} ₫</p>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <p class="mb-1 text-muted small text-uppercase fw-semibold">Thời gian yêu cầu</p>
                        <p class="mb-0 fw-bold">{{ $refundRequest->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="col-12">
                        <div class="p-3 rounded-4 bg-light bg-opacity-50 border">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <h6 class="fw-bold small text-primary text-uppercase mb-3"><i class="bi bi-bank me-2"></i>Tài khoản nhận tiền</h6>
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5 small text-muted font-normal mb-1">Chủ tài khoản:</dt>
                                        <dd class="col-sm-7 fw-bold mb-1">{{ $refundRequest->account_name }}</dd>
                                        <dt class="col-sm-5 small text-muted font-normal mb-1">Số tài khoản:</dt>
                                        <dd class="col-sm-7 fw-bold mb-1">{{ $refundRequest->account_number }}</dd>
                                        <dt class="col-sm-5 small text-muted font-normal mb-0">Ngân hàng:</dt>
                                        <dd class="col-sm-7 fw-bold mb-0 text-primary">{{ $refundRequest->bank_name }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-5 text-center text-md-end border-start border-md-0 ps-md-4">
                                    @if($refundRequest->qr_image)
                                        <img src="{{ asset('storage/' . $refundRequest->qr_image) }}" class="img-fluid rounded border shadow-sm" style="max-height: 150px;" alt="QR Account">
                                        <p class="mb-0 mt-2 small text-muted">Mã QR đính kèm</p>
                                    @else
                                        <div class="d-flex flex-column align-items-center justify-content-center h-100 py-3">
                                            <i class="bi bi-qr-code text-muted opacity-25" style="font-size: 3rem;"></i>
                                            <p class="mb-0 small text-muted">Không có mã QR</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($refundRequest->note)
                    <div class="col-12">
                        <p class="mb-1 text-muted small text-uppercase fw-semibold">Ghi chú của khách</p>
                        <div class="p-3 rounded-3 bg-light italic border-start border-3 border-secondary">
                            {{ $refundRequest->note }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Action Column -->
    <div class="col-lg-5">
        @if($refundRequest->status === 'pending_refund')
        <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 2rem;">
            <div class="card-header bg-white py-3 border-bottom">
                <i class="bi bi-check2-circle fs-5 text-success me-2"></i>
                <h6 class="mb-0 fw-bold">Xử lý yêu cầu</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.refunds.process', $refundRequest) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label mb-2">Quyết định xử lý</label>
                        <div class="d-flex gap-3">
                            <input type="radio" class="btn-check" name="action" id="approve" value="approve" checked autocomplete="off">
                            <label class="btn btn-outline-success flex-fill border-2 py-3 d-flex align-items-center justify-content-center gap-2" for="approve" title="Chấp nhận">
                                <i class="bi bi-check2 fs-5"></i>
                                <span>Duyệt hoàn tiền</span>
                            </label>

                            <input type="radio" class="btn-check" name="action" id="reject" value="reject" autocomplete="off">
                            <label class="btn btn-outline-danger flex-fill border-2 py-3 d-flex align-items-center justify-content-center gap-2" for="reject" title="Từ chối">
                                <i class="bi bi-x-lg fs-5"></i>
                                <span>Từ chối yêu cầu</span>
                            </label>
                        </div>
                    </div>

                    <div id="proof-container" class="mb-4">
                        <label class="form-label mb-2" for="refund_proof_image">Minh chứng chuyển khoản (Bắt buộc nếu chấp nhận)</label>
                        <div class="p-3 rounded-3 bg-light border border-dashed border-2">
                            <input type="file" name="refund_proof_image" class="form-control" id="refund_proof_image">
                            <div class="form-text small mt-2">Đính kèm ảnh chụp màn hình xác nhận chuyển khoản thành công.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label mb-2" for="admin_note">Ghi chú phản hồi khách (Tùy chọn)</label>
                        <textarea name="admin_note" id="admin_note" rows="4" class="form-control p-3 bg-light border-0" placeholder="Nhập lý do từ chối hoặc lời nhắn gửi khách..."></textarea>
                    </div>

                    <div class="alert alert-warning border-0 small mb-4 bg-opacity-75 d-flex">
                        <i class="bi bi-lightbulb me-2 pt-1 text-warning"></i>
                        <span><strong>Lưu ý:</strong> Khi chấp nhận, đơn sẽ bị hủy vĩnh viễn và không thể hoàn tác.</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 shadow-sm d-flex align-items-center justify-content-center" title="Xử lý yêu cầu" onclick="return confirm('Bạn có chắc chắn muốn xử lý yêu cầu này?')">
                        <i class="bi bi-check2-circle fs-5 me-2"></i>
                        <span>Xác nhận xử lý</span>
                    </button>
                </form>
            </div>
        </div>
        @else
        <!-- Completed Status View -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <i class="bi bi-shield-check fs-5 text-primary me-2"></i>
                <h6 class="mb-0 fw-bold">Kết quả xử lý</h6>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <p class="mb-1 text-muted small text-uppercase fw-semibold">Trạng thái cuối cùng</p>
                    @if($refundRequest->status === 'refunded')
                        <div class="p-3 rounded-3 bg-success bg-opacity-10 text-success fw-bold d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                            ĐÃ HOÀN TIỀN THÀNH CÔNG
                        </div>
                    @else
                        <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger fw-bold d-flex align-items-center">
                            <i class="bi bi-x-circle-fill me-2 fs-5"></i>
                            YÊU CẦU ĐÃ BỊ TỪ CHỐI
                        </div>
                    @endif
                </div>

                @if($refundRequest->admin_note)
                <div class="mb-4">
                    <p class="mb-1 text-muted small text-uppercase fw-semibold">Ghi chú của Admin</p>
                    <div class="p-3 rounded-3 bg-light italic border-start border-3 border-primary italic">
                        {{ $refundRequest->admin_note }}
                    </div>
                </div>
                @endif

                @if($refundRequest && $refundRequest->refund_proof_image)
                <div class="mb-0">
                    <p class="mb-1 text-muted small text-uppercase fw-semibold">Minh chứng thanh toán</p>
                    @if($refundRequest->refundProofFileExists())
                        @php $proofUrl = $refundRequest->refundProofPublicUrl(); @endphp
                        <a href="{{ $proofUrl }}" target="_blank" rel="noopener" class="d-block">
                            <img src="{{ $proofUrl }}" class="img-fluid rounded border shadow-sm w-100" alt="Minh chứng hoàn tiền">
                        </a>
                        <p class="mt-2 small text-muted">
                            <i class="bi bi-image me-1"></i>
                            Click để xem ảnh lớn hơn
                        </p>
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Ảnh minh chứng không tồn tại trên máy chủ</strong>
                            <p class="mb-0 small text-muted">Đường dẫn lưu: {{ $refundRequest->refund_proof_image }}</p>
                        </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const approveRadio = document.getElementById('approve');
        const rejectRadio = document.getElementById('reject');
        const proofContainer = document.getElementById('proof-container');
        const proofInput = document.getElementById('refund_proof_image');

        function toggleProof() {
            if (approveRadio.checked) {
                proofContainer.style.display = 'block';
                proofInput.setAttribute('required', 'required');
            } else {
                proofContainer.style.display = 'none';
                proofInput.removeAttribute('required');
            }
        }

        approveRadio.addEventListener('change', toggleProof);
        rejectRadio.addEventListener('change', toggleProof);
        
        toggleProof(); // Initial check
    });
</script>
@endsection
