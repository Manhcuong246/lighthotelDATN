@extends('layouts.app')

@section('title', 'Chi tiết đặt phòng #' . $booking->id)

@section('content')
@php
    $paidRecorded = $booking->isPaymentRecordedPaid();
    $pendingExpired = $booking->status === 'pending' && ! $paidRecorded && $booking->isPendingDisplayExpired();
@endphp
<div class="mb-4">
    <a href="{{ route('account.bookings') }}" class="btn btn-sm btn-outline-secondary text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Quay lại lịch sử
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold">Đơn đặt phòng #{{ $booking->id }}</h5>
        <span class="badge {{ $pendingExpired ? 'bg-secondary' : ($booking->status === 'pending' ? 'bg-warning text-dark' : ($booking->status === 'confirmed' ? 'bg-info' : ($booking->status === 'completed' ? 'bg-success' : 'bg-secondary'))) }} px-3 py-2">
            @if($pendingExpired) Hết hạn
            @elseif($booking->status === 'pending' && $paidRecorded) Đã thanh toán
            @elseif($booking->status === 'pending') Chờ thanh toán
            @elseif($booking->status === 'confirmed') Đã thanh toán
            @elseif($booking->status === 'completed') Hoàn thành
            @elseif($booking->status === 'cancelled') Đã hủy
            @elseif($booking->status === 'cancel_requested') Đang chờ hoàn tiền
            @elseif($booking->status === 'refunded') Đã hoàn tiền
            @else {{ $booking->status }}
            @endif
        </span>
    </div>
    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small fw-semibold mb-2">Phòng đã đặt</h6>
                <ul class="list-unstyled mb-0">
                    @if($booking->bookingRooms->isNotEmpty())
                        @foreach($booking->bookingRooms as $br)
                            @php $room = $br->room; @endphp
                            <li class="mb-3">
                                <p class="mb-1 fw-semibold">
                                    <i class="bi bi-door-open text-primary me-2"></i>
                                    @if($room)
                                        <a href="{{ route('rooms.show', $room) }}" class="link-dark text-decoration-underline">{{ $room->name }}</a>
                                    @else
                                        <span class="text-dark">{{ $br->guestFacingLine() }}</span>
                                    @endif
                                </p>
                                <div class="ms-4">
                                    <small class="text-muted d-block">
                                        @if($br->roomType)
                                            <a href="{{ route('home', ['room_type' => $br->roomType->id]) }}" class="text-decoration-none">{{ $br->roomType->name }}</a>
                                        @else
                                            —
                                        @endif
                                        — {{ number_format($br->price_per_night, 0, ',', '.') }} ₫/đêm
                                    </small>
                                    <div class="small mt-1">
                                        @if($room)
                                            <a href="{{ route('rooms.show', $room) }}" class="text-primary text-decoration-none">Chi tiết phòng</a>
                                            @if($br->roomType)
                                                <span class="text-muted">·</span>
                                            @endif
                                        @endif
                                        @if($br->roomType)
                                            <a href="{{ route('home', ['room_type' => $br->roomType->id]) }}" class="text-primary text-decoration-none">Loại phòng</a>
                                        @endif
                                    </div>
                                    @unless($room)
                                        <small class="text-muted d-block mt-1">Số phòng cụ thể sẽ do lễ tân bố trí và thông báo khi nhận phòng.</small>
                                    @endunless
                                    <small class="text-info d-block mt-1">
                                        <i class="bi bi-people me-1"></i>
                                        {{ $br->adults }} Người lớn,
                                        @if($br->children_6_11 > 0){{ $br->children_6_11 }} Trẻ 6–11t @endif
                                        @if($br->children_0_5 > 0){{ $br->children_0_5 }} Trẻ 0–5t @endif
                                        @if($br->children_6_11 + $br->children_0_5 === 0) 0 Trẻ em @endif
                                    </small>
                                    @auth
                                        @if($room)
                                            @php
                                                $uidBr = (int) auth()->id();
                                                $ridBr = (int) $room->id;
                                                $canSubmitReviewRoom = \App\Models\Booking::userCanSubmitRoomReview($uidBr, $ridBr);
                                                $reviewedRoom = \App\Models\Review::userHasReviewedRoom($uidBr, $ridBr);
                                            @endphp
                                            @if($canSubmitReviewRoom)
                                                <a href="{{ route('rooms.show', $room) }}#write-review" class="btn btn-sm btn-success mt-2 rounded-pill">
                                                    <i class="bi bi-star me-1"></i>
                                                    Viết đánh giá (loại: {{ $room->roomType->name ?? $room->type ?? 'phòng này' }})
                                                </a>
                                            @elseif($reviewedRoom)
                                                <span class="d-block small text-muted mt-2"><i class="bi bi-check2-circle me-1"></i>Đã đánh giá phòng này (mỗi tài khoản một lần / phòng).</span>
                                            @endif
                                        @endif
                                    @endauth
                                </div>
                            </li>
                        @endforeach
                    @else
                    @forelse($booking->rooms as $room)
                    <li class="mb-3">
                        <p class="mb-1 fw-semibold">
                            <i class="bi bi-door-open text-primary me-2"></i>
                            <a href="{{ route('rooms.show', $room) }}" class="link-dark text-decoration-underline">{{ $room->name }}</a>
                        </p>
                        <div class="ms-4">
                            <small class="text-muted d-block">
                                @if($room->roomType)
                                    <a href="{{ route('home', ['room_type' => $room->room_type_id]) }}" class="text-decoration-none">{{ $room->roomType->name }}</a>
                                @else
                                    {{ $room->type ?? '—' }}
                                @endif
                                — {{ number_format($room->pivot->price_per_night, 0, ',', '.') }} ₫/đêm
                            </small>
                            <div class="small mt-1">
                                <a href="{{ route('rooms.show', $room) }}" class="text-primary text-decoration-none">Chi tiết phòng</a>
                                @if($room->roomType)
                                    <span class="text-muted">·</span>
                                    <a href="{{ route('home', ['room_type' => $room->room_type_id]) }}" class="text-primary text-decoration-none">Loại phòng</a>
                                @endif
                            </div>
                            <small class="text-info d-block mt-1">
                                <i class="bi bi-people me-1"></i>
                                {{ $room->pivot->adults }} Người lớn,
                                @if($room->pivot->children_6_11 > 0){{ $room->pivot->children_6_11 }} Trẻ 6–11t @endif
                                @if($room->pivot->children_0_5 > 0){{ $room->pivot->children_0_5 }} Trẻ 0–5t @endif
                                @if($room->pivot->children_6_11 + $room->pivot->children_0_5 === 0) 0 Trẻ em @endif
                            </small>
                            @auth
                                @php
                                    $uidBr = (int) auth()->id();
                                    $ridBr = (int) $room->id;
                                    $canSubmitReviewRoom = \App\Models\Booking::userCanSubmitRoomReview($uidBr, $ridBr);
                                    $reviewedRoom = \App\Models\Review::userHasReviewedRoom($uidBr, $ridBr);
                                @endphp
                                @if($canSubmitReviewRoom)
                                    <a href="{{ route('rooms.show', $room) }}#write-review" class="btn btn-sm btn-success mt-2 rounded-pill">
                                        <i class="bi bi-star me-1"></i>
                                        Viết đánh giá (loại: {{ $room->roomType->name ?? $room->type ?? 'phòng này' }})
                                    </a>
                                @elseif($reviewedRoom)
                                    <span class="d-block small text-muted mt-2"><i class="bi bi-check2-circle me-1"></i>Đã đánh giá phòng này (mỗi tài khoản một lần / phòng).</span>
                                @endif
                            @endauth
                        </div>
                    </li>
                    @empty
                        @if($booking->room)
                            @php $r = $booking->room; @endphp
                            <li class="mb-3">
                                <p class="mb-1 fw-semibold">
                                    <i class="bi bi-door-open text-primary me-2"></i>
                                    <a href="{{ route('rooms.show', $r) }}" class="link-dark text-decoration-underline">{{ $r->name }}</a>
                                </p>
                                <div class="ms-4">
                                    <small class="text-muted d-block">
                                        @if($r->roomType)
                                            <a href="{{ route('home', ['room_type' => $r->room_type_id]) }}" class="text-decoration-none">{{ $r->roomType->name }}</a>
                                        @else
                                            {{ $r->type ?? '—' }}
                                        @endif
                                        @if($r->base_price)
                                            — {{ number_format($r->base_price, 0, ',', '.') }} ₫/đêm
                                        @endif
                                    </small>
                                    <div class="small mt-1">
                                        <a href="{{ route('rooms.show', $r) }}" class="text-primary text-decoration-none">Chi tiết phòng</a>
                                        @if($r->roomType)
                                            <span class="text-muted">·</span>
                                            <a href="{{ route('home', ['room_type' => $r->room_type_id]) }}" class="text-primary text-decoration-none">Loại phòng</a>
                                        @endif
                                    </div>
                                    @if($booking->guests)
                                        <small class="text-info d-block mt-1">
                                            <i class="bi bi-people me-1"></i>{{ $booking->guests }} khách
                                        </small>
                                    @endif
                                    @auth
                                        @php
                                            $uidLg = (int) auth()->id();
                                            $ridLg = (int) $r->id;
                                            $canSubmitLegacy = \App\Models\Booking::userCanSubmitRoomReview($uidLg, $ridLg);
                                            $reviewedLegacy = \App\Models\Review::userHasReviewedRoom($uidLg, $ridLg);
                                        @endphp
                                        @if($canSubmitLegacy)
                                            <a href="{{ route('rooms.show', $r) }}#write-review" class="btn btn-sm btn-success mt-2 rounded-pill">
                                                <i class="bi bi-star me-1"></i>
                                                Viết đánh giá (loại: {{ $r->roomType->name ?? $r->type ?? 'phòng này' }})
                                            </a>
                                        @elseif($reviewedLegacy)
                                            <span class="d-block small text-muted mt-2"><i class="bi bi-check2-circle me-1"></i>Đã đánh giá phòng này (mỗi tài khoản một lần / phòng).</span>
                                        @endif
                                    @endauth
                                </div>
                            </li>
                        @else
                            <li class="text-muted">Không có thông tin phòng cho đơn này.</li>
                        @endif
                    @endforelse
                    @endif
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
                        <span class="{{ $pendingExpired ? 'text-secondary' : 'text-warning' }} fw-bold"><i class="bi bi-credit-card-2-front me-2"></i>{{ $pendingExpired ? 'Hết hạn' : 'Chưa thanh toán' }}</span>
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

        @if($booking->surcharges->isNotEmpty())
        <div class="mt-4">
            <h6 class="text-muted text-uppercase small fw-semibold mb-2">Phụ thu &amp; phí phát sinh (sau khi nhận phòng)</h6>
            <p class="small text-muted mb-2">Các khoản ghi nhận khi lưu trú (ví dụ dịch vụ dùng thêm không có trong đơn đặt ban đầu).</p>
            <div class="table-responsive rounded-2 border border-warning border-opacity-50">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Mô tả</th>
                            <th class="text-end pe-3">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($booking->surcharges as $sc)
                        <tr>
                            <td class="ps-3">
                                @if($sc->service)
                                    <span class="fw-semibold">{{ $sc->service->name }}</span>
                                    @if((int) ($sc->quantity ?? 1) > 1)
                                        <span class="text-muted small">×{{ (int) $sc->quantity }}</span>
                                    @endif
                                    <br><span class="small text-muted">{{ $sc->reason }}</span>
                                @else
                                    {{ $sc->reason }}
                                @endif
                            </td>
                            <td class="text-end pe-3 fw-semibold text-danger">+ {{ number_format($sc->amount, 0, ',', '.') }} ₫</td>
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
            @elseif($booking->payment->status === 'pending') <span class="{{ $pendingExpired ? 'text-secondary' : 'text-warning' }}">{{ $pendingExpired ? 'Hết hạn' : 'Chờ thanh toán' }}</span>
            @elseif($booking->payment->status === 'failed') <span class="text-danger">Đã hủy / Thất bại</span>
            @elseif($booking->payment->status === 'refunded') <span class="text-info fw-semibold">Đã hoàn tiền</span>
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

        @if($booking->status === 'cancelled' && $booking->cancel_reason)
        <hr class="my-4">
        <div class="alert alert-danger">
            <h6 class="alert-heading">
                <i class="bi bi-x-circle me-2"></i>Đơn đã bị hủy
            </h6>
            <p class="mb-0"><strong>Lý do hủy:</strong> {{ $booking->cancel_reason }}</p>
            @if($booking->cancelled_at)
            <p class="mb-0 mt-2"><small class="text-muted">Thời gian hủy: {{ $booking->cancelled_at->format('d/m/Y H:i') }}</small></p>
            @endif
        </div>
        @endif

        <div class="mt-4 pt-3 border-top">
            <small class="text-muted">Đặt lúc: {{ $booking->created_at?->format('d/m/Y H:i') ?? '—' }}</small>
        </div>
    </div>
</div>

@if($booking->refundRequest)
<div class="card border-0 shadow-sm rounded-3 mt-4 overflow-hidden border-start border-4 {{ $booking->refundRequest->status === 'refunded' ? 'border-success' : ($booking->refundRequest->status === 'rejected' ? 'border-danger' : 'border-warning') }}">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i>Thông tin hoàn tiền</h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-md-4">
                <p class="mb-1 small text-muted text-uppercase fw-semibold">Tài khoản nhận</p>
                <p class="mb-0 fw-bold">{{ $booking->refundRequest->account_name }}</p>
                <p class="mb-0 small">{{ $booking->refundRequest->bank_name }} - {{ $booking->refundRequest->account_number }}</p>
            </div>
            <div class="col-md-4 text-md-center">
                <p class="mb-1 small text-muted text-uppercase fw-semibold">Số tiền hoàn ({{ $booking->refundRequest->refund_percentage }}%)</p>
                <p class="mb-0 fw-bold text-success fs-5">{{ number_format($booking->refundRequest->refund_amount, 0, ',', '.') }} ₫</p>
            </div>
            <div class="col-md-4 text-md-end">
                <p class="mb-1 small text-muted text-uppercase fw-semibold">Trạng thái yêu cầu</p>
                <span class="badge {{ $booking->refundRequest->status === 'refunded' ? 'bg-success' : ($booking->refundRequest->status === 'rejected' ? 'bg-danger' : 'bg-warning') }} px-3 py-2 rounded-pill">
                    @if($booking->refundRequest->status === 'pending_refund') Đang xử lý
                    @elseif($booking->refundRequest->status === 'refunded') Đã hoàn tiền
                    @elseif($booking->refundRequest->status === 'rejected') Từ chối hoàn tiền
                    @endif
                </span>
            </div>
            
            @if($booking->refundRequest->admin_note)
            <div class="col-12 mt-3">
                <div class="p-3 rounded-3 bg-light border-start border-3 {{ $booking->refundRequest->status === 'refunded' ? 'border-success' : 'border-danger' }}">
                    <p class="mb-1 small fw-bold">Phản hồi từ Admin:</p>
                    <p class="mb-0 italic">{{ $booking->refundRequest->admin_note }}</p>
                </div>
            </div>
            @endif

            @if($booking->refundRequest && $booking->refundRequest->refund_proof_image)
            <div class="col-12 mt-3">
                <p class="mb-2 small fw-bold">Minh chứng chuyển khoản:</p>
                @if($booking->refundRequest->refundProofFileExists())
                    @php $proofUrl = $booking->refundRequest->refundProofPublicUrl(); @endphp
                    <div class="text-center">
                        <a href="{{ $proofUrl }}" target="_blank" rel="noopener" class="d-inline-block">
                            <img src="{{ $proofUrl }}" class="img-fluid rounded shadow-sm border" style="max-height: 300px;" alt="Minh chứng hoàn tiền">
                        </a>
                        <p class="mt-2 small text-muted">
                            <i class="bi bi-image me-1"></i>
                            Click để xem ảnh lớn hơn
                        </p>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Ảnh minh chứng không tồn tại trên máy chủ</strong>
                        <p class="mb-0 small text-muted">Đường dẫn lưu: {{ $booking->refundRequest->refund_proof_image }}</p>
                        <p class="mb-0 small">Vui lòng liên hệ admin nếu bạn vừa tải lên nhưng vẫn báo lỗi.</p>
                    </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endif

@php
    $bookingPrimaryRoom = $booking->rooms->first() ?? $booking->room;
    $canViewInvoice = \App\Support\BookingInvoiceViewData::customerCanView($booking);
@endphp
<div class="mt-3 d-flex flex-wrap gap-2">
    @if($canViewInvoice)
    <a href="{{ route('account.bookings.invoice', $booking) }}" class="btn btn-outline-dark btn-sm" target="_blank" rel="noopener">
        <i class="bi bi-receipt-cutoff me-1"></i>Hóa đơn
    </a>
    @endif
    @if($bookingPrimaryRoom)
    <a href="{{ route('rooms.show', $bookingPrimaryRoom) }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-eye me-1"></i>Xem phòng đã đặt
    </a>
    @if($bookingPrimaryRoom->roomType)
    <a href="{{ route('home', ['room_type' => $bookingPrimaryRoom->room_type_id]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-grid me-1"></i>Xem loại phòng
    </a>
    @endif
    @endif
    
    @if($booking->status === 'confirmed')
        <a href="{{ route('account.bookings.refund', $booking) }}" class="btn btn-danger btn-sm px-4">
            <i class="bi bi-wallet2 me-1"></i>Hủy &amp; hoàn tiền
        </a>
    @elseif($booking->status === 'pending')
        <a href="{{ route('bookings.cancel', $booking) }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-x-circle me-1"></i>Hủy &amp; hoàn tiền (theo chính sách)
        </a>
    @endif
</div>
@endsection
