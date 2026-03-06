@extends('layouts.admin')

@section('content')
<div class="container mt-4">
    <h2>✏️ Cập nhật loại phòng</h2>

    {{-- Hiển thị lỗi --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.roomtypes.update', $roomType->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Tên loại phòng -->
        <div class="mb-3">
            <label class="form-label">Tên loại phòng</label>
            <input type="text" name="name" class="form-control" value="{{ $roomType->name }}" required>
        </div>

        <!-- Mô tả -->
        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control" rows="3">{{ $roomType->description }}</textarea>
        </div>

        <!-- Trạng thái -->
        <div class="mb-3">
            <label class="form-label">Trạng thái</label>
           <select name="status" class="form-select">
    <option value="1" {{ $roomType->status == 1 ? 'selected' : '' }}>Hoạt động</option>
    <option value="0" {{ $roomType->status == 0 ? 'selected' : '' }}>Ẩn</option>
</select>
        </div>

        <!-- Nút -->
        <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
        <a href="{{ route('admin.roomtypes.index') }}" class="btn btn-secondary">⬅ Quay lại</a>
    </form>
</div>
@endsection
