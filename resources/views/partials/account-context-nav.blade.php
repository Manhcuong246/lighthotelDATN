{{--
  Điều hướng trong khu vực tài khoản (bổ sung cho navbar/footer layout.app).
  @param string $current Một trong: profile | bookings | refund
--}}
@php
    $current = $current ?? 'profile';
@endphp
<div class="lh-account-context border-bottom pb-3 mb-4">
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb small mb-2">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            @if($current === 'bookings')
                <li class="breadcrumb-item active" aria-current="page">Lịch sử đặt phòng</li>
            @elseif($current === 'refund')
                <li class="breadcrumb-item"><a href="{{ route('account.bookings') }}">Lịch sử đặt phòng</a></li>
                @isset($booking)
                    <li class="breadcrumb-item"><a href="{{ route('bookings.show', $booking) }}">Đơn #{{ $booking->id }}</a></li>
                @endisset
                <li class="breadcrumb-item active" aria-current="page">Yêu cầu hoàn tiền</li>
            @else
                <li class="breadcrumb-item active" aria-current="page">Tài khoản</li>
            @endif
        </ol>
    </nav>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="small text-muted me-1 d-none d-sm-inline">Điều hướng nhanh:</span>
        <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="bi bi-house-door me-1" aria-hidden="true"></i>Trang chủ
        </a>
        <a href="{{ route('account.bookings') }}" class="btn btn-sm rounded-pill {{ $current === 'bookings' ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Đơn của tôi
        </a>
        <a href="{{ route('account.profile') }}" class="btn btn-sm rounded-pill {{ $current === 'profile' ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="bi bi-person-badge me-1" aria-hidden="true"></i>Tài khoản
        </a>
    </div>
</div>
