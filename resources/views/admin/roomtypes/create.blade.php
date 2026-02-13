@extends('layouts.admin')

@section('title', 'Thêm loại phòng')

@section('content')
<div class="container-fluid px-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Thêm loại phòng</h1>

        <a href="{{ route('admin.roomtypes.index') }}" class="btn btn-secondary">
            ← Quay lại
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-light fw-bold">
            Thông tin loại phòng
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.roomtypes.store') }}">
                @csrf

                <!-- Tên loại phòng -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên loại phòng</label>
                    <input type="text" name="name" class="form-control"
                           placeholder="Ví dụ: Phòng đơn, Phòng VIP..." required>
                </div>

                <!-- Mô tả -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả</label>
                    <textarea name="description" rows="4" class="form-control"
                              placeholder="Mô tả loại phòng..."></textarea>
                </div>

                <!-- Trạng thái -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="1">Hiển thị</option>
                        <option value="0">Ẩn</option>
                    </select>
                </div>

                <!-- Nút -->
                <div class="d-flex justify-content-end">
                    <button class="btn btn-success px-4">
                        💾 Lưu loại phòng
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection
