@extends('layouts.app')

@section('title', 'Đánh giá — ' . $room->name)

@section('content')
@php
    $backUrl = route('bookings.show', ['booking' => $prefillBookingId]);
    $reviewReturnUrl = $backUrl;
@endphp
<div class="mb-4">
    <a href="{{ $backUrl }}" class="btn btn-sm btn-outline-secondary text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Quay lại đơn
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-5">
    <div class="card-header bg-light py-3 border-0">
        <h5 class="mb-0 fw-bold">Đánh giá trải nghiệm</h5>
        <div class="small text-muted mt-1">
            <i class="bi bi-door-open text-primary me-1"></i>{{ $room->name }}
            @if($room->roomType)
                <span class="text-muted">·</span> {{ $room->roomType->name }}
            @endif
        </div>
    </div>
    <div class="card-body p-4">
        <p class="small text-muted mb-4">
            Mỗi lượt lưu trú (một đơn đã thanh toán và đã trả phòng) được gửi <strong>một đánh giá</strong> cho phòng vật lý này.
            Bạn có thể đánh giá lại khi có lưu trú mới.
        </p>
        @include('rooms.partials.review-write-form', [
            'roomEntity' => $room,
            'reviewReturnUrl' => $reviewReturnUrl,
            'reviewableBookings' => $reviewable,
            'prefillBookingId' => $prefillBookingId,
        ])
    </div>
</div>
@endsection
