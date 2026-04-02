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
        <span class="badge {{ $booking->status === 'pending' ? 'bg-warning text-dark' : ($booking->status === 'confirmed' ? 'bg-info' : ($booking->status === 'completed' ? 'bg-success' : 'bg-secondary')) }} px-3 py-2">
            @if($booking->status === 'pending') Chờ xác nhận
            @elseif($booking->status === 'confirmed') Đã xác nhận
            @elseif($booking->status === 'completed') Hoàn thành
            @elseif($booking->status === 'cancelled') Đã hủy
            @else {{ $booking->status }}
            @endif
        </span>
    </div>
    <div class="card-body p-4">
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

<div class="mt-3 d-flex gap-2">
    @if($booking->rooms->isNotEmpty())
    <a href="{{ route('rooms.show', $booking->rooms->first()) }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-eye me-1"></i>Xem phòng
    </a>
    @endif
    <form action="{{ route('bookings.cancel.post', $booking) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đặt phòng này?');">
        @csrf
        <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-x-circle me-1"></i>Hủy đơn
        </button>
    </form>
</div>
@endsection
