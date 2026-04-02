@extends('layouts.admin')

@section('title', 'Quản lý phòng')

@section('content')
<div class="container-fluid admin-page px-0">
    <div class="page-header">
        <h1>Quản lý phòng</h1>
        @if(auth()->user()->canAccessAdmin())
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Thêm phòng</a>
        @endif
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Danh sách phòng</h5>
            <form action="{{ route('admin.rooms.index') }}" method="GET" class="admin-toolbar">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm admin-filter-field" placeholder="Tìm tên, số phòng, loại...">
                <select name="status" class="form-select form-select-sm admin-filter-field">
                    <option value="">Tất cả trạng thái</option>
                    <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Sẵn sàng</option>
                    <option value="booked" {{ request('status') === 'booked' ? 'selected' : '' }}>Đã đặt</option>
                    <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Bảo trì</option>
                </select>
                <button type="submit" class="btn btn-light btn-sm flex-shrink-0"><i class="bi bi-search me-1"></i>Tìm</button>
                @if(request()->hasAny(['q','status']))
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-light btn-sm flex-shrink-0">Xóa lọc</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="admin-table-wrap">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tên phòng</th>
                            <th>Ảnh</th>
                            <th>Loại</th>
                            <th class="text-end text-nowrap">Giá cơ bản</th>
                            <th class="text-center text-nowrap">Tối đa khách</th>
                            <th>Trạng thái</th>
                            <th class="text-end text-nowrap" style="min-width: 9.5rem;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td>{{ $room->id }}</td>
                                <td>{{ $room->name }}</td>
                                <td class="text-nowrap">
                                    <img src="{{ $room->adminThumbnailUrl() }}" alt="" width="60" height="45" class="rounded border bg-light d-block" style="width: 60px; height: 45px; object-fit: cover;" loading="lazy">
                                </td>
                                <td>{{ $room->type }}</td>
                                <td class="text-end text-nowrap">{{ number_format($room->base_price, 0, ',', '.') }} VNĐ</td>
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
                                <td class="text-end align-middle">
                                    <div class="admin-table-actions">
                                        <button type="button"
                                                class="btn btn-info btn-sm px-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailModal{{ $room->id }}"
                                                title="Xem">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-warning btn-sm px-2" title="Sửa"><i class="bi bi-pencil"></i></a>
                                        @if(auth()->user()->isAdmin())
                                        <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa phòng này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2" title="Xóa"><i class="bi bi-trash"></i></button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Chưa có phòng nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rooms->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $rooms->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Chi tiết phòng -->
@foreach($rooms as $room)
<div class="modal fade" id="detailModal{{ $room->id }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $room->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-bottom-0 py-3">
                <div class="d-flex align-items-center justify-content-between w-100 gap-3">
                    <h5 class="modal-title m-0" id="detailModalLabel{{ $room->id }}">
                        Chi tiết phòng: {{ $room->name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-5">
                        <div class="ratio ratio-4x3 rounded-3 overflow-hidden shadow-sm bg-light">
                            <img src="{{ $room->adminDetailImageUrl() }}"
                                 alt="{{ $room->name }}"
                                 class="w-100 h-100"
                                 style="object-fit: cover;">
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <tr>
                                <th class="text-muted fw-semibold" style="width: 40%;"><i class="bi bi-hash me-2"></i>Số phòng</th>
                                <td class="text-dark">{{ $room->room_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-tag me-2"></i>Tên phòng</th>
                                <td class="text-dark">{{ $room->name }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-building me-2"></i>Loại phòng</th>
                                <td class="text-dark">{{ $room->type ?? 'Chưa phân loại' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-people me-2"></i>Sức chứa</th>
                                <td><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $room->max_guests }} người</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-door-open me-2"></i>Số giường</th>
                                <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $room->beds ?? 1 }} giường</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-droplet me-2"></i>Số phòng tắm</th>
                                <td><span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">{{ $room->baths ?? 1 }} phòng</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-rulers me-2"></i>Diện tích</th>
                                <td class="text-dark">{{ $room->area ?? 0 }} m²</td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-currency-dollar me-2"></i>Giá cơ bản</th>
                                <td class="fw-bold text-danger">{{ number_format($room->base_price, 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-patch-check me-2"></i>Trạng thái</th>
                                <td>
                                    @if($room->status === 'available')
                                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Sẵn sàng</span>
                                    @elseif($room->status === 'booked')
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Đã đặt</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Bảo trì</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold" style="vertical-align: top;"><i class="bi bi-card-text me-2"></i>Mô tả</th>
                                <td class="text-dark" style="white-space: normal;">
                                    {{ $room->description ?? 'Không có mô tả' }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Chỉnh sửa
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
</div>
</div>
@endforeach
@endsection
