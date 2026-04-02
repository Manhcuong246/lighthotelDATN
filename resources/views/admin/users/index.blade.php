@extends('layouts.admin')

@section('title', 'Quản lý người dùng')

@section('content')
<div class="container-fluid admin-page px-0">
    <div class="page-header">
        <h1>Quản lý người dùng</h1>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Danh sách người dùng</h5>
            <form action="{{ route('admin.users.index') }}" method="GET" class="admin-toolbar">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm admin-filter-field" placeholder="Tìm tên, email, SĐT...">
                <button type="submit" class="btn btn-light btn-sm flex-shrink-0"><i class="bi bi-search me-1"></i>Tìm</button>
                @if(request('q'))
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-light btn-sm flex-shrink-0">Xóa lọc</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="admin-table-wrap">
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
                            <th class="text-end text-nowrap" style="min-width: 9.5rem;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    @if($user->avatar_url)
                                        <img src="{{ str_starts_with($user->avatar_url, 'http') ? $user->avatar_url : asset('storage/' . $user->avatar_url) }}" alt="{{ $user->full_name }}" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">
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
                                <td class="text-end">
                                    <div class="admin-table-actions">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                        @if(auth()->user()->isAdmin())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                        </form>
                                        @endif
                                    </div>
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
        <div class="card-footer bg-white border-0 py-3">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
