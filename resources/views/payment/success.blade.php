@extends('layouts.app')

@section('title', 'Thanh toán thành công')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center py-5">
                <div class="text-success mb-3">
                    <i class="bi bi-check-circle display-4"></i>
                </div>
                <h3 class="card-title mb-3">Thanh toán thành công</h3>
                @if(session('success'))
                <p class="text-muted mb-4">{{ session('success') }}</p>
                @else
                <p class="text-muted mb-4">
                    Đơn đặt phòng của bạn đã được xác nhận. Chúng tôi sẽ liên hệ với bạn qua email để hoàn tất thủ tục.
                </p>
                @endif
                @if(isset($booking))
                <div class="bg-light rounded p-3 mb-4 text-start">
                    <p class="mb-1"><strong>Mã đơn:</strong> #{{ $booking->id }}</p>
                    <p class="mb-1"><strong>Phòng:</strong> {{ $booking->room?->name ?? '—' }}</p>
                    <p class="mb-1"><strong>Nhận phòng:</strong> {{ $booking->check_in?->format('d/m/Y') ?? '—' }}</p>
                    <p class="mb-0"><strong>Trả phòng:</strong> {{ $booking->check_out?->format('d/m/Y') ?? '—' }}</p>
                </div>
                @endif
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    @auth
                    <a href="{{ route('account.bookings') }}" class="btn btn-primary">Xem lịch đặt phòng</a>
                    @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Đăng nhập để xem đơn hàng</a>
                    @endauth
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Về trang chủ</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
