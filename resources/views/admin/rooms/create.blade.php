@extends('layouts.admin')

@section('title', 'Thêm phòng mới')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Thêm phòng mới</h1>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">Quay lại</a>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3">
            <h5 class="mb-0">Thông tin phòng</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.rooms.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tên phòng</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Loại phòng</label>
                        <input type="text" name="type" class="form-control" value="{{ old('type') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Giá cơ bản (VNĐ/đêm)</label>
                        <input type="number" name="base_price" class="form-control" value="{{ old('base_price') }}" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tối đa khách</label>
                        <input type="number" name="max_guests" class="form-control" value="{{ old('max_guests') }}" min="1" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Số giường</label>
                        <input type="number" name="beds" class="form-control" value="{{ old('beds') }}" min="1" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Số phòng tắm</label>
                        <input type="number" name="baths" class="form-control" value="{{ old('baths') }}" min="0" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Diện tích (m²)</label>
                    <input type="number" step="0.1" name="area" class="form-control" value="{{ old('area') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select" required>
                        <option value="available" {{ old('status') === 'available' ? 'selected' : '' }}>Sẵn sàng</option>
                        <option value="booked" {{ old('status') === 'booked' ? 'selected' : '' }}>Đã đặt</option>
                        <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Bảo trì</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Lưu</button>
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-secondary">Hủy</a>
            </form>
        </div>
    </div>
</div>
@endsection
