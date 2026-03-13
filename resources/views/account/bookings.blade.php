@extends('layouts.app')

@section('title', 'Lịch sử đặt phòng')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <h2 class="mb-4">Lịch sử đặt phòng</h2>
        @if($bookings->isEmpty())
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-calendar-x display-4"></i>
                    <p class="mt-3 mb-0">Bạn chưa có đơn đặt phòng nào.</p>
                    <a href="{{ route('home') }}#rooms-section" class="btn btn-primary mt-3">Xem phòng & đặt ngay</a>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Phòng</th>
                                    <th>Nhận phòng</th>
                                    <th>Trả phòng</th>
                                    <th>Số khách</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bookings as $b)
                                <tr>
                                    <td>{{ $b->room ? $b->room->name : '—' }}</td>
                                    <td>{{ $b->check_in ? $b->check_in->format('d/m/Y') : '—' }}</td>
                                    <td>{{ $b->check_out ? $b->check_out->format('d/m/Y') : '—' }}</td>
                                    <td>{{ $b->guests ?? '—' }}</td>
                                    <td>{{ $b->total_price ? number_format($b->total_price, 0, ',', '.') . ' VNĐ' : '—' }}</td>
                                    <td>
                                        @if($b->status === 'pending')
                                            <span class="badge bg-warning text-dark">Chờ xác nhận</span>
                                        @elseif($b->status === 'confirmed')
                                            <span class="badge bg-info">Đã xác nhận</span>
                                        @elseif($b->status === 'completed')
                                            <span class="badge bg-success">Hoàn thành</span>
                                        @elseif($b->status === 'cancelled')
                                            <span class="badge bg-secondary">Đã hủy</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $b->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
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
        @endif
    </div>
</div>
@endsection
