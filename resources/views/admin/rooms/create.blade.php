@extends('layouts.admin')

@section('title', 'Thêm phòng mới')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Thêm phòng mới</h1>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary btn-admin-icon" title="Quay lại"><i class="bi bi-arrow-left"></i></a>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3">
            <h5 class="mb-0">Thông tin phòng</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.rooms.store') }}" enctype="multipart/form-data">
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
                                    data-image="{{ $rt->image_url ?? '' }}"
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
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ảnh phòng</label>
                    <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" multiple>
                    <small class="text-muted">Có thể chọn nhiều ảnh (tối đa 4 ảnh, mỗi ảnh &lt; 2MB)</small>
                </div>
                <!-- Hiển thị ảnh từ loại phòng -->
                <div class="mb-3" id="roomTypeImagePreview" style="display: none;">
                    <label class="form-label fw-semibold">Ảnh từ loại phòng:</label>
                    <div>
                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 200px;">
                    </div>
                    <small class="text-muted">Phòng này sẽ sử dụng ảnh từ loại phòng</small>
                </div>
                <div class="alert alert-light border small mb-3 mb-md-4 text-muted">
                    <strong class="text-dark">Giá, tối đa khách, giường, tắm</strong> khi chọn <strong>loại phòng</strong> sẽ lấy từ loại (sửa tại <a href="{{ route('admin.roomtypes.index') }}" class="link-primary">Quản lý loại phòng</a>). Không chọn loại thì nhập tay đủ bốn ô.
                    <span class="d-block mt-1">Diện tích và mô tả là thông tin riêng phòng vật lý.</span>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Giá cơ bản (VNĐ/đêm)</label>
                        <input type="number" name="base_price" id="base_price" class="form-control js-room-catalogue-input" value="{{ old('base_price') }}" min="0" step="1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tối đa khách</label>
                        <input type="number" name="max_guests" id="max_guests" class="form-control js-room-catalogue-input" value="{{ old('max_guests') }}" min="1" step="1">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Số giường</label>
                        <input type="number" name="beds" id="beds" class="form-control js-room-catalogue-input" value="{{ old('beds') }}" min="1" step="1">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Số phòng tắm</label>
                        <input type="number" name="baths" id="baths" class="form-control js-room-catalogue-input" value="{{ old('baths') }}" min="0" step="1">
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
                <button type="submit" class="btn btn-primary" title="Lưu"><i class="bi bi-check2-lg"></i> Lưu</button>
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-secondary btn-admin-icon" title="Hủy"><i class="bi bi-x-lg"></i></a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomTypeSelect = document.getElementById('room_type_id');
    const nameInput = document.getElementById('name');
    const basePriceInput = document.getElementById('base_price');
    const maxGuestsInput = document.getElementById('max_guests');
    const bedsInput = document.getElementById('beds');
    const bathsInput = document.getElementById('baths');
    const catalogueInputs = [basePriceInput, maxGuestsInput, bedsInput, bathsInput].filter(Boolean);
    const imagePreviewDiv = document.getElementById('roomTypeImagePreview');
    const previewImg = document.getElementById('previewImg');

    function applyCatalogueFromType() {
        const hasType = roomTypeSelect.value !== '';
        catalogueInputs.forEach(function (el) {
            el.readOnly = hasType;
            el.classList.toggle('bg-light', hasType);
            el.required = !hasType;
        });
        if (hasType) {
            const opt = roomTypeSelect.options[roomTypeSelect.selectedIndex];
            basePriceInput.value = opt.dataset.price || '';
            maxGuestsInput.value = opt.dataset.capacity || '';
            bedsInput.value = opt.dataset.beds || '1';
            bathsInput.value = opt.dataset.baths || '0';
        }
    }
    
    // Format số phòng khi blur (mất focus)
    nameInput.addEventListener('blur', function() {
        let value = this.value.trim();
        if (value) {
            // Chỉ lấy số
            let numberOnly = value.replace(/[^0-9]/g, '');
            if (numberOnly) {
                // Format với 3 chữ số (001, 002, ...)
                let formattedNumber = numberOnly.padStart(3, '0');
                
                // Lấy tên loại phòng làm prefix, nếu không có thì dùng "Phòng"
                let prefix = 'Phòng';
                if (roomTypeSelect.value) {
                    const selectedOption = roomTypeSelect.options[roomTypeSelect.selectedIndex];
                    prefix = selectedOption.dataset.name || 'Phòng';
                }
                
                this.value = prefix + ' ' + formattedNumber;
            }
        }
    });
    
    // Xóa prefix khi focus để dễ chỉnh sửa
    nameInput.addEventListener('focus', function() {
        let value = this.value;
        // Lấy prefix hiện tại (tên loại phòng hoặc "Phòng")
        let currentPrefix = 'Phòng';
        if (roomTypeSelect.value) {
            const selectedOption = roomTypeSelect.options[roomTypeSelect.selectedIndex];
            currentPrefix = selectedOption.dataset.name || 'Phòng';
        }
        
        if (value.startsWith(currentPrefix + ' ')) {
            this.value = value.replace(currentPrefix + ' ', '').trim();
        }
    });
    
    roomTypeSelect.addEventListener('change', function() {
        applyCatalogueFromType();
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            const imageData = selectedOption.dataset.image;
            if (imageData && imageData !== '') {
                previewImg.src = imageData;
                imagePreviewDiv.style.display = 'block';
            } else {
                imagePreviewDiv.style.display = 'none';
            }
            
            // Cập nhật lại tên phòng nếu đã có số phòng
            let currentNameValue = nameInput.value.trim();
            if (currentNameValue) {
                // Lấy số từ tên hiện tại
                let numberOnly = currentNameValue.replace(/[^0-9]/g, '');
                if (numberOnly) {
                    // Format lại với prefix mới
                    let formattedNumber = numberOnly.padStart(3, '0');
                    let prefix = selectedOption.dataset.name || 'Phòng';
                    nameInput.value = prefix + ' ' + formattedNumber;
                }
            }
        } else {
            imagePreviewDiv.style.display = 'none';
            
            // Reset tên phòng về "Phòng" khi không chọn loại phòng
            let currentNameValue = nameInput.value.trim();
            if (currentNameValue) {
                let numberOnly = currentNameValue.replace(/[^0-9]/g, '');
                if (numberOnly) {
                    let formattedNumber = numberOnly.padStart(3, '0');
                    nameInput.value = 'Phòng ' + formattedNumber;
                }
            }
        }
    });
    applyCatalogueFromType();
});
</script>
@endsection
