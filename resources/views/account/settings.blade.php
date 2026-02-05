@extends('layouts.app')

@section('title', 'Cài đặt tài khoản')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-5">
        <h2 class="mb-4">Cài đặt tài khoản</h2>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <h5 class="mb-3">Đổi mật khẩu</h5>
                <form method="POST" action="{{ route('account.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required>
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" class="form-control" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Quay lại</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
