@extends('layouts.admin')

@section('title', 'Quản lý loại phòng')

@section('content')
<div class="container-fluid px-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark fw-bold">Quản lý loại phòng</h1>

        <a href="{{ route('admin.roomtypes.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Thêm loại phòng
        </a>
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
                            <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th width="150" class="text-center">Hành động</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($roomTypes as $type)

                        <tr>

                            <td class="text-muted">{{ $type->id }}</td>

                            <td>
                                @if($type->image)
                                    <img src="{{ asset('storage/' . $type->image) }}" alt="{{ $type->name }}" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                                @else
                                    <!-- show placeholder image when no upload -->
                                    <img src="{{ asset('storage/room_types/dummy.png') }}" alt="không có" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; opacity: .5;">
                                @endif
                            </td>

                            <td class="fw-semibold">
                                {{ $type->name }}
                            </td>

                            <td class="fw-semibold text-danger">
                                {{ number_format($type->price) }} VNĐ
                            </td>

                            <td class="text-muted">
                                {{ $type->description ?? 'Không có mô tả' }}
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
                                <!-- Nút xem chi tiết -->
                                <button type="button"
                                        class="btn btn-info btn-sm mb-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#detailModal{{ $type->id }}">
                                    <i class="bi bi-eye"></i>
                                </button>

                                <!-- Nút sửa -->
                                <a href="{{ route('admin.roomtypes.edit', $type->id) }}"
                                   class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('admin.roomtypes.destroy', $type->id) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa loại phòng này?')">

                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </form>

                            </td>

                        </tr>

                        @empty

                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
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
                        @if($type->image)
                            <img src="{{ asset('storage/' . $type->image) }}" alt="{{ $type->name }}" class="img-fluid rounded shadow-sm w-100" style="max-height: 300px; object-fit: cover;">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 300px;">
                                <span class="text-muted">Không có ảnh</span>
                            </div>
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
                <a href="{{ route('admin.roomtypes.edit', $type->id) }}" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Chỉnh sửa
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
