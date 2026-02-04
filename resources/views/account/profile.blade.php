@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h2 class="mb-4">Hồ sơ cá nhân</h2>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="{{ route('account.profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" class="form-control @error('full_name') is-invalid @enderror" name="full_name" value="{{ old('full_name', $user->full_name) }}" required>
                        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled>
                        <small class="text-muted">Email không thể thay đổi.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09xx xxx xxx">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Quay lại</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
