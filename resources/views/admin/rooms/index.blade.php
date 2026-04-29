@extends('layouts.admin')

@section('title', 'Quản lý phòng')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Quản lý phòng</h1>
        @if(auth()->user()->canAccessAdmin())
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary btn-sm" title="Thêm phòng"><i class="bi bi-plus-lg"></i> Thêm phòng</a>
        @endif
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Danh sách phòng</h5>
            <form action="{{ route('admin.rooms.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm tên, số phòng, loại..." style="width: 200px;">
                <select name="status" class="form-select form-select-sm" style="width: 140px;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Sẵn sàng</option>
                    <option value="booked" {{ request('status') === 'booked' ? 'selected' : '' }}>Đã đặt</option>
                    <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Bảo trì</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm btn-admin-icon" title="Tìm"><i class="bi bi-search"></i></button>
                @if(request()->hasAny(['q','status']))
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary btn-sm btn-admin-icon" title="Xóa bộ lọc"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tên phòng</th>
                            <th>Ảnh</th>
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
                                <td>
                                    @php $imgUrl = $room->getDisplayImageUrls()[0] ?? null; @endphp
                                    @if($imgUrl)
                                        <img src="{{ $imgUrl }}" alt="{{ $room->name }}" style="width: 60px; height: 45px; object-fit: cover; border-radius: 6px;">
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>{{ $room->type }}</td>
                                <td>{{ number_format($room->catalogueBasePrice(), 0, ',', '.') }} VNĐ</td>
                                <td>{{ $room->catalogueMaxGuests() }}</td>
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
                                    <div class="admin-action-row">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-admin-icon"
                                                title="Xem chi tiết"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailModal{{ $room->id }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <a href="{{ route('admin.rooms.edit', $room->id) }}"
                                           class="btn btn-sm btn-outline-warning btn-admin-icon"
                                           title="Sửa"><i class="bi bi-pencil-square"></i></a>

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
        <div class="card-footer bg-white border-0 py-2">
            {{ $rooms->links() }}
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
                        @if($room->image)
                            <div class="ratio ratio-4x3 rounded-3 overflow-hidden shadow-sm bg-light">
                                <img src="{{ \App\Models\Room::resolveImageUrl($room->image) }}"
                                     alt="{{ $room->name }}"
                                     class="w-100 h-100"
                                     style="object-fit: cover;">
                            </div>
                        @elseif($room->roomType && $room->roomType->image_url)
                            <div class="ratio ratio-4x3 rounded-3 overflow-hidden shadow-sm bg-light">
                                <img src="{{ $room->roomType->image_url }}"
                                     alt="{{ $room->roomType->name }}"
                                     class="w-100 h-100"
                                     style="object-fit: cover;">
                            </div>
                        @else
                            <div class="ratio ratio-4x3 rounded-3 border bg-light d-flex align-items-center justify-content-center">
                                <span class="text-muted">Không có ảnh</span>
                            </div>
                        @endif
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
                                <td><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $room->catalogueMaxGuests() }} người</span>@if($room->room_type_id)<span class="text-muted small ms-1">(theo loại)</span>@endif</td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-door-open me-2"></i>Số giường</th>
                                <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $room->catalogueBeds() }} giường</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-droplet me-2"></i>Số phòng tắm</th>
                                <td><span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">{{ $room->catalogueBaths() }} phòng</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-rulers me-2"></i>Diện tích</th>
                                <td class="text-dark">{{ $room->area ?? 0 }} m²</td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-semibold"><i class="bi bi-currency-dollar me-2"></i>Giá cơ bản</th>
                                <td class="fw-bold text-danger">{{ number_format($room->catalogueBasePrice(), 0, ',', '.') }} VNĐ</td>
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
                <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-outline-warning btn-admin-icon" title="Sửa"><i class="bi bi-pencil-square"></i></a>
                <button type="button" class="btn btn-outline-secondary btn-admin-icon" data-bs-dismiss="modal" title="Đóng"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
</div>
</div>
@endforeach
@endsection
