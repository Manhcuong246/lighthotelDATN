@extends('layouts.app')

@section('title', 'Đăng nhập')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="h4 mb-3 text-center">Đăng nhập</h2>
                    <p class="text-muted text-center mb-4 small">
                        Đăng nhập để quản lý đặt phòng và thông tin tài khoản.
                    </p>

                    <form method="POST" action="{{ route('login.submit') }}">
                                            @csrf
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="you@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 small">
                            <div>
                                <input type="checkbox" id="remember" name="remember" class="form-check-input me-1">
                                <label for="remember" class="form-check-label">Ghi nhớ đăng nhập</label>
                            </div>
                            <a href="#" class="text-decoration-none">Quên mật khẩu?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Đăng nhập
                        </button>
                    </form>

                    <p class="text-center mt-4 small text-muted">
                        Chưa có tài khoản?
                        <a href="{{ route('register') }}" class="text-decoration-none">Đăng ký ngay</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection


