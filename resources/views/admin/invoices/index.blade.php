@extends('layouts.admin')

@section('title', 'Hóa đơn')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h2 fw-bold mb-0">Hóa đơn</h1>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-primary rounded-2">Đơn đặt phòng</a>
    </div>

    <div class="row g-2 mb-3 small">
        <div class="col-auto"><span class="badge bg-secondary">Tổng {{ $counts['total'] ?? 0 }}</span></div>
        <div class="col-auto"><span class="badge bg-warning text-dark">Chờ {{ $counts['pending'] ?? 0 }}</span></div>
        <div class="col-auto"><span class="badge bg-info text-dark">Một phần {{ $counts['partially_paid'] ?? 0 }}</span></div>
        <div class="col-auto"><span class="badge bg-success">Đã TT {{ $counts['paid'] ?? 0 }}</span></div>
    </div>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Số HĐ, mã đơn, tên khách…">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">Mọi trạng thái</option>
                <option value="pending" @selected(request('status') === 'pending')>Chờ thanh toán</option>
                <option value="partially_paid" @selected(request('status') === 'partially_paid')>Thanh toán một phần</option>
                <option value="paid" @selected(request('status') === 'paid')>Đã thanh toán</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm btn-admin-icon rounded-2" title="Lọc"><i class="bi bi-search"></i></button>
        </div>
    </form>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Số HĐ</th>
                        <th>Đơn</th>
                        <th>Khách</th>
                        <th class="text-end">Tổng</th>
                        <th>Trạng thái</th>
                        <th class="pe-3 text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        <tr>
                            <td class="ps-3 fw-semibold"><code>{{ $inv->invoice_number }}</code></td>
                            <td><a href="{{ route('admin.bookings.show', $inv->booking_id) }}">#{{ $inv->booking_id }}</a></td>
                            <td>{{ $inv->booking?->user?->full_name ?? '—' }}</td>
                            <td class="text-end">{{ number_format((float) $inv->total_amount, 0, ',', '.') }} ₫</td>
                            <td>
                                @if($inv->status === 'paid')
                                    <span class="badge bg-success">Đã TT</span>
                                @elseif($inv->status === 'partially_paid')
                                    <span class="badge bg-info text-dark">Một phần</span>
                                @else
                                    <span class="badge bg-warning text-dark">Chờ</span>
                                @endif
                            </td>
                            <td class="pe-3 text-end text-nowrap">
                                <a href="{{ route('admin.invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary btn-admin-icon rounded-2" title="Xem"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">Chưa có hóa đơn.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer bg-white border-0 py-3">{{ $invoices->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection
