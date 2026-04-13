@extends('layouts.admin')

@section('title', 'Quản lý hoàn tiền')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header mb-4">
        <h1 class="text-dark fw-bold">Quản lý hoàn tiền</h1>
        <p class="text-muted">Danh sách các yêu cầu hủy phòng và hoàn tiền từ khách hàng.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-primary">Yêu cầu hoàn tiền</h5>
            <div class="admin-action-row">
                <a href="{{ route('admin.refunds.index') }}?status=pending_refund" class="btn btn-sm {{ request('status') === 'pending_refund' ? 'btn-warning' : 'btn-outline-warning' }} btn-admin-icon" title="Đang chờ"><i class="bi bi-hourglass-split"></i></a>
                <a href="{{ route('admin.refunds.index') }}?status=refunded" class="btn btn-sm {{ request('status') === 'refunded' ? 'btn-success' : 'btn-outline-success' }} btn-admin-icon" title="Đã hoàn"><i class="bi bi-check2-circle"></i></a>
                <a href="{{ route('admin.refunds.index') }}?status=rejected" class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-danger' : 'btn-outline-danger' }} btn-admin-icon" title="Đã từ chối"><i class="bi bi-x-octagon"></i></a>
                <a href="{{ route('admin.refunds.index') }}" class="btn btn-sm btn-admin-icon {{ !request()->filled('status') ? 'btn-secondary' : 'btn-outline-secondary' }}" title="Tất cả"><i class="bi bi-list-ul"></i></a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Mã Đơn</th>
                            <th>Khách hàng</th>
                            <th>Thông tin hoàn tiền</th>
                            <th class="text-end">Số tiền</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($refundRequests as $request)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('admin.bookings.show', $request->booking_id) }}" class="fw-bold text-decoration-none">#{{ $request->booking_id }}</a>
                                <div class="small text-muted">{{ $request->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $request->user->full_name }}</div>
                                <div class="small text-muted">{{ $request->user->phone }}</div>
                            </td>
                            <td>
                                <div class="small"><strong>Ngân hàng:</strong> {{ $request->bank_name }}</div>
                                <div class="small"><strong>STK:</strong> {{ $request->account_number }}</div>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-success">{{ number_format($request->refund_amount, 0, ',', '.') }} ₫</span>
                                <div class="small text-muted">({{ $request->refund_percentage }}%)</div>
                            </td>
                            <td class="text-center">
                                @if($request->status === 'pending_refund')
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Chờ xử lý</span>
                                @elseif($request->status === 'refunded')
                                    <span class="badge bg-success px-3 py-2 rounded-pill">Đã hoàn tiền</span>
                                @elseif($request->status === 'rejected')
                                    <span class="badge bg-danger px-3 py-2 rounded-pill">Đã từ chối</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.refunds.show', $request) }}" class="btn btn-outline-primary btn-sm btn-admin-icon" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                📭 Không có yêu cầu hoàn tiền nào.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($refundRequests->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $refundRequests->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
