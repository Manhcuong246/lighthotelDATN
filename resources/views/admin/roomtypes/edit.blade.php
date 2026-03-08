@extends('layouts.admin')

@section('title', 'Cập nhật loại phòng')

@section('content')
<div class="container-fluid px-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark fw-bold">Cập nhật loại phòng</h1>

        <a href="{{ route('admin.roomtypes.index') }}" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>


    <!-- Hiển thị lỗi -->
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
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

            <form action="{{ route('admin.roomtypes.update', $roomType->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">

                    <!-- Tên loại phòng -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Tên loại phòng</label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name', $roomType->name) }}"
                               required>
                    </div>

                    <!-- Số người -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Số người</label>
                        <input type="number"
                               name="capacity"
                               class="form-control"
                               value="{{ old('capacity', $roomType->capacity) }}"
                               required>
                    </div>

                    <!-- Giá phòng -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Giá phòng</label>
                        <input type="number"
                               name="price"
                               class="form-control"
                               value="{{ old('price', $roomType->price) }}"
                               required>
                    </div>

                    <!-- Mô tả -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description"
                                  rows="4"
                                  class="form-control">{{ old('description', $roomType->description) }}</textarea>
                    </div>

                    <!-- Trạng thái -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>

                        <select name="status" class="form-select">
                            <option value="1" {{ $roomType->status == 1 ? 'selected' : '' }}>
                                Hiển thị
                            </option>

                            <option value="0" {{ $roomType->status == 0 ? 'selected' : '' }}>
                                Ẩn
                            </option>
                        </select>
                    </div>

                </div>

                <!-- Button -->
                <div class="d-flex justify-content-end gap-2 mt-3">

                    <a href="{{ route('admin.roomtypes.index') }}"
                       class="btn btn-light border">
                        Hủy
                    </a>

                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-save"></i> Cập nhật
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
@endsection