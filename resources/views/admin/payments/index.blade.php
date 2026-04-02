@extends('layouts.admin')

@section('title', 'Quản lý thanh toán')

@section('content')
<<<<<<< HEAD
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Quản lý thanh toán</h1>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3">
            <h5 class="mb-0">Danh sách thanh toán</h5>
=======
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Quản lý thanh toán</h1>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Danh sách thanh toán</h5>
            <form action="{{ route('admin.payments.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm khách, mã GD..." style="width: 200px;">
                <select name="status" class="form-select form-select-sm" style="width: 140px;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Thất bại</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Tìm</button>
                @if(request()->hasAny(['q','status']))
                <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary btn-sm">Xóa bộ lọc</a>
                @endif
            </form>
>>>>>>> vinam
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Khách hàng</th>
<<<<<<< HEAD
                            <th>Đặt phòng</th>
=======
>>>>>>> vinam
                            <th>Phòng</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>Trạng thái</th>
<<<<<<< HEAD
                            <th>Ngày thanh toán</th>
=======
>>>>>>> vinam
                            <th width="180">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>
                                    @if($payment->booking && $payment->booking->user)
                                        {{ $payment->booking->user->full_name }}<br>
                                        <small class="text-muted">{{ $payment->booking->user->email }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
<<<<<<< HEAD
                                <td>
                                    @if($payment->booking)
                                        <a href="{{ route('admin.bookings.show', $payment->booking) }}" class="text-primary text-decoration-none">
                                            #{{ $payment->booking->id }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
=======
>>>>>>> vinam
                                <td>{{ $payment->booking && $payment->booking->room ? $payment->booking->room->name : '—' }}</td>
                                <td><strong>{{ number_format($payment->amount, 0, ',', '.') }} VNĐ</strong></td>
                                <td>
                                    @if($payment->method === 'credit_card')
                                        <span class="badge bg-info">Thẻ tín dụng</span>
                                    @elseif($payment->method === 'bank_transfer')
                                        <span class="badge bg-primary">Chuyển khoản</span>
                                    @elseif($payment->method === 'cash')
                                        <span class="badge bg-secondary">Tiền mặt</span>
<<<<<<< HEAD
=======
                                    @elseif($payment->method === 'vnpay')
                                        <span class="badge bg-dark">VNPay</span>
>>>>>>> vinam
                                    @else
                                        {{ $payment->method }}
                                    @endif
                                </td>
                                <td>
                                    @if($payment->status === 'pending')
                                        <span class="badge bg-warning text-dark">Chờ thanh toán</span>
                                    @elseif($payment->status === 'paid')
                                        <span class="badge bg-success">Đã thanh toán</span>
                                    @elseif($payment->status === 'failed')
                                        <span class="badge bg-danger">Thất bại</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $payment->status }}</span>
                                    @endif
                                </td>
                                <td>
<<<<<<< HEAD
                                    @if($payment->created_at)
                                        @if(is_string($payment->created_at))
                                            {{ \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i') }}
                                        @else
                                            {{ $payment->created_at->format('d/m/Y H:i') }}
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
=======
>>>>>>> vinam
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                    <a href="{{ route('admin.payments.edit', $payment) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa thanh toán này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
<<<<<<< HEAD
                                <td colspan="9" class="text-center py-4 text-muted">Chưa có thanh toán nào.</td>
=======
                                <td colspan="7" class="text-center py-4 text-muted">Chưa có thanh toán nào.</td>
>>>>>>> vinam
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
