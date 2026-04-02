@extends('layouts.admin')

@section('title', 'Chỉnh sửa người dùng - ' . $user->full_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Chỉnh sửa người dùng</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Quay lại</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin người dùng</h5>
                </div>
                <div class="card-body">
<<<<<<< HEAD
                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
=======
                    <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data">

>>>>>>> vinam
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="full_name" class="form-label fw-bold">Họ tên</label>
                            <input type="text" 
                                   class="form-control @error('full_name') is-invalid @enderror" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="{{ old('full_name', $user->full_name) }}"
                                   required>
                            @error('full_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-bold">Điện thoại</label>
                            <input type="text" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $user->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label fw-bold">Trạng thái</label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" 
                                    name="status">
                                <option value="active" @selected(old('status', $user->status) === 'active')>Hoạt động</option>
                                <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Không hoạt động</option>
                                <option value="banned" @selected(old('status', $user->status) === 'banned')>Bị cấm</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
<<<<<<< HEAD
=======
+
+                        <div class="mb-3">
+                            <label for="avatar" class="form-label fw-bold">Ảnh đại diện</label>
+                            <div class="d-flex align-items-center gap-3 mb-2">
+                                @if($user->avatar_url)
+                                    <img src="{{ str_starts_with($user->avatar_url, 'http') ? $user->avatar_url : asset('storage/' . $user->avatar_url) }}" 
+                                         alt="Avatar" class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover;">
+                                @else
+                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" 
+                                         style="width: 64px; height: 64px;">
+                                        {{ strtoupper(mb_substr($user->full_name, 0, 1)) }}
+                                    </div>
+                                @endif
+                                <input type="file" class="form-control @error('avatar') is-invalid @enderror" id="avatar" name="avatar" accept="image/*">
+                            </div>
+                            <small class="text-muted">Định dạng: JPG, PNG, GIF. Tối đa 2MB.</small>
+                            @error('avatar')
+                                <div class="invalid-feedback">{{ $message }}</div>
+                            @enderror
+                        </div>
+
+                        <div class="card bg-light border-0 mb-4">
+                            <div class="card-body p-3">
+                                <h6 class="fw-bold mb-3">Vai trò</h6>
+                                <div class="d-flex flex-wrap gap-3">
+                                    @forelse ($roles as $role)
+                                        <div class="form-check">
+                                            <input class="form-check-input" type="checkbox" name="role_ids[]" id="role_{{ $role->id }}" value="{{ $role->id }}"
+                                                   @if($user->roles->contains($role->id)) checked @endif>
+                                            <label class="form-check-label" for="role_{{ $role->id }}">
+                                                {{ ucfirst($role->name) }}
+                                            </label>
+                                        </div>
+                                    @empty
+                                        <p class="text-muted mb-0">Không có vai trò nào</p>
+                                    @endforelse
+                                </div>
+                            </div>
+                        </div>

>>>>>>> vinam

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<<<<<<< HEAD
        <div class="col-lg-4">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Vai trò</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @forelse($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" id="role_{{ $role->id }}" value="{{ $role->id }}"
                                       @if($user->roles->contains($role->id)) checked @endif>
                                <label class="form-check-label" for="role_{{ $role->id }}">
                                    {{ ucfirst($role->name) }}
                                </label>
                            </div>
                        @empty
                            <p class="text-muted">Không có vai trò nào</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
=======
        </div>
+        {{-- Cột Roles cũ đã dời vào form chính --}}

>>>>>>> vinam
    </div>
</div>
@endsection
