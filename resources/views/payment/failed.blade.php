@extends('layouts.app')

@section('title', 'Thanh toán thất bại')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center py-5">
                <div class="text-danger mb-3" aria-hidden="true">
                    <i class="bi bi-x-circle display-4"></i>
                </div>
                <h3 class="card-title mb-3">Thanh toán không thành công</h3>
                <p class="text-muted mb-4">
                    Giao dịch đã bị hủy hoặc có lỗi xảy ra. Vui lòng thử lại hoặc chọn phương thức thanh toán khác.
                </p>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <a href="{{ route('home') }}#rooms-section" class="btn btn-primary">Đặt phòng lại</a>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Về trang chủ</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
