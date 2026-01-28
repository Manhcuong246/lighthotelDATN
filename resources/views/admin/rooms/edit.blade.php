@extends('layouts.app')

@section('title', 'Cập nhật phòng')

@section('content')
    <h2 class="mb-3">Cập nhật phòng: {{ $room->name }}</h2>

    <form method="POST" action="{{ route('admin.rooms.update', $room) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tên phòng</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $room->name) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Loại phòng</label>
                <input type="text" name="type" class="form-control" value="{{ old('type', $room->type) }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Giá cơ bản (VNĐ/đêm)</label>
                <input type="number" name="base_price" class="form-control"
                       value="{{ old('base_price', $room->base_price) }}" min="0" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Tối đa khách</label>
                <input type="number" name="max_guests" class="form-control"
                       value="{{ old('max_guests', $room->max_guests) }}" min="1" required>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Số giường</label>
                <input type="number" name="beds" class="form-control"
                       value="{{ old('beds', $room->beds) }}" min="1" required>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Số phòng tắm</label>
                <input type="number" name="baths" class="form-control"
                       value="{{ old('baths', $room->baths) }}" min="0" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Diện tích (m²)</label>
            <input type="number" step="0.1" name="area" class="form-control"
                   value="{{ old('area', $room->area) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="4">{{ old('description', $room->description) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" required>
                <option value="available" {{ old('status', $room->status) === 'available' ? 'selected' : '' }}>Sẵn sàng</option>
                <option value="booked" {{ old('status', $room->status) === 'booked' ? 'selected' : '' }}>Đã đặt</option>
                <option value="maintenance" {{ old('status', $room->status) === 'maintenance' ? 'selected' : '' }}>Bảo trì</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
@endsection


