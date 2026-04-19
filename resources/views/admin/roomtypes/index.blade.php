@extends('layouts.admin')

@section('title', 'Quản lý loại phòng')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="text-dark fw-bold">Quản lý loại phòng</h1>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <form action="{{ route('admin.roomtypes.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm tên loại phòng..." style="width: 200px;">
                <button type="submit" class="btn btn-primary btn-sm btn-admin-icon" title="Tìm"><i class="bi bi-search"></i></button>
                @if(request('q'))
                <a href="{{ route('admin.roomtypes.index') }}" class="btn btn-outline-secondary btn-sm btn-admin-icon" title="Xóa bộ lọc"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
            <a href="{{ route('admin.roomtypes.create') }}" class="btn btn-primary btn-sm btn-admin-icon" title="Thêm loại phòng"><i class="bi bi-plus-lg"></i></a>
        </div>
    </div>

    <!-- Alert -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif


    <!-- Card -->
    <div class="card shadow border-0">

        <!-- Card header -->
        <div class="card-header bg-primary text-white fw-semibold">
            Danh sách loại phòng
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th width="60">ID</th>
                            <th>Ảnh</th>
                            <th>Tên loại phòng</th>
                            <th>Giá phòng</th>
                            <th>Trạng thái</th>
                            <th width="150" class="text-center">Hành động</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($roomTypes as $type)

                        <tr>

                            <td class="text-muted">{{ $type->id }}</td>

                            <td>
                                @if($type->image_url)
                                    <img src="{{ $type->image_url }}" alt="{{ $type->name }}" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="width: 80px; height: 60px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; display: none; align-items: center; justify-content: center; font-size: 10px; color: #666;">
                                        No Image
                                    </div>
                                @else
                                    <div style="width: 80px; height: 60px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #666;">
                                        No Image
                                    </div>
                                @endif
                            </td>

                            <td class="fw-semibold">
                                {{ $type->name }}
                            </td>

                            <td class="fw-semibold text-danger">
                                {{ number_format($type->price, 0, ',', '.') }} VNĐ
                            </td>

                            <td>
                                @if($type->status)
                                    <span class="badge bg-success">
                                        Hiển thị
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        Ẩn
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="admin-action-row justify-content-center">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary btn-admin-icon"
                                            title="Xem chi tiết"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailModal{{ $type->id }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="{{ route('admin.roomtypes.edit', $type->id) }}"
                                       class="btn btn-sm btn-outline-warning btn-admin-icon"
                                       title="Sửa"><i class="bi bi-pencil-square"></i></a>
                                    <form action="{{ route('admin.roomtypes.destroy', $type->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa loại phòng này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-admin-icon" title="Xóa"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>

                        </tr>

                        @empty

                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Chưa có loại phòng nào
                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi tiết loại phòng -->
@foreach($roomTypes as $type)
<div class="modal fade" id="detailModal{{ $type->id }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $type->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailModalLabel{{ $type->id }}">Chi tiết loại phòng: {{ $type->name }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                    <div class="row">
                    <div class="col-md-6 mb-3">
                        @if($type->image_url)
                            <div class="rounded overflow-hidden shadow-sm bg-light" style="max-height: 300px;">
                                <img src="{{ $type->image_url }}" alt="{{ $type->name }}" class="img-fluid w-100 d-block" style="max-height: 300px; object-fit: cover;"
                                     onerror="this.style.display='none'; document.getElementById('roomtype-img-fb-{{ $type->id }}')?.classList.remove('d-none');">
                            </div>
                            <p id="roomtype-img-fb-{{ $type->id }}" class="text-muted small mb-0 mt-2 d-none">Không tải được ảnh.</p>
                        @else
                            <p class="text-muted small fst-italic mb-0">Chưa có ảnh đại diện.</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><i class="bi bi-tag"></i> Tên loại:</th>
                                <td>{{ $type->name }}</td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-people"></i> Sức chứa:</th>
                                <td><span class="badge bg-info">{{ $type->capacity }} người</span></td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-door-open"></i> Số giường:</th>
                                <td><span class="badge bg-warning text-dark">{{ $type->beds ?? 1 }} giường</span></td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-droplet"></i> Số phòng tắm:</th>
                                <td><span class="badge bg-secondary">{{ $type->baths ?? 1 }} phòng</span></td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-currency-dollar"></i> Giá phòng:</th>
                                <td class="fw-bold text-danger">{{ number_format($type->price, 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-patch-check"></i> Trạng thái:</th>
                                <td>
                                    @if($type->status)
                                        <span class="badge bg-success">Hiển thị</span>
                                    @else
                                        <span class="badge bg-secondary">Ẩn</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-card-text"></i> Mô tả:</th>
                                <td>{{ $type->description ?? 'Không có mô tả' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('admin.roomtypes.edit', $type->id) }}" class="btn btn-outline-warning btn-admin-icon" title="Sửa"><i class="bi bi-pencil-square"></i></a>
                <button type="button" class="btn btn-outline-secondary btn-admin-icon" data-bs-dismiss="modal" title="Đóng"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
