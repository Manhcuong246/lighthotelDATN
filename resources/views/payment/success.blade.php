@extends('layouts.app')

@section('title', 'Thanh toán thành công')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center py-5">
                <div class="text-success mb-3" aria-hidden="true">
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
                <div class="bg-light rounded p-4 mb-4 text-start">
                    <p class="mb-2 border-bottom pb-2"><strong>Mã đơn:</strong> <span class="text-primary">#{{ $booking->id }}</span></p>
                    
                    <div class="mb-3">
                        <strong>Các phòng đã đặt:</strong>
                        <ul class="list-unstyled mt-2 mb-0">
                            @foreach($booking->rooms as $room)
                                <li class="d-flex justify-content-between align-items-center mb-1 bg-white p-2 rounded shadow-sm">
                                    <span><i class="bi bi-door-closed me-2"></i>{{ $room->name }}</span>
                                    <span class="text-muted small">{{ number_format($room->pivot->price_per_night, 0, ',', '.') }} ₫</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <p class="mb-1"><strong>Nhận phòng:</strong> {{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}</p>
                    <p class="mb-1"><strong>Trả phòng:</strong> {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</p>
                    <p class="mb-0 fw-bold pt-2 border-top"><strong>Tổng cộng:</strong> <span class="text-success fs-5">{{ number_format($booking->total_price, 0, ',', '.') }} ₫</span></p>
                </div>
                @endif
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    @if(!empty($signedBookingViewUrl))
                        <a href="{{ $signedBookingViewUrl }}" class="btn btn-primary">Xem chi tiết đơn (không cần đăng nhập)</a>
                    @endif
                    @auth
                    @if(!auth()->user()->canAccessAdmin())
                    <a href="{{ route('account.bookings') }}" class="btn {{ !empty($signedBookingViewUrl) ? 'btn-outline-primary' : 'btn-primary' }}">Đơn của tôi (tài khoản)</a>
                    @endif
                    @else
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary">Đăng nhập / đăng ký</a>
                    @endauth
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Về trang chủ</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
