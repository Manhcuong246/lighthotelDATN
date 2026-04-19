@extends('layouts.admin')

@section('title', 'Quản lý thanh toán')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Quản lý thanh toán</h1>
    </div>

    <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex gap-2 align-items-start">
            <i class="bi bi-info-circle fs-5 flex-shrink-0 mt-1"></i>
            <div>
                <strong>Sửa trạng thái đơn / phương thức thanh toán (tiền mặt, VNPay…)?</strong>
                <p class="mb-0 small mt-1">
                    Phần đó nằm ở <strong>Đặt phòng → mở chi tiết đơn #...</strong>, kéo xuống mục <strong>«Trạng thái đơn &amp; thanh toán»</strong>.
                    Từ bảng dưới đây, dùng nút <span class="badge bg-primary">Đơn</span> để nhảy thẳng tới chỗ chỉnh.
                </p>
            </div>
        </div>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Danh sách thanh toán</h5>
            <form action="{{ route('admin.bookings.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm khách, mã GD..." style="width: 200px;">
                <select name="payment_status" class="form-select form-select-sm" style="width: 140px;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Thất bại</option>
                    <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Đã hoàn tiền</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm btn-admin-icon" title="Tìm"><i class="bi bi-search"></i></button>
                @if(request()->hasAny(['q','payment_status']))
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-sm btn-admin-icon" title="Xóa bộ lọc"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Khách hàng</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>Trạng thái</th>
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
                                <td><strong>{{ number_format($payment->amount, 0, ',', '.') }} VNĐ</strong></td>
                                <td>
                                    @if($payment->method === 'credit_card')
                                        <span class="badge bg-info">Thẻ tín dụng</span>
                                    @elseif($payment->method === 'bank_transfer')
                                        <span class="badge bg-primary">Chuyển khoản</span>
                                    @elseif($payment->method === 'cash')
                                        <span class="badge bg-secondary">Tiền mặt</span>
                                    @elseif($payment->method === 'vnpay')
                                        <span class="badge bg-dark">VNPay</span>
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
                                    @elseif($payment->status === 'refunded')
                                        <span class="badge bg-info text-dark">Đã hoàn tiền</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $payment->status }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary btn-admin-icon" title="Xem thanh toán"><i class="bi bi-eye"></i></a>
                                    @if($payment->booking_id)
                                        <a href="{{ route('admin.bookings.show', $payment->booking_id) }}#payment-booking-settings" class="btn btn-sm btn-primary btn-admin-icon" title="Sửa trạng thái đơn &amp; thanh toán trên đơn"><i class="bi bi-pencil-square"></i><span class="d-none d-xl-inline ms-1 small">Đơn</span></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Chưa có thanh toán nào.</td>
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
