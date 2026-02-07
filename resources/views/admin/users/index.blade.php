@extends('layouts.admin')

@section('title', 'Quản lý người dùng')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Quản lý người dùng</h1>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3">
            <h5 class="mb-0">Danh sách người dùng</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Điện thoại</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng ký</th>
                            <th width="180">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    @if($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" class="rounded-circle me-2" width="32" height="32">
                                    @endif
                                    {{ $user->full_name }}
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?? '—' }}</td>
                                <td>
                                    @forelse($user->roles as $role)
                                        <span class="badge bg-primary">{{ ucfirst($role->name) }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if($user->status === 'active')
                                        <span class="badge bg-success">Hoạt động</span>
                                    @elseif($user->status === 'inactive')
                                        <span class="badge bg-warning text-dark">Không hoạt động</span>
                                    @elseif($user->status === 'banned')
                                        <span class="badge bg-danger">Bị cấm</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $user->status }}</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at ? $user->created_at->format('d/m/Y') : '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Chưa có người dùng nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
