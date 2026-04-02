@extends('layouts.admin')

@section('title', 'Quản lý thanh toán')

@section('content')
<div class="container-fluid admin-page px-0">
    <div class="page-header">
        <div>
            <h1>Quản lý thanh toán</h1>
            <p class="admin-text-muted mb-0 mt-1">Thanh toán theo giao dịch thực tế; hoàn tiền khi hủy đơn đã thanh toán xử lý tại chi tiết và cột &quot;Hoàn tiền&quot;.</p>
        </div>
    </div>

    @if(($pendingCancellationCount ?? 0) > 0)
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <span><i class="bi bi-exclamation-triangle-fill me-2"></i>Có <strong>{{ $pendingCancellationCount }}</strong> đơn đang <strong>chờ duyệt hủy</strong> (khách đã gửi yêu cầu). Vào <a href="{{ route('admin.bookings.index', ['status' => 'cancellation_pending']) }}" class="alert-link fw-semibold">Đặt phòng → lọc &quot;Chờ xử lý hủy&quot;</a> để chấp nhận / từ chối.</span>
        <a href="{{ route('admin.bookings.index', ['status' => 'cancellation_pending']) }}" class="btn btn-sm btn-dark">Mở danh sách</a>
    </div>
    @endif

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Danh sách thanh toán</h5>
            <form action="{{ route('admin.payments.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm khách, mã GD..." style="width: 200px;">
                <select name="status" class="form-select form-select-sm" style="width: 140px;">
                    <option value="">TT thanh toán</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Thất bại</option>
                </select>
                <select name="refund_status" class="form-select form-select-sm admin-filter-field">
                    <option value="">TT hoàn tiền</option>
                    <option value="none" {{ request('refund_status') === 'none' ? 'selected' : '' }}>Không áp dụng</option>
                    <option value="awaiting_user_info" {{ request('refund_status') === 'awaiting_user_info' ? 'selected' : '' }}>Chờ khách gửi TK</option>
                    <option value="pending_admin" {{ request('refund_status') === 'pending_admin' ? 'selected' : '' }}>Chờ KS hoàn tiền</option>
                    <option value="completed" {{ request('refund_status') === 'completed' ? 'selected' : '' }}>Đã hoàn xong</option>
                </select>
                <select name="booking_status" class="form-select form-select-sm admin-filter-field" title="Trạng thái đơn đặt phòng">
                    <option value="">Trạng thái đơn</option>
                    <option value="cancellation_pending" {{ request('booking_status') === 'cancellation_pending' ? 'selected' : '' }}>Chờ xử lý hủy</option>
                    <option value="pending" {{ request('booking_status') === 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                    <option value="confirmed" {{ request('booking_status') === 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    <option value="cancelled" {{ request('booking_status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    <option value="refunded" {{ request('booking_status') === 'refunded' ? 'selected' : '' }}>Đã hoàn tiền</option>
                </select>
                <button type="submit" class="btn btn-light btn-sm flex-shrink-0"><i class="bi bi-search me-1"></i>Tìm</button>
                @if(request()->hasAny(['q','status','refund_status','booking_status']))
                <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-light btn-sm flex-shrink-0">Xóa lọc</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="admin-table-wrap">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Khách hàng</th>
                            <th>Phòng</th>
                            <th>Trạng thái đơn</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>TT thanh toán</th>
                            <th>Hoàn tiền</th>
                            <th class="text-end text-nowrap" style="min-width: 6rem;">Hành động</th>
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
                                <td>
                                    @if($payment->booking)
                                        <span class="small">{{ $payment->booking->roomNamesLabel() }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->booking)
                                        @php
                                            $bs = $payment->booking->status;
                                            $bsBadge = match ($bs) {
                                                'pending' => ['warning', 'Chờ xác nhận'],
                                                'confirmed' => ['info', 'Đã xác nhận'],
                                                'cancellation_pending' => ['primary', 'Chờ xử lý hủy'],
                                                'cancelled' => ['secondary', 'Đã hủy'],
                                                'refunded' => ['success', 'Đã hoàn tiền'],
                                                'completed' => ['success', 'Hoàn thành'],
                                                default => ['secondary', $bs],
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $bsBadge[0] }}">{{ $bsBadge[1] }}</span>
                                        @if($bs === 'cancellation_pending' && $payment->booking->cancellation_reason)
                                            <div class="small text-muted mt-1" style="max-width:12rem;">{{ \Illuminate\Support\Str::limit($payment->booking->cancellation_reason, 80) }}</div>
                                        @endif
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
                                    @else
                                        <span class="badge bg-secondary">{{ $payment->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(($payment->refund_status ?? 'none') === 'none')
                                        <span class="text-muted small">—</span>
                                    @elseif($payment->refund_status === 'awaiting_user_info')
                                        <span class="badge bg-warning text-dark">Chờ TK khách</span>
                                    @elseif($payment->refund_status === 'pending_admin')
                                        <span class="badge bg-primary">Chờ hoàn tiền</span>
                                    @elseif($payment->refund_status === 'completed')
                                        <span class="badge bg-success">Đã hoàn</span>
                                    @elseif($payment->refund_status === 'rejected')
                                        <span class="badge bg-secondary">Từ chối</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $payment->refund_status }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="admin-table-actions">
                                        <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Chưa có thanh toán nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $payments->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
