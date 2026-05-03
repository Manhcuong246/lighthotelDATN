@extends('layouts.admin')

@section('title', $user->full_name . ' — Quản lý người dùng')

@section('content')
@php
    $avatarSrc = $user->avatar_url
        ? (str_starts_with($user->avatar_url, 'http') ? $user->avatar_url : asset('storage/' . $user->avatar_url))
        : null;
@endphp
<div class="container-fluid user-detail-page">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}" class="text-decoration-none">Người dùng</a></li>
            <li class="breadcrumb-item active text-truncate" aria-current="page">{{ $user->full_name }}</li>
        </ol>
    </nav>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('warning') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="user-detail-hero card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="user-detail-hero-accent"></div>
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row align-items-start gap-4">
                <div class="user-detail-avatar flex-shrink-0">
                    @if($avatarSrc)
                        <img src="{{ $avatarSrc }}" alt="" class="rounded-circle object-fit-cover shadow" width="96" height="96">
                    @else
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-2 shadow user-detail-avatar-fallback">
                            {{ strtoupper(mb_substr($user->full_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <h1 class="h4 fw-bold text-dark mb-1">{{ $user->full_name }}</h1>
                            <p class="text-muted mb-2 small text-break">{{ $user->email }}</p>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                @if($user->status === 'active')
                                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                                @elseif($user->status === 'banned')
                                    <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">Bị cấm</span>
                                @else
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary border">{{ $user->status }}</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light border shadow-sm rounded-3">
                            <i class="bi bi-arrow-left me-1"></i> Danh sách
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data" class="user-detail-form">
        @csrf
        @method('PUT')

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="h6 fw-bold text-uppercase letter-spacing text-muted mb-4 pb-2 border-bottom">Thông tin &amp; chỉnh sửa</h2>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label fw-semibold small">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg rounded-3 @error('full_name') is-invalid @enderror"
                                       id="full_name" name="full_name" value="{{ old('full_name', $user->full_name) }}" required>
                                @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-lg rounded-3 @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold small">Điện thoại</label>
                                <input type="text" class="form-control rounded-3 @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="VD: 0901234567">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold small">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select rounded-3 @error('status') is-invalid @enderror" id="status" name="status">
                                    <option value="active" @selected(old('status', $user->status) === 'active')>Hoạt động</option>
                                    <option value="banned" @selected(old('status', $user->status) === 'banned')>Bị cấm</option>
                                </select>
                                <div class="form-text">
                                    <strong>Hoạt động:</strong> đăng nhập bình thường.
                                    <strong>Bị cấm:</strong> không đăng nhập được; hệ thống gửi email thông báo tới khách khi bạn lưu trạng thái này.
                                </div>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="avatar" class="form-label fw-semibold small">Ảnh đại diện</label>
                            <input type="file" class="form-control rounded-3 @error('avatar') is-invalid @enderror" id="avatar" name="avatar" accept="image/*">
                            <div class="form-text">JPG, PNG, GIF — tối đa 2MB.</div>
                            @error('avatar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="rounded-4 bg-light border p-4 mb-4">
                            <h3 class="h6 fw-bold mb-3">Vai trò</h3>
                            <div class="row g-2">
                                @forelse ($roles as $role)
                                    @php
                                        $roleChecked = is_array(old('role_ids'))
                                            ? in_array((string) $role->id, old('role_ids', []), true)
                                            : $user->roles->contains($role->id);
                                    @endphp
                                    <div class="col-sm-6 col-md-4">
                                        <div class="form-check user-detail-role-check">
                                            <input class="form-check-input" type="checkbox" name="role_ids[]" id="role_{{ $role->id }}" value="{{ $role->id }}" @checked($roleChecked)>
                                            <label class="form-check-label" for="role_{{ $role->id }}">{{ ucfirst($role->name) }}</label>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">Chưa có vai trò trong hệ thống.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-2">
                            <button type="submit" class="btn btn-primary rounded-3 px-4 shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Lưu thay đổi
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary rounded-3">Hủy</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4 sticky-lg-top" style="top: 88px;">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold text-uppercase letter-spacing text-muted mb-3">Lịch sử tài khoản</h2>
                        <dl class="row small mb-0 user-detail-meta">
                            <dt class="col-5 text-muted fw-normal">Ngày tạo</dt>
                            <dd class="col-7 mb-3">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '—' }}</dd>
                            <dt class="col-5 text-muted fw-normal">Cập nhật</dt>
                            <dd class="col-7 mb-0">{{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    .user-detail-page .letter-spacing { letter-spacing: 0.06em; }
    .user-detail-hero { background: #fff; }
    .user-detail-hero-accent {
        height: 4px;
        background: linear-gradient(90deg, var(--secondary-color, #3f37c9), var(--primary-color, #4361ee), #72ddf7);
        opacity: 0.9;
    }
    .user-detail-avatar-fallback {
        width: 96px;
        height: 96px;
        background: linear-gradient(135deg, var(--secondary-color, #3f37c9), var(--primary-color, #4361ee));
    }
    .user-detail-role-check {
        padding: 0.5rem 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid rgba(0,0,0,0.06);
        background: #fff;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .user-detail-role-check:has(.form-check-input:checked) {
        border-color: rgba(67, 97, 238, 0.45);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.12);
        background: rgba(67, 97, 238, 0.04);
    }
    .user-detail-meta dd { word-break: break-word; }
    @supports not selector(:has(*)) {
        .user-detail-role-check .form-check-input:checked ~ label { font-weight: 600; }
    }
</style>
@endpush
@endsection
