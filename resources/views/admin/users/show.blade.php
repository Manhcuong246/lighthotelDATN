@extends('layouts.admin')

@section('title', 'Chi tiết người dùng - ' . $user->full_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">{{ $user->full_name }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Chỉnh sửa</a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Quay lại</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin cá nhân</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Họ tên</label>
                            <p class="form-control-plaintext">{{ $user->full_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <p class="form-control-plaintext">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Điện thoại</label>
                            <p class="form-control-plaintext">{{ $user->phone ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Trạng thái</label>
                            <p class="form-control-plaintext">
                                @if($user->status === 'active')
                                    <span class="badge bg-success">Hoạt động</span>
                                @elseif($user->status === 'inactive')
                                    <span class="badge bg-warning text-dark">Không hoạt động</span>
                                @elseif($user->status === 'banned')
                                    <span class="badge bg-danger">Bị cấm</span>
                                @else
                                    <span class="badge bg-secondary">{{ $user->status }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ngày tạo</label>
                            <p class="form-control-plaintext">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i:s') : '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cập nhật lần cuối</label>
                            <p class="form-control-plaintext">{{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i:s') : '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Vai trò</h5>
                </div>
                <div class="card-body">
                    @forelse($user->roles as $role)
                        <p class="mb-2">
                            <span class="badge bg-primary" style="font-size: 14px;">{{ ucfirst($role->name) }}</span>
                        </p>
                    @empty
                        <p class="text-muted">Không có vai trò nào</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
