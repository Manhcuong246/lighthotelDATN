@extends('layouts.admin')

@section('title', 'Quản lý phòng')

@section('content')
<<<<<<< HEAD
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Quản lý phòng</h1>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm phòng mới</a>
=======
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Quản lý phòng</h1>
        @if(auth()->user()->canAccessAdmin())
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Thêm phòng</a>
>>>>>>> vinam
        @endif
    </div>

    <div class="card card-admin shadow mb-4">
<<<<<<< HEAD
        <div class="card-header-admin py-3">
            <h5 class="mb-0">Danh sách phòng</h5>
=======
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
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Tìm</button>
                @if(request()->hasAny(['q','status']))
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary btn-sm">Xóa bộ lọc</a>
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
<<<<<<< HEAD
                                    @if($room->image)
                                        <img src="{{ asset('storage/' . $room->image) }}" alt="{{ $room->name }}" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                                    @elseif($room->roomType && $room->roomType->image)
                                        <img src="{{ asset('storage/' . $room->roomType->image) }}" alt="{{ $room->roomType->name }}" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                                    @else
                                        <span class="text-muted">Không có</span>
=======
                                    @php $imgUrl = $room->getDisplayImageUrls()[0] ?? null; @endphp
                                    @if($imgUrl)
                                        <img src="{{ $imgUrl }}" alt="{{ $room->name }}" style="width: 60px; height: 45px; object-fit: cover; border-radius: 6px;">
                                    @else
                                        <span class="text-muted small">—</span>
>>>>>>> vinam
                                    @endif
                                </td>
                                <td>{{ $room->type }}</td>
                                <td>{{ number_format($room->base_price, 0, ',', '.') }} VNĐ</td>
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
                                <td>
                                   <!-- Nút xem chi tiết -->
                                   <button type="button" 
                                           class="btn btn-info btn-sm mb-1" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#detailModal{{ $room->id }}">
                                       <i class="bi bi-eye"></i>
                                   </button>
                                   
                                   <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-warning btn-sm">
    Sửa
</a>

                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa phòng này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                    @endif
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
<<<<<<< HEAD
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailModalLabel{{ $room->id }}">Chi tiết phòng: {{ $room->name }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        @if($room->image)
                            <img src="{{ asset('storage/' . $room->image) }}" alt="{{ $room->name }}" class="img-fluid rounded shadow-sm w-100" style="max-height: 300px; object-fit: cover;">
                        @elseif($room->roomType && $room->roomType->image)
                            <img src="{{ asset('storage/' . $room->roomType->image) }}" alt="{{ $room->roomType->name }}" class="img-fluid rounded shadow-sm w-100" style="max-height: 300px; object-fit: cover;">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 300px;">
=======
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
                                <img src="{{ asset('storage/' . $room->image) }}"
                                     alt="{{ $room->name }}"
                                     class="w-100 h-100"
                                     style="object-fit: cover;">
                            </div>
                        @elseif($room->roomType && $room->roomType->image)
                            <div class="ratio ratio-4x3 rounded-3 overflow-hidden shadow-sm bg-light">
                                <img src="{{ asset('storage/' . $room->roomType->image) }}"
                                     alt="{{ $room->roomType->name }}"
                                     class="w-100 h-100"
                                     style="object-fit: cover;">
                            </div>
                        @else
                            <div class="ratio ratio-4x3 rounded-3 border bg-light d-flex align-items-center justify-content-center">
>>>>>>> vinam
                                <span class="text-muted">Không có ảnh</span>
                            </div>
                        @endif
                    </div>
<<<<<<< HEAD
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><i class="bi bi-hash"></i> Số phòng:</th>
                                <td>{{ $room->room_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-tag"></i> Tên phòng:</th>
                                <td>{{ $room->name }}</td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-building"></i> Loại phòng:</th>
                                <td>{{ $room->type ?? 'Chưa phân loại' }}</td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-people"></i> Sức chứa:</th>
                                <td><span class="badge bg-info">{{ $room->max_guests }} người</span></td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-door-open"></i> Số giường:</th>
                                <td><span class="badge bg-warning text-dark">{{ $room->beds ?? 1 }} giường</span></td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-droplet"></i> Số phòng tắm:</th>
                                <td><span class="badge bg-secondary">{{ $room->baths ?? 1 }} phòng</span></td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-rulers"></i> Diện tích:</th>
                                <td>{{ $room->area ?? 0 }} m²</td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-currency-dollar"></i> Giá cơ bản:</th>
                                <td class="fw-bold text-danger">{{ number_format($room->base_price, 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-patch-check"></i> Trạng thái:</th>
                                <td>
                                    @if($room->status === 'available')
                                        <span class="badge bg-success">Sẵn sàng</span>
                                    @elseif($room->status === 'booked')
                                        <span class="badge bg-warning text-dark">Đã đặt</span>
                                    @else
                                        <span class="badge bg-secondary">Bảo trì</span>
=======
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
>>>>>>> vinam
                                    @endif
                                </td>
                            </tr>
                            <tr>
<<<<<<< HEAD
                                <th><i class="bi bi-card-text"></i> Mô tả:</th>
                                <td>{{ $room->description ?? 'Không có mô tả' }}</td>
=======
                                <th class="text-muted fw-semibold" style="vertical-align: top;"><i class="bi bi-card-text me-2"></i>Mô tả</th>
                                <td class="text-dark" style="white-space: normal;">
                                    {{ $room->description ?? 'Không có mô tả' }}
                                </td>
>>>>>>> vinam
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
<<<<<<< HEAD
                <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-warning">
=======
                <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-primary">
>>>>>>> vinam
                    <i class="bi bi-pencil"></i> Chỉnh sửa
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
<<<<<<< HEAD
    </div>
=======
</div>
>>>>>>> vinam
</div>
@endforeach
@endsection
