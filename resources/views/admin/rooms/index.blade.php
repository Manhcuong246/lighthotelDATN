@extends('layouts.admin')

@section('title', 'Quản lý phòng')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Quản lý phòng</h1>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm phòng mới</a>
        @endif
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3">
            <h5 class="mb-0">Danh sách phòng</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tên phòng</th>
                            <th>Loại</th>
                            <th>Giá cơ bản</th>
                            <th>Tối đa khách</th>
                            <th>Trạng thái</th>
                            <th width="150">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td>{{ $room->id }}</td>
                                <td>{{ $room->name }}</td>
                                <td>{{ $room->type }}</td>
                                <td>{{ number_format($room->base_price, 0, ',', '.') }} VNĐ</td>
                                <td>{{ $room->max_guests }}</td>
                                <td>
                                    @if($room->status === 'available')
                                        <span class="badge bg-success">Sẵn sàng</span>
                                    @elseif($room->status === 'booked')
                                        <span class="badge bg-warning text-dark">Đã đặt</span>
                                    @else
                                        <span class="badge bg-secondary">Bảo trì</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa phòng này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Chưa có phòng nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rooms->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $rooms->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
