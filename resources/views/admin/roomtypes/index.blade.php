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
                            <th>Tên loại phòng</th>
                            <th>Số người</th>
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

                            <td class="fw-semibold">
                                {{ $type->name }}
                            </td>

                            <td>
                                <span class="badge bg-info">
                                    {{ $type->capacity }} người
                                </span>
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
@endsection