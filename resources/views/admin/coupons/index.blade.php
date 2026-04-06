@extends('layouts.admin')

@section('title', 'Quản lý Mã giảm giá')

@section('content')
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Mã giảm giá</h1>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary shadow-sm btn-admin-icon" title="Thêm mã mới"><i class="bi bi-plus-lg"></i></a>
    </div>

    <div class="card card-admin">
        <div class="card-header card-header-admin d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Danh sách Mã giảm giá</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Mã</th>
                            <th>Giảm giá</th>
                            <th>Ngày hết hạn</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                            <tr>
                                <td class="ps-4">#{{ $coupon->id }}</td>
                                <td><span class="badge bg-primary fs-6">{{ $coupon->code }}</span></td>
                                <td><span class="text-success fw-bold">-{{ $coupon->discount_percent }}%</span></td>
                                <td>{{ $coupon->expired_at ? $coupon->expired_at->format('d/m/Y') : 'Không giới hạn' }}</td>
                                <td>
                                    @if($coupon->is_active)
                                        <span class="badge bg-success">Đang kích hoạt</span>
                                    @else
                                        <span class="badge bg-secondary">Tạm dừng</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="admin-action-row justify-content-end">
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-warning btn-admin-icon" title="Sửa">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-admin-icon" title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Không có mã giảm giá nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($coupons->hasPages())
            <div class="card-footer bg-white border-top-0 pt-3 pb-3">
                {{ $coupons->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
