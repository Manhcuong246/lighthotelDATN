@extends('layouts.app')

@section('title', 'Chi tiết đặt phòng #' . $booking->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('account.bookings') }}" class="btn btn-sm btn-outline-secondary text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Quay lại lịch sử
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold">Đơn đặt phòng #{{ $booking->id }}</h5>
        @php
            $st = $booking->status;
            $badgeClass = match ($st) {
                'pending' => 'bg-warning text-dark',
                'confirmed' => 'bg-info',
                'cancellation_pending' => 'bg-primary',
                'cancelled' => 'bg-secondary',
                'refunded' => 'bg-success',
                'completed' => 'bg-success',
                default => 'bg-secondary',
            };
            $badgeLabel = match ($st) {
                'pending' => 'Chờ xác nhận',
                'confirmed' => 'Đã xác nhận / Đã thanh toán (nếu có)',
                'cancellation_pending' => 'Chờ xử lý hủy',
                'cancelled' => 'Đã hủy',
                'refunded' => 'Đã hoàn tiền',
                'completed' => 'Hoàn thành',
                default => $st,
            };
        @endphp
        <span class="badge {{ $badgeClass }} px-3 py-2">{{ $badgeLabel }}</span>
    </div>
    <div class="card-body p-4">
        @if($booking->status === 'cancellation_pending')
            <div class="alert alert-primary mb-4">
                <i class="bi bi-hourglass-split me-2"></i>
                Yêu cầu hủy đang chờ khách sạn xác nhận. Phòng vẫn được giữ trong lịch cho đến khi có quyết định.
                @if($booking->cancellation_reason)
                    <div class="small mt-2 mb-0"><span class="text-muted">Lý do bạn gửi:</span> {{ $booking->cancellation_reason }}</div>
                @endif
            </div>
        @endif

        @if(in_array($booking->status, ['pending', 'confirmed'], true) && $booking->payment?->status === 'paid' && !($nonRefundable ?? false))
            <div class="alert alert-light border mb-4 small">
                <strong>Chính sách hủy (ước tính):</strong>
                Còn khoảng <strong>{{ max(0, (int) round($policy['hours_until'])) }}</strong> giờ đến giờ nhận phòng.
                @if($policy['tier'] === 'free')
                    Hủy miễn phí — hoàn toàn bộ số đã thanh toán.
                @elseif($policy['tier'] === 'mid')
                    Phí hủy dự kiến {{ $policy['penalty_percent'] }}% (≈ {{ number_format($policy['penalty_amount'], 0, ',', '.') }} ₫). Hoàn lại ≈ {{ number_format($policy['eligible_amount'], 0, ',', '.') }} ₫.
                @else
                    Phí hủy dự kiến {{ $policy['penalty_percent'] }}% (≈ {{ number_format($policy['penalty_amount'], 0, ',', '.') }} ₫). Hoàn lại ≈ {{ number_format($policy['eligible_amount'], 0, ',', '.') }} ₫.
                @endif
                Nếu có phí, hệ thống có thể gửi yêu cầu hủy để <strong>admin duyệt</strong> trước khi giải phóng phòng.
            </div>
        @endif

        @if(($nonRefundable ?? false) && in_array($booking->status, ['pending', 'confirmed'], true))
            <div class="alert alert-warning small">
                Gói phòng <strong>không hoàn tiền (non-refundable)</strong> — bạn không thể tự hủy trên web. Vui lòng liên hệ khách sạn.
            </div>
        @endif

        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small fw-semibold mb-2">Phòng đã đặt</h6>
                <ul class="list-unstyled mb-0">
                    @foreach($booking->rooms as $room)
                    <li class="mb-3">
                        <p class="mb-0 fw-semibold">
                            <i class="bi bi-door-open text-primary me-2"></i>{{ $room->name }}
                        </p>
                        <div class="ms-4">
                            <small class="text-muted d-block">{{ $room->roomType->name ?? '' }} - {{ number_format($room->pivot->price_per_night, 0, ',', '.') }} ₫/đêm</small>
                            <small class="text-info d-block">
                                <i class="bi bi-people me-1"></i>
                                {{ $room->pivot->adults }} Người lớn,
                                {{ $room->pivot->children_0_5 + $room->pivot->children_6_11 }} Trẻ em
                            </small>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small fw-semibold mb-2">Thời gian</h6>
                <p class="mb-1">
                    <i class="bi bi-calendar-check me-2 text-muted"></i>Nhận phòng: {{ $booking->check_in?->format('d/m/Y') ?? '—' }}
                </p>
                <p class="mb-0">
                    <i class="bi bi-calendar-x me-2 text-muted"></i>Trả phòng: {{ $booking->check_out?->format('d/m/Y') ?? '—' }}
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small fw-semibold mb-2">Trạng thái thanh toán</h6>
                <p class="mb-0">
                    @if($booking->payment?->status === 'paid')
                        <span class="text-success fw-bold"><i class="bi bi-credit-card-2-front me-2"></i>Đã thanh toán</span>
                    @else
                        <span class="text-warning fw-bold"><i class="bi bi-credit-card-2-front me-2"></i>Chưa thanh toán</span>
                    @endif
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small fw-semibold mb-2">Tổng tiền</h6>
                <p class="mb-0 fw-bold text-success fs-5">{{ $booking->total_price ? number_format($booking->total_price, 0, ',', '.') . ' ₫' : '—' }}</p>
            </div>
        </div>

        @if($booking->bookingServices->isNotEmpty())
        <div class="mt-4">
            <h6 class="text-muted text-uppercase small fw-semibold mb-2">Dịch vụ kèm theo</h6>
            <div class="table-responsive rounded-2 border">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Dịch vụ</th>
                            <th class="text-end">SL</th>
                            <th class="text-end pe-3">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($booking->bookingServices as $bs)
                        @php
                            $line = (float) $bs->price * (int) $bs->quantity;
                        @endphp
                        <tr>
                            <td class="ps-3">{{ $bs->service?->name ?? 'Dịch vụ #' . $bs->service_id }}</td>
                            <td class="text-end text-muted">{{ $bs->quantity }}</td>
                            <td class="text-end pe-3 fw-semibold">{{ number_format($line, 0, ',', '.') }} ₫</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($booking->payment)
        <hr class="my-4">
        <h6 class="text-muted text-uppercase small fw-semibold mb-2">Thanh toán</h6>
        <p class="mb-1">
            <span class="text-muted">Phương thức:</span>
            @if($booking->payment->method === 'bank_transfer') Chuyển khoản
            @elseif($booking->payment->method === 'vnpay') VNPay
            @elseif($booking->payment->method === 'cash') Tiền mặt
            @else {{ $booking->payment->method }}
            @endif
        </p>
        <p class="mb-1">
            <span class="text-muted">Trạng thái:</span>
            @if($booking->payment->status === 'paid') <span class="text-success fw-semibold">Đã thanh toán</span>
            @elseif($booking->payment->status === 'pending') <span class="text-warning">Chờ thanh toán</span>
            @elseif($booking->payment->status === 'failed') <span class="text-danger">Đã hủy / Thất bại</span>
            @else {{ $booking->payment->status }}
            @endif
        </p>
        @if($booking->payment->paid_at)
        <p class="mb-0 small text-muted">Thanh toán lúc: {{ \Carbon\Carbon::parse($booking->payment->paid_at)->format('d/m/Y H:i') }}</p>
        @endif
        @endif

        @if($booking->actual_check_in || $booking->actual_check_out)
        <hr class="my-4">
        <h6 class="text-muted text-uppercase small fw-semibold mb-2">Thời gian thực tế</h6>
        @if($booking->actual_check_in)
        <p class="mb-1">Check-in: {{ $booking->actual_check_in->format('d/m/Y H:i') }}</p>
        @endif
        @if($booking->actual_check_out)
        <p class="mb-0">Check-out: {{ $booking->actual_check_out->format('d/m/Y H:i') }}</p>
        @endif
        @endif

        <div class="mt-4 pt-3 border-top">
            <small class="text-muted">Đặt lúc: {{ $booking->created_at?->format('d/m/Y H:i') ?? '—' }}</small>
        </div>
    </div>
</div>

@php
    $pay = $booking->payment;
    $refundActive = $pay && $pay->status === 'paid' && ($pay->refund_status ?? \App\Models\Payment::REFUND_NONE) !== \App\Models\Payment::REFUND_NONE;
@endphp

@if($refundActive)
<div class="card border-0 shadow-sm rounded-3 mt-4" id="refund">
    <div class="card-header bg-light py-3">
        <h6 class="mb-0 fw-bold">Hoàn tiền sau khi hủy đơn</h6>
    </div>
    <div class="card-body p-4">
        <p class="small text-muted mb-3">
            Bạn đã thanh toán trước khi hủy. Khách sạn sẽ hoàn tiền qua chuyển khoản theo thông tin bạn cung cấp bên dưới (hoặc qua API cổng thanh toán nếu được tích hợp).
            <strong>Thứ tự:</strong> hủy đơn → điền chủ TK, số tài khoản (có thể đính kèm ảnh mã QR) → kế toán chuyển khoản → khách sạn đăng chứng từ để bạn đối chiếu.
        </p>
        @if($pay->refund_penalty_amount !== null || $pay->refund_eligible_amount !== null)
            <dl class="row small mb-3 pb-3 border-bottom">
                <dt class="col-sm-4">Đã thanh toán</dt>
                <dd class="col-sm-8">{{ number_format((float) $pay->amount, 0, ',', '.') }} ₫</dd>
                <dt class="col-sm-4">Phí hủy (ước tính)</dt>
                <dd class="col-sm-8">{{ number_format((float) ($pay->refund_penalty_amount ?? 0), 0, ',', '.') }} ₫</dd>
                <dt class="col-sm-4">Số tiền hoàn dự kiến</dt>
                <dd class="col-sm-8 fw-semibold text-success">{{ number_format((float) ($pay->refund_eligible_amount ?? 0), 0, ',', '.') }} ₫</dd>
            </dl>
        @endif

        @if($pay->refund_status === \App\Models\Payment::REFUND_AWAITING_USER)
            <form action="{{ route('account.bookings.refund', $booking) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Chủ tài khoản nhận tiền <span class="text-danger">*</span></label>
                        <input type="text" name="refund_account_name" value="{{ old('refund_account_name') }}" class="form-control @error('refund_account_name') is-invalid @enderror" required maxlength="150" placeholder="Họ tên đúng với ngân hàng">
                        @error('refund_account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số tài khoản <span class="text-danger">*</span></label>
                        <input type="text" name="refund_account_number" value="{{ old('refund_account_number') }}" class="form-control @error('refund_account_number') is-invalid @enderror" required maxlength="64" placeholder="Chỉ số, không dấu cách nếu có thể">
                        @error('refund_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Ảnh mã QR ngân hàng (tuỳ chọn)</label>
                        <input type="file" name="refund_qr" class="form-control @error('refund_qr') is-invalid @enderror" accept="image/*">
                        @error('refund_qr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Giúp khách sạn chuyển khoản đúng tài khoản.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Ghi chú thêm (tuỳ chọn)</label>
                        <textarea name="refund_user_note" class="form-control @error('refund_user_note') is-invalid @enderror" rows="2" maxlength="1000" placeholder="Tên ngân hàng, chi nhánh...">{{ old('refund_user_note') }}</textarea>
                        @error('refund_user_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Gửi thông tin nhận hoàn tiền</button>
            </form>
        @elseif($pay->refund_status === \App\Models\Payment::REFUND_PENDING_ADMIN)
            <div class="alert alert-info mb-3">
                <i class="bi bi-hourglass-split me-1"></i>Đã nhận thông tin của bạn. Khách sạn đang xử lý hoàn tiền và sẽ đăng ảnh chứng từ khi chuyển khoản xong.
            </div>
            <dl class="row small mb-0">
                <dt class="col-sm-4">Chủ tài khoản</dt>
                <dd class="col-sm-8">{{ $pay->refund_account_name ?? '—' }}</dd>
                <dt class="col-sm-4">Số tài khoản</dt>
                <dd class="col-sm-8">{{ $pay->refund_account_number ?? '—' }}</dd>
                @if($pay->refund_user_note)
                <dt class="col-sm-4">Ghi chú</dt>
                <dd class="col-sm-8">{{ $pay->refund_user_note }}</dd>
                @endif
                @if($pay->refund_qr_path)
                <dt class="col-sm-4">Mã QR đã gửi</dt>
                <dd class="col-sm-8">
                    <a href="{{ asset('storage/'.$pay->refund_qr_path) }}" target="_blank" rel="noopener">
                        <img src="{{ asset('storage/'.$pay->refund_qr_path) }}" alt="QR" class="img-thumbnail" style="max-width: 160px;">
                    </a>
                </dd>
                @endif
            </dl>
        @elseif($pay->refund_status === \App\Models\Payment::REFUND_COMPLETED)
            <div class="alert alert-success mb-3">
                <i class="bi bi-check-circle me-1"></i>Hoàn tiền đã được xử lý. Dưới đây là chứng từ do khách sạn cung cấp để bạn đối chiếu.
            </div>
            @if($pay->refund_proof_path)
            <p class="mb-2 fw-semibold">Ảnh chứng từ chuyển khoản</p>
            <a href="{{ asset('storage/'.$pay->refund_proof_path) }}" target="_blank" rel="noopener">
                <img src="{{ asset('storage/'.$pay->refund_proof_path) }}" alt="Chứng từ hoàn tiền" class="img-fluid rounded border" style="max-width: 100%; max-height: 420px;">
            </a>
            @endif
            @if($pay->refund_admin_note)
            <p class="mt-3 mb-0"><span class="text-muted">Ghi chú từ khách sạn:</span> {{ $pay->refund_admin_note }}</p>
            @endif
        @elseif($pay->refund_status === \App\Models\Payment::REFUND_REJECTED)
            <div class="alert alert-secondary mb-0">Yêu cầu hoàn tiền không được chấp nhận. Liên hệ khách sạn nếu bạn cần hỗ trợ.</div>
        @endif
    </div>
</div>
@endif

<div class="mt-3 d-flex flex-wrap gap-2">
    @if($booking->rooms->isNotEmpty())
    <a href="{{ route('rooms.show', $booking->rooms->first()) }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-eye me-1"></i>Xem phòng
    </a>
    @endif
    @if(in_array($booking->status, ['pending', 'confirmed'], true) && !($nonRefundable ?? false))
    <form action="{{ route('account.bookings.cancel', $booking) }}" method="POST" class="d-flex flex-column flex-sm-row align-items-start gap-2 flex-wrap" onsubmit="return confirm('Bạn có chắc chắn muốn gửi yêu cầu hủy? Đơn đã thanh toán có thể bị phí hủy theo chính sách; có thể cần admin duyệt trước khi hủy xong.');">
        @csrf
        @method('PUT')
        <input type="text" name="cancellation_reason" class="form-control form-control-sm" style="min-width:220px;max-width:420px;" placeholder="Lý do hủy (tuỳ chọn)" value="{{ old('cancellation_reason') }}" maxlength="2000">
        <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-x-circle me-1"></i>Hủy đơn
        </button>
    </form>
    @endif
</div>
@endsection
