@extends('layouts.app')

@section('title', 'Đăng ký')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="h4 mb-3 text-center">Tạo tài khoản mới</h2>
                    <p class="text-muted text-center mb-4 small">
                        Đăng ký để nhận ưu đãi độc quyền và quản lý đặt phòng nhanh chóng.
                    </p>

                    <form method="POST" action="{{ route('register.submit') }}">
                        @csrf
                        @if($errors->any())
                            <div class="alert alert-danger py-2 small">
                                <ul class="mb-0 list-unstyled">@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và tên</label>
                                <input type="text" class="form-control @error('full_name') is-invalid @enderror" name="full_name" value="{{ old('full_name') }}" placeholder="Nguyễn Văn A" required>
                                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" name="phone" value="{{ old('phone') }}" placeholder="09xx xxx xxx">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="••••••••" required>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" name="password_confirmation" placeholder="••••••••" required>
                            </div>
                        </div>
                        <div class="form-check mb-3 small">
                            <input class="form-check-input" type="checkbox" id="terms">
                            <label class="form-check-label" for="terms">
                                Tôi đồng ý với các điều khoản sử dụng và chính sách bảo mật.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Đăng ký
                        </button>
                    </form>

                    <p class="text-center mt-4 small text-muted">
                        Đã có tài khoản?
                        <a href="{{ route('login') }}" class="text-decoration-none">Đăng nhập</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection


