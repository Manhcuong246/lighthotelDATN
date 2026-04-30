@extends('layouts.admin')

@section('title', 'Thêm loại phòng')

@section('content')
<div class="container-fluid px-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark fw-bold">Thêm loại phòng</h1>

        <a href="{{ route('admin.roomtypes.index') }}" class="btn btn-outline-secondary shadow-sm btn-admin-icon" title="Quay lại"><i class="bi bi-arrow-left"></i></a>
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

                    <!-- Số người tiêu chuẩn (không phụ phí) -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Tiêu chuẩn (không phụ phí)</label>
                        <input type="number"
                               name="standard_capacity"
                               class="form-control"
                               placeholder="Ví dụ: 2"
                               value="{{ old('standard_capacity') }}"
                               min="1"
                               required>
                        <small class="text-muted">Chỉ thiết lập khi tạo mới. Vượt tiêu chuẩn sẽ tính phụ phí.</small>
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

                    <div class="col-12 mb-3">
                        <label class="form-label fw-semibold">Dịch vụ đi kèm có sẵn</label>
                        <p class="small text-muted mb-2">Chọn từ <a href="{{ route('admin.services.index') }}" target="_blank" rel="noopener">danh mục dịch vụ</a>. Khách tìm phòng có thể lọc theo các dịch vụ này.</p>
                        <div class="row g-2 border rounded p-3 bg-light">
                            @forelse($services as $svc)
                                <div class="col-md-4 col-lg-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="service_ids[]" value="{{ $svc->id }}" id="svc_rt_new_{{ $svc->id }}"
                                            {{ in_array($svc->id, old('service_ids', []), true) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="svc_rt_new_{{ $svc->id }}">{{ $svc->name }}</label>
                                    </div>
                                </div>
                            @empty
                                <p class="small text-warning mb-0">Chưa có dịch vụ — <a href="{{ route('admin.services.create') }}">thêm dịch vụ</a>.</p>
                            @endforelse
                        </div>
                    </div>

                </div>

                <!-- Button -->
                <div class="d-flex justify-content-end gap-2 mt-3">

                    <a href="{{ route('admin.roomtypes.index') }}"
                       class="btn btn-outline-secondary btn-admin-icon"
                       title="Hủy"><i class="bi bi-x-lg"></i></a>

                    <button type="submit" class="btn btn-success" title="Lưu"><i class="bi bi-check2-lg"></i> Lưu</button>

                </div>

            </form>

        </div>
    </div>

</div>
@endsection