@extends('layouts.admin')

@section('title', 'Quản lý đặt phòng')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Quản lý đặt phòng</h1>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3">
            <h5 class="mb-0">Danh sách đơn đặt phòng</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Khách hàng</th>
                            <th>Phòng</th>
                            <th>Nhận phòng</th>
                            <th>Trả phòng</th>
                            <th>Khách</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th width="180">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td>
                                    @if($booking->user)
                                        {{ $booking->user->full_name }}<br>
                                        <small class="text-muted">{{ $booking->user->email }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $booking->room ? $booking->room->name : '—' }}</td>
                                <td>{{ $booking->check_in ? $booking->check_in->format('d/m/Y') : '—' }}</td>
                                <td>{{ $booking->check_out ? $booking->check_out->format('d/m/Y') : '—' }}</td>
                                <td>{{ $booking->guests ?? '—' }}</td>
                                <td>{{ $booking->total_price ? number_format($booking->total_price, 0, ',', '.') . ' VNĐ' : '—' }}</td>
                                <td>
                                    @if($booking->status === 'pending')
                                        <span class="badge bg-warning text-dark">Chờ xác nhận</span>
                                    @elseif($booking->status === 'confirmed')
                                        <span class="badge bg-info">Đã xác nhận</span>
                                    @elseif($booking->status === 'completed')
                                        <span class="badge bg-success">Hoàn thành</span>
                                    @elseif($booking->status === 'cancelled')
                                        <span class="badge bg-secondary">Đã hủy</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $booking->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                    <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa đơn đặt phòng này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Chưa có đơn đặt phòng nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($bookings->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $bookings->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
