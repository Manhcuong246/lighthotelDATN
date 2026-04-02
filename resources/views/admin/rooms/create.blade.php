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
<<<<<<< HEAD
            <form method="POST" action="{{ route('admin.rooms.store') }}">
=======
            <form method="POST" action="{{ route('admin.rooms.store') }}" enctype="multipart/form-data">
>>>>>>> vinam
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Loại phòng</label>
                        <select name="room_type_id" id="room_type_id" class="form-select">
                            <option value="">-- Chọn loại phòng --</option>
                            @foreach($roomTypes as $rt)
                                <option value="{{ $rt->id }}" 
                                    data-name="{{ $rt->name }}" 
                                    data-capacity="{{ $rt->capacity }}" 
                                    data-price="{{ $rt->price }}"
                                    data-beds="{{ $rt->beds }}"
                                    data-baths="{{ $rt->baths }}"
                                    data-image="{{ $rt->image }}"
                                    {{ old('room_type_id') == $rt->id ? 'selected' : '' }}>
                                    {{ $rt->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Số phòng</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}"required placeholder="Nhập số (ví dụ: 1, 101...)">
                        <small class="text-muted">Tự động thêm "Phòng" và format số</small>
                    </div>
                </div>
<<<<<<< HEAD
=======
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ảnh phòng</label>
                    <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" multiple>
                    <small class="text-muted">Có thể chọn nhiều ảnh (tối đa 4 ảnh, mỗi ảnh &lt; 2MB)</small>
                </div>
>>>>>>> vinam
                <!-- Hiển thị ảnh từ loại phòng -->
                <div class="mb-3" id="roomTypeImagePreview" style="display: none;">
                    <label class="form-label fw-semibold">Ảnh từ loại phòng:</label>
                    <div>
                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 200px;">
                    </div>
                    <small class="text-muted">Phòng này sẽ sử dụng ảnh từ loại phòng</small>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Giá cơ bản (VNĐ/đêm)</label>
<<<<<<< HEAD
                        <input type="number" name="base_price" class="form-control" value="{{ old('base_price') }}" min="0" required>
=======
                        <input type="number" name="base_price" class="form-control" value="{{ old('base_price') }}" min="0" required readonly>
>>>>>>> vinam
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomTypeSelect = document.getElementById('room_type_id');
    const nameInput = document.getElementById('name');
    const basePriceInput = document.querySelector('input[name="base_price"]');
    const maxGuestsInput = document.querySelector('input[name="max_guests"]');
    const bedsInput = document.querySelector('input[name="beds"]');
    const bathsInput = document.querySelector('input[name="baths"]');
    const imagePreviewDiv = document.getElementById('roomTypeImagePreview');
    const previewImg = document.getElementById('previewImg');
    
    // Format số phòng khi blur (mất focus)
    nameInput.addEventListener('blur', function() {
        let value = this.value.trim();
        if (value) {
            // Chỉ lấy số
            let numberOnly = value.replace(/[^0-9]/g, '');
            if (numberOnly) {
                // Format với 3 chữ số (001, 002, ...)
                let formattedNumber = numberOnly.padStart(3, '0');
                this.value = 'Phòng ' + formattedNumber;
            }
        }
    });
    
    // Xóa "Phòng" khi focus để dễ chỉnh sửa
    nameInput.addEventListener('focus', function() {
        let value = this.value;
        if (value.startsWith('Phòng ')) {
            this.value = value.replace('Phòng ', '').trim();
        }
    });
    
    roomTypeSelect.addEventListener('change', function() {
        const selectedOption= this.options[this.selectedIndex];
        if (this.value) {
            // Tự động điền giá và thông tin từ loại phòng (KHÔNG điền tên phòng)
            if (!basePriceInput.value || basePriceInput.value === '0') {
                basePriceInput.value = selectedOption.dataset.price || '';
            }
            if (!maxGuestsInput.value || maxGuestsInput.value === '1') {
                maxGuestsInput.value = selectedOption.dataset.capacity || '';
            }
            // Điền số giường và số phòng tắm
            if (!bedsInput.value || bedsInput.value === '1') {
                bedsInput.value = selectedOption.dataset.beds || '1';
            }
            if (!bathsInput.value || bathsInput.value === '1') {
                bathsInput.value = selectedOption.dataset.baths || '1';
            }
            
            // Hiển thị ảnh từ loại phòng
            const imageData = selectedOption.dataset.image;
            if (imageData && imageData !== '') {
                previewImg.src = '/storage/' + imageData;
                imagePreviewDiv.style.display = 'block';
            } else {
                imagePreviewDiv.style.display = 'none';
            }
        } else {
            imagePreviewDiv.style.display = 'none';
        }
    });
});
</script>
@endsection
