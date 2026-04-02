@extends('layouts.admin')

@section('title', 'Thêm loại phòng')

@section('content')
<div class="container-fluid admin-page px-2 px-lg-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark fw-bold">Thêm loại phòng</h1>

        <a href="{{ route('admin.roomtypes.index') }}" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>


    <!-- Hiển thị lỗi -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <!-- Card -->
    <div class="card shadow border-0">

        <div class="card-header bg-primary text-white fw-semibold">
            Thông tin loại phòng
        </div>

        <div class="card-body">

            <form method="POST" action="{{ route('admin.roomtypes.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row">

                    <!-- Tên loại phòng -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Tên loại phòng</label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               placeholder="Ví dụ: Phòng đơn, Phòng VIP..."
                               value="{{ old('name') }}"
                               required>
                    </div>

                    <!-- Số người -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Số người</label>
                        <input type="number"
                               name="capacity"
                               class="form-control"
                               placeholder="Ví dụ: 2"
                               value="{{ old('capacity') }}"
                               required>
                    </div>

                    <!-- Số giường -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Số giường</label>
                        <input type="number"
                               name="beds"
                               class="form-control"
                               placeholder="Ví dụ: 1"
                               value="{{ old('beds', 1) }}"
                               min="1"
                               required>
                    </div>

                    <!-- Số phòng tắm -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Số phòng tắm</label>
                        <input type="number"
                               name="baths"
                               class="form-control"
                               placeholder="Ví dụ: 1"
                               value="{{ old('baths', 1) }}"
                               min="0"
                               required>
                    </div>

                    <!-- Giá -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Giá phòng</label>
                        <input type="number"
                               name="price"
                               class="form-control"
                               placeholder="Ví dụ: 500000"
                               value="{{ old('price') }}"
                               required>
                    </div>

                    <!-- Mô tả -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description"
                                  rows="4"
                                  class="form-control"
                                  placeholder="Mô tả loại phòng...">{{ old('description') }}</textarea>
                    </div>

                    <!-- Ảnh đại diện -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Ảnh đại diện</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Định dạng: JPG, PNG, GIF. Tối đa 2MB</small>
                    </div>

                    <!-- Trạng thái -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="1">Hiển thị</option>
                            <option value="0">Ẩn</option>
                        </select>
                    </div>

                    <div class="col-md-8 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="is_non_refundable" id="is_non_refundable" value="1" {{ old('is_non_refundable') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_non_refundable">Non-refundable (không cho khách tự hủy / không hoàn tiền)</label>
                        </div>
                    </div>

                </div>

                <!-- Button -->
                <div class="d-flex justify-content-end gap-2 mt-3">

                    <a href="{{ route('admin.roomtypes.index') }}"
                       class="btn btn-light border">
                        Hủy
                    </a>

                    <button class="btn btn-success px-4">
                        <i class="bi bi-save"></i> Lưu loại phòng
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>
@endsection