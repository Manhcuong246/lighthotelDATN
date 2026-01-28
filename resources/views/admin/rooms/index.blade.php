@extends('layouts.app')

@section('title', 'Quản lý phòng')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Quản lý phòng</h2>
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary">Thêm phòng mới</a>
    </div>

    <table class="table table-bordered table-hover align-middle">
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
                    <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Bạn có chắc muốn xóa phòng này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">Chưa có phòng nào.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div>
        {{ $rooms->links() }}
    </div>
@endsection


