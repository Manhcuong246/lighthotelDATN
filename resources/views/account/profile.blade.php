@extends('layouts.app')

@section('title', 'Thông tin cá nhân')

@section('content')

<div class="container py-5">

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4" role="alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4" role="alert">{{ session('error') }}</div>
    @endif

        {{-- Navigation Tabs --}}
        <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="info-tab" data-bs-toggle="pill" data-bs-target="#info" type="button" role="tab">
                    <i class="bi bi-person me-2"></i>Thông tin cá nhân
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button" role="tab">
                    <i class="bi bi-shield-lock me-2"></i>Đổi mật khẩu
                </button>
            </li>
             @if($user->canSelfCloseAccountFromWebsite())
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="close-tab" data-bs-toggle="pill" data-bs-target="#close-account" type="button" role="tab">
                    <i class="bi bi-person-x me-2"></i>Đóng tài khoản
                </button>
            </li>
            @endif
        </ul>

        {{-- Tab Content --}}
        <div class="tab-content" id="profileTabsContent">

            {{-- Tab: Thông tin cá nhân --}}
            <div class="tab-pane fade show active" id="info" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Chỉnh sửa hồ sơ</h5>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="{{ url('/account/profile') }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')

                                    <div class="row">
                                        {{-- Form fields --}}
                                        <div class="col-md-8">

                                    {{-- Thông tin --}}
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('full_name') is-invalid @enderror" name="full_name" value="{{ old('full_name', $user->full_name) }}" required>
                                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Số điện thoại</label>
                                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09xx xxx xxx">
                                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled>
                                        <small class="text-muted">Email không thể thay đổi.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Ngày tham gia</label>
                                        <input type="text" class="form-control bg-light" value="{{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y H:i') }}" disabled>
                                    </div>

                                        </div>

                                        {{-- Avatar section --}}
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <label class="form-label d-block">Ảnh đại diện</label>
                                                @php
                                                    $avatarUrl = $user->avatar_url
                                                        ? (str_starts_with($user->avatar_url, 'http') ? $user->avatar_url : '/storage/' . $user->avatar_url)
                                                        : null;
                                                @endphp
                                                @if($avatarUrl)
                                                    <img src="{{ $avatarUrl }}" alt="Avatar" class="rounded-circle border mb-3" style="width:120px;height:120px;object-fit:cover;">
                                                @else
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width:120px;height:120px;font-size:3rem;font-weight:600;">
                                                        {{ strtoupper(mb_substr($user->full_name ?? 'U', 0, 1)) }}
                                                    </div>
                                                @endif
                                                <input type="file" name="avatar" class="form-control form-control-sm" accept="image/*" id="avatarInput">
                                                <small class="text-muted d-block mt-1">JPG, PNG, GIF, WebP</small>
                                                @error('avatar') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="{{ route('home') }}" class="btn btn-outline-secondary">Quay lại</a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-2"></i>Lưu thay đổi
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab: Đổi mật khẩu --}}
            <div class="tab-pane fade" id="password" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Đổi mật khẩu</h5>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="{{ url('/account/profile/password') }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required>
                                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="password_confirmation" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-key me-2"></i>Đổi mật khẩu
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

             @if($user->canSelfCloseAccountFromWebsite())
            <div class="tab-pane fade" id="close-account" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card border border-danger border-opacity-25 shadow-sm rounded-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="mb-0 text-danger"><i class="bi bi-exclamation-octagon me-2"></i>Đóng tài khoản</h5>
                            </div>
                            <div class="card-body p-4">
                                <p class="text-muted">Sau khi đóng, bạn sẽ bị đăng xuất. Email được giải phóng để có thể <strong>đăng ký lại</strong>. Lịch sử đơn đặt vẫn được lưu trong hệ thống theo quy định.</p>
                                <p class="text-muted small mb-4">Chỉ có thể đóng khi bạn <strong>không còn đơn</strong> nào đang xử lý (trừ đơn đã hủy hoặc đã hoàn tất).</p>
                                <form method="POST" action="{{ route('account.close') }}" onsubmit="return confirm('Bạn chắc chắn muốn đóng tài khoản? Hành động này không hoàn tác qua website.');">
                                    @csrf
                                    @method('DELETE')
                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="form-check mb-4">
                                        <input class="form-check-input @error('confirm_close') is-invalid @enderror" type="checkbox" name="confirm_close" id="confirm_close" value="1" {{ old('confirm_close') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="confirm_close">Tôi hiểu tôi sẽ không đăng nhập được nữa và đồng ý đóng tài khoản.</label>
                                        @error('confirm_close')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger">Xác nhận đóng tài khoản</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>

</div>

@push('scripts')
<script>
// Tab switching - tự động chuyển tab theo hash
function switchTab(hash) {
    if (hash === '#password') {
        const passwordTab = document.getElementById('password-tab');
        if (passwordTab) {
            passwordTab.click();
        }
    }
    if (hash === '#close-account') {
        const t = document.getElementById('close-tab');
        if (t) t.click();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Xử lý hash trong URL
    if (window.location.hash === '#password') {
        switchTab('#password');
    }
    if (window.location.hash === '#close-account') {
        switchTab('#close-account');
    }

    // Preview avatar khi chọn file
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function() {
                    // Có thể thêm preview nếu cần
                    console.log('Avatar selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
/* Tab pills styling */
.nav-pills .nav-link {
    color: #495057;
    font-weight: 500;
    padding: 12px 24px;
    border-radius: 50px;
    margin-right: 8px;
    transition: all 0.3s ease;
}

.nav-pills .nav-link:hover {
    background-color: #e9ecef;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #0f172a, #1d4ed8);
    color: white;
    box-shadow: 0 4px 12px rgba(29, 78, 216, 0.3);
}

/* Avatar hover effect */
#avatarPreviewHero {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

#avatarPreviewHero:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3) !important;
}

/* Card styling */
.card {
    border-radius: 16px;
}

.card-header {
    background: transparent;
}

/* Form styling */
.form-control:focus {
    border-color: #1d4ed8;
    box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.25);
}
</style>
@endpush
