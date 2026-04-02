@extends('layouts.admin')

@section('title', 'Chi tiết thanh toán #' . $payment->id)

@section('content')
<div class="container-fluid admin-page px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Chi tiết thanh toán #{{ $payment->id }}</h1>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Quay lại</a>
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
                            <label class="form-label fw-bold">Mã thanh toán</label>
                            <p class="form-control-plaintext">{{ $payment->id }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Trạng thái</label>
                            <p class="form-control-plaintext">
                                @if($payment->status === 'pending')
                                    <span class="badge bg-warning text-dark">Chờ thanh toán</span>
                                @elseif($payment->status === 'paid')
                                    <span class="badge bg-success">Đã thanh toán</span>
                                @elseif($payment->status === 'failed')
                                    <span class="badge bg-danger">Thất bại</span>
                                @else
                                    <span class="badge bg-secondary">{{ $payment->status }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Số tiền</label>
                            <p class="form-control-plaintext h5 text-success fw-bold">{{ number_format($payment->amount, 0, ',', '.') }} VNĐ</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phương thức thanh toán</label>
                            <p class="form-control-plaintext">
                                @if($payment->method === 'credit_card')
                                    Thẻ tín dụng
                                @elseif($payment->method === 'bank_transfer')
                                    Chuyển khoản ngân hàng
                                @elseif($payment->method === 'cash')
                                    Tiền mặt
                                @elseif($payment->method === 'vnpay')
                                    <span class="badge bg-dark">VNPay</span>
                                @else
                                    {{ $payment->method }}
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($payment->refund_penalty_amount !== null || $payment->refund_eligible_amount !== null)
                    <div class="row mb-3 small">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phí hủy (hệ thống)</label>
                            <p class="form-control-plaintext mb-0">{{ number_format((float) ($payment->refund_penalty_amount ?? 0), 0, ',', '.') }} VNĐ</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Số tiền hoàn dự kiến</label>
                            <p class="form-control-plaintext mb-0 text-primary fw-semibold">{{ number_format((float) ($payment->refund_eligible_amount ?? 0), 0, ',', '.') }} VNĐ</p>
                        </div>
                    </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ngày thanh toán</label>
                            <p class="form-control-plaintext">
                                {{ $payment->paid_at
                                    ? (is_string($payment->paid_at)
                                        ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i:s')
                                        : $payment->paid_at->format('d/m/Y H:i:s'))
                                    : '—' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cập nhật lần cuối</label>
                            <p class="form-control-plaintext">{{ $payment->updated_at ? (is_string($payment->updated_at) ? \Carbon\Carbon::parse($payment->updated_at)->format('d/m/Y H:i:s') : $payment->updated_at->format('d/m/Y H:i:s')) : '—' }}</p>
                        </div>
                    </div>
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
                    <p class="mb-2">
                        <strong>Trạng thái đơn:</strong>
                        @php $bst = $payment->booking->status; @endphp
                        @if($bst === 'cancellation_pending')
                            <span class="badge bg-primary">Chờ xử lý hủy</span>
                        @elseif($bst === 'pending')<span class="badge bg-warning text-dark">Chờ xác nhận</span>
                        @elseif($bst === 'confirmed')<span class="badge bg-info">Đã xác nhận</span>
                        @elseif($bst === 'cancelled')<span class="badge bg-secondary">Đã hủy</span>
                        @elseif($bst === 'refunded')<span class="badge bg-success">Đã hoàn tiền</span>
                        @elseif($bst === 'completed')<span class="badge bg-success">Hoàn thành</span>
                        @else<span class="badge bg-secondary">{{ $bst }}</span>
                        @endif
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
                        <strong>Phòng (chính):</strong> {{ $payment->booking->room->name }}
                    </p>
                    @endif

                    @if($payment->booking->rooms && $payment->booking->rooms->isNotEmpty())
                    <p class="mb-2">
                        <strong>Tất cả phòng:</strong><br>
                        @foreach($payment->booking->rooms as $r)
                            <span class="d-block">{{ $r->name }}</span>
                        @endforeach
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

    @if(($payment->refund_status ?? \App\Models\Payment::REFUND_NONE) !== \App\Models\Payment::REFUND_NONE)
    <div class="row">
        <div class="col-12">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Hoàn tiền sau hủy đơn</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        <strong>Luồng nghiệp vụ:</strong> Khách hủy đơn đã thanh toán → điền chủ tài khoản, số TK (có thể đính kèm ảnh mã QR) → khách sạn chuyển khoản hoàn tiền → tải ảnh chứng từ để khách đối chiếu trên tài khoản.
                    </p>
                    <p class="mb-2">
                        <strong>Trạng thái hoàn tiền:</strong>
                        @if($payment->refund_status === 'awaiting_user_info')
                            <span class="badge bg-warning text-dark">Chờ khách gửi thông tin TK nhận tiền</span>
                        @elseif($payment->refund_status === 'pending_admin')
                            <span class="badge bg-primary">Chờ khách sạn hoàn tiền &amp; gửi chứng từ</span>
                        @elseif($payment->refund_status === 'completed')
                            <span class="badge bg-success">Đã hoàn tất (đã có chứng từ)</span>
                        @elseif($payment->refund_status === 'rejected')
                            <span class="badge bg-secondary">Từ chối hoàn tiền</span>
                        @else
                            <span class="badge bg-secondary">{{ $payment->refund_status }}</span>
                        @endif
                    </p>
                    @if($payment->refund_requested_at)
                    <p class="small text-muted">Yêu cầu hoàn tiền: {{ \Carbon\Carbon::parse($payment->refund_requested_at)->format('d/m/Y H:i') }}</p>
                    @endif

                    @if(in_array($payment->refund_status, ['pending_admin', 'completed', 'rejected'], true))
                    <hr>
                    <h6 class="fw-bold">Thông tin nhận hoàn tiền (do khách cung cấp)</h6>
                    <p class="mb-1"><span class="text-muted">Chủ tài khoản:</span> {{ $payment->refund_account_name ?? '—' }}</p>
                    <p class="mb-1"><span class="text-muted">Số tài khoản:</span> {{ $payment->refund_account_number ?? '—' }}</p>
                    @if($payment->refund_user_note)
                    <p class="mb-2"><span class="text-muted">Ghi chú của khách:</span> {{ $payment->refund_user_note }}</p>
                    @endif
                    @if($payment->refund_qr_path)
                    <p class="mb-2">
                        <span class="text-muted d-block mb-1">Ảnh mã QR (nếu có):</span>
                        <a href="{{ asset('storage/'.$payment->refund_qr_path) }}" target="_blank" rel="noopener">
                            <img src="{{ asset('storage/'.$payment->refund_qr_path) }}" alt="QR" class="img-thumbnail" style="max-width: 200px;">
                        </a>
                    </p>
                    @endif
                    @endif

                    @if($payment->refund_status === 'pending_admin')
                    <hr>
                    <h6 class="fw-bold">Gửi chứng từ hoàn tiền cho khách</h6>
                    <p class="small text-muted">Chụp ảnh biên lai/chuyển khoản hoàn tiền và tải lên. Khách sẽ thấy trên chi tiết đặt phòng.</p>
                    <form action="{{ route('admin.payments.refundProof', $payment) }}" method="POST" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Ảnh chứng từ <span class="text-danger">*</span></label>
                            <input type="file" name="refund_proof" class="form-control @error('refund_proof') is-invalid @enderror" accept="image/*" required>
                            @error('refund_proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú hiển thị cho khách</label>
                            <textarea name="refund_admin_note" class="form-control @error('refund_admin_note') is-invalid @enderror" rows="2" placeholder="Ví dụ: đã chuyển khoản lúc...">{{ old('refund_admin_note') }}</textarea>
                            @error('refund_admin_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Lưu chứng từ &amp; hoàn tất hoàn tiền</button>
                    </form>
                    @endif

                    @if($payment->refund_status === 'completed' && $payment->refund_proof_path)
                    <hr>
                    <h6 class="fw-bold">Chứng từ đã gửi cho khách</h6>
                    <a href="{{ asset('storage/'.$payment->refund_proof_path) }}" target="_blank" rel="noopener">
                        <img src="{{ asset('storage/'.$payment->refund_proof_path) }}" alt="Chứng từ hoàn tiền" class="img-fluid img-thumbnail" style="max-width: 360px;">
                    </a>
                    @if($payment->refund_admin_note)
                    <p class="mt-2 mb-0"><span class="text-muted">Ghi chú:</span> {{ $payment->refund_admin_note }}</p>
                    @endif
                    @if($payment->refund_completed_at)
                    <p class="small text-muted mt-2">Hoàn tất lúc: {{ \Carbon\Carbon::parse($payment->refund_completed_at)->format('d/m/Y H:i') }}</p>
                    @endif
                    @endif

                    @if($payment->refund_status === 'awaiting_user_info')
                    <div class="alert alert-warning mb-0 mt-3">
                        Chờ khách điền form trên trang <strong>Chi tiết đặt phòng</strong> (tài khoản khách).
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
