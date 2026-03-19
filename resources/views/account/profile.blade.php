@extends('layouts.app')

@section('title', 'Thông tin cá nhân')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h2 class="mb-4">Thông tin cá nhân</h2>

        {{-- Thông tin cá nhân --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-md-5">
                <h5 class="card-title mb-4">Hồ sơ</h5>
                <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label class="form-label">Ảnh đại diện</label>
                        <div class="d-flex align-items-center gap-3">
                            @php
                                $avatarUrl = $user->avatar_url
                                    ? (str_starts_with($user->avatar_url, 'http') ? $user->avatar_url : asset('storage/' . $user->avatar_url))
                                    : null;
                            @endphp
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="Avatar" class="rounded-circle border" style="width:80px;height:80px;object-fit:cover;" id="avatarPreview">
                            @else
                                <div class="rounded-circle border d-flex align-items-center justify-content-center bg-light" style="width:80px;height:80px;font-size:2rem;font-weight:600;color:#94a3b8;" id="avatarPreview">
                                    {{ strtoupper(mb_substr($user->full_name ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <input type="file" name="avatar" class="form-control form-control-sm" accept="image/*" id="avatarInput">
                                <small class="text-muted">JPG, PNG, GIF, WebP. Tối đa 2MB</small>
                            </div>
                        </div>
                        @error('avatar') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
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
                </form>
            </div>
        </div>

        {{-- Đổi mật khẩu --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <h5 class="card-title mb-4">Đổi mật khẩu</h5>
                <form method="POST" action="{{ route('account.profile.update.password') }}">
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
                </form>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('home') }}" class="btn btn-outline-secondary">Quay lại</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('avatarInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function() {
            const preview = document.getElementById('avatarPreview');
            if (preview.tagName === 'IMG') {
                preview.src = reader.result;
            } else {
                const img = document.createElement('img');
                img.src = reader.result;
                img.alt = 'Avatar';
                img.className = 'rounded-circle border';
                img.style.cssText = 'width:80px;height:80px;object-fit:cover;';
                img.id = 'avatarPreview';
                preview.parentNode.replaceChild(img, preview);
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
@endsection
