@extends('layouts.app')

@section('title', 'Thông tin cá nhân')

@push('styles')
<style>
.profile-page .page-header {
    margin-bottom: 1.25rem;
}
.profile-page .page-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #111827;
}
.profile-page .page-subtitle {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 0.15rem;
    max-width: 36rem;
}
.profile-page .profile-segmented {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    margin-bottom: 1.25rem;
}
.profile-page .profile-segmented .segment {
    border: none;
    background: transparent;
    padding: 0.55rem 1.1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    color: #6b7280;
    transition: background 0.15s ease, color 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.profile-page .profile-segmented .segment:hover:not(.active) {
    background: #f9fafb;
    color: #374151;
}
.profile-page .profile-segmented .segment.active {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    color: #4338ca;
    box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.2);
}
.profile-page .profile-panel {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}
.profile-page .profile-panel-header {
    padding: 1.25rem 1.25rem 0;
}
.profile-page .profile-panel-title {
    font-size: 1rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.profile-page .profile-panel-title i {
    color: #6366f1;
    font-size: 1.1rem;
}
.profile-page .profile-panel-body {
    padding: 1.25rem;
}
.profile-page .profile-avatar-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.25rem;
    height: 100%;
}
.profile-page .profile-avatar-wrap {
    width: 128px;
    height: 128px;
    margin-left: auto;
    margin-right: auto;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #fff;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12);
}
.profile-page .profile-avatar-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.profile-page .avatar-placeholder-lg {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    font-size: 2.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
.profile-page .profile-avatar-label {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #9ca3af;
    margin-bottom: 0.75rem;
    display: block;
    text-align: center;
}
.profile-page .form-control,
.profile-page .form-select {
    border-color: #e5e7eb;
    border-radius: 10px;
    font-size: 0.9375rem;
}
.profile-page .form-control:focus {
    border-color: #a5b4fc;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18);
}
.profile-page .form-control:disabled,
.profile-page .form-control.bg-light {
    background: #f3f4f6 !important;
    color: #6b7280;
}
.profile-page .btn-primary.rounded-pill {
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}
.profile-page .panel-danger {
    border-color: #fecaca;
    box-shadow: 0 1px 3px rgba(220, 38, 38, 0.08);
}
.profile-page .panel-danger .profile-panel-title i {
    color: #dc2626;
}
</style>
@endpush

@section('content')
<div class="profile-page pt-2 pb-5">
    @include('partials.account-context-nav', ['current' => 'profile'])
    <div class="page-header">
        <h1 class="page-title">Tài khoản</h1>
        <p class="page-subtitle">Cập nhật hồ sơ, ảnh đại diện và mật khẩu để phù hợp với {{ $hotelInfo?->name ?? config('app.name', 'Light Hotel') }}.</p>
    </div>

    <div class="profile-segmented" id="profileTabs" role="tablist">
        <button class="segment active" id="info-tab" data-bs-toggle="pill" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
            <i class="bi bi-person"></i>Thông tin cá nhân
        </button>
        <button class="segment" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
            <i class="bi bi-shield-lock"></i>Đổi mật khẩu
        </button>
        @if($user->canSelfCloseAccountFromWebsite())
        <button class="segment" id="close-tab" data-bs-toggle="pill" data-bs-target="#close-account" type="button" role="tab" aria-controls="close-account" aria-selected="false">
            <i class="bi bi-person-x"></i>Đóng tài khoản
        </button>
        @endif
    </div>

    <div class="tab-content" id="profileTabsContent">

        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab" tabindex="0">
            <div class="profile-panel">
                <div class="profile-panel-header">
                    <h2 class="profile-panel-title"><i class="bi bi-person-badge"></i>Chỉnh sửa hồ sơ</h2>
                </div>
                <div class="profile-panel-body">
                    <form method="POST" action="{{ url('/account/profile') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="row">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label class="form-label fw-semibold small text-muted">Họ và tên <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('full_name') is-invalid @enderror" name="full_name" value="{{ old('full_name', $user->full_name) }}" required autocomplete="name">
                                        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-muted">Số điện thoại</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09xx xxx xxx" autocomplete="tel">
                                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label fw-semibold small text-muted">Email</label>
                                    <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled>
                                    <small class="text-muted">Email không thể thay đổi.</small>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label fw-semibold small text-muted">Ngày tham gia</label>
                                    <input type="text" class="form-control bg-light" value="{{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y H:i') }}" disabled>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="profile-avatar-card">
                                    <span class="profile-avatar-label">Ảnh đại diện</span>
                                    @php
                                        $avatarUrl = $user->avatar_url
                                            ? (str_starts_with($user->avatar_url, 'http') ? $user->avatar_url : '/storage/' . $user->avatar_url)
                                            : null;
                                    @endphp
                                    <div class="profile-avatar-wrap" id="profileAvatarWrap">
                                        @if($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="" id="profileAvatarImg">
                                        @else
                                            <div class="avatar-placeholder-lg" id="profileAvatarPlaceholder">{{ strtoupper(mb_substr($user->full_name ?? 'U', 0, 1)) }}</div>
                                        @endif
                                    </div>
                                    <input type="file" name="avatar" class="d-none" accept="image/jpeg,image/png,image/gif,image/webp" id="avatarInput">
                                    <label for="avatarInput" class="btn btn-light border w-100 mt-2 mb-1 py-2 rounded-3 small fw-semibold text-secondary">
                                        <i class="bi bi-upload me-1"></i>Chọn ảnh
                                    </label>
                                    <small class="text-muted d-block text-center">JPG, PNG, GIF, WebP · tối đa 2MB</small>
                                    @error('avatar') <div class="text-danger small mt-2 text-center">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-end mt-4 pt-3 border-top border-light-subtle">
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-house-door me-1"></i>Về trang chủ</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-check-lg me-1"></i>Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab" tabindex="0">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-xl-6">
                    <div class="profile-panel">
                        <div class="profile-panel-header">
                            <h2 class="profile-panel-title"><i class="bi bi-shield-lock"></i>Đổi mật khẩu</h2>
                        </div>
                        <div class="profile-panel-body">
                            <form method="POST" action="{{ url('/account/profile/password') }}">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required autocomplete="current-password">
                                    @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Mật khẩu mới <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold small text-muted">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                </div>
                                <button type="submit" class="btn btn-primary rounded-pill w-100 py-2">
                                    <i class="bi bi-key me-1"></i>Đổi mật khẩu
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($user->canSelfCloseAccountFromWebsite())
        <div class="tab-pane fade" id="close-account" role="tabpanel" aria-labelledby="close-tab" tabindex="0">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="profile-panel panel-danger">
                        <div class="profile-panel-header">
                            <h2 class="profile-panel-title text-danger"><i class="bi bi-exclamation-octagon"></i>Đóng tài khoản</h2>
                        </div>
                        <div class="profile-panel-body">
                            <p class="text-muted small mb-3">Sau khi đóng, bạn sẽ bị đăng xuất. Email được giải phóng để có thể <strong>đăng ký lại</strong>. Lịch sử đơn đặt vẫn được lưu trong hệ thống theo quy định.</p>
                            <p class="text-muted small mb-4">Chỉ có thể đóng khi bạn <strong>không còn đơn</strong> nào đang xử lý (trừ đơn đã hủy hoặc đã hoàn tất).</p>
                            <form method="POST" action="{{ route('account.close') }}" onsubmit="return confirm('Bạn chắc chắn muốn đóng tài khoản? Hành động này không hoàn tác qua website.');">
                                @csrf
                                @method('DELETE')
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-check mb-4">
                                    <input class="form-check-input @error('confirm_close') is-invalid @enderror" type="checkbox" name="confirm_close" id="confirm_close" value="1" {{ old('confirm_close') ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="confirm_close">Tôi hiểu tôi sẽ không đăng nhập được nữa và đồng ý đóng tài khoản.</label>
                                    @error('confirm_close')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-outline-danger rounded-pill">Xác nhận đóng tài khoản</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function syncSegmentActive() {
        document.querySelectorAll('.profile-page .profile-segmented .segment').forEach(function (btn) {
            var target = btn.getAttribute('data-bs-target');
            var pane = target ? document.querySelector(target) : null;
            var on = !!(pane && pane.classList.contains('active'));
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }

    function switchTab(hash) {
        if (hash === '#password') {
            var passwordTab = document.getElementById('password-tab');
            if (passwordTab) passwordTab.click();
        }
        if (hash === '#close-account') {
            var t = document.getElementById('close-tab');
            if (t) t.click();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.profile-page .profile-segmented .segment').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', syncSegmentActive);
        });
        syncSegmentActive();

        if (window.location.hash === '#password') switchTab('#password');
        if (window.location.hash === '#close-account') switchTab('#close-account');

        var input = document.getElementById('avatarInput');
        var wrap = document.getElementById('profileAvatarWrap');
        if (!input || !wrap) return;

        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            if (!f || !f.type.match(/^image\//)) return;
            var url = URL.createObjectURL(f);
            var existing = wrap.querySelector('#profileAvatarImg');
            if (existing) {
                existing.onload = function () { URL.revokeObjectURL(url); };
                existing.src = url;
            } else {
                var ph = wrap.querySelector('#profileAvatarPlaceholder');
                if (ph) ph.remove();
                var img = document.createElement('img');
                img.id = 'profileAvatarImg';
                img.alt = '';
                img.src = url;
                img.onload = function () { URL.revokeObjectURL(url); };
                wrap.appendChild(img);
            }
        });
    });
})();
</script>
@endpush
