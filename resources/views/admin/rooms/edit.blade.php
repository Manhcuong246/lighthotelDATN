@extends('layouts.admin')

@section('title', 'Cập nhật phòng')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Cập nhật phòng: {{ $room->name }}</h1>
        <div class="d-flex gap-2">
            <button type="submit" form="room-edit-form" class="btn btn-primary d-none d-md-inline-block" title="Cập nhật"><i class="bi bi-check2-lg"></i> Cập nhật</button>
            <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary btn-admin-icon" title="Quay lại"><i class="bi bi-arrow-left"></i></a>
        </div>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3">
            <h5 class="mb-0">Thông tin phòng</h5>
        </div>
        <div class="card-body">
            <form id="room-edit-form" method="POST" action="{{ route('admin.rooms.update', $room) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
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
                                    {{ old('room_type_id', $room->room_type_id) == $rt->id ? 'selected' : '' }}>
                                    {{ $rt->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Số phòng</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $room->name) }}" required placeholder="Nhập số (ví dụ: 1, 101...)">
                        <small class="text-muted">Tự động thêm "Phòng" và format số</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ảnh phòng</label>
                    @if($room->images->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @foreach($room->images as $img)
                        <div class="position-relative d-inline-block room-image-item" style="width: 80px;">
                            <img src="{{ \App\Models\Room::resolveImageUrl($img->image_url) ?? '' }}"
                                 alt=""
                                 class="img-thumbnail room-image-thumb"
                                 style="width: 80px; height: 60px; object-fit: cover;">
                            <input
                                type="checkbox"
                                id="remove_image_{{ $img->id }}"
                                name="remove_images[]"
                                value="{{ $img->id }}"
                                class="room-remove-image-checkbox visually-hidden">

                            <button
                                type="button"
                                class="position-absolute top-0 end-0 m-1 btn btn-sm btn-danger p-1 rounded-circle room-remove-image-btn"
                                style="cursor: pointer; line-height: 1; z-index: 10;"
                                title="Đánh dấu xóa ảnh"
                                data-checkbox-id="remove_image_{{ $img->id }}"
                                aria-pressed="false"
                            >
                                <i class="bi bi-x"></i>
                            </button>

                            <div class="position-absolute top-0 start-0 w-100 h-100 rounded-2 room-image-remove-overlay"
                                 style="display:none; background: rgba(220,53,69,0.20); border: 2px solid rgba(220,53,69,0.55);">
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mb-1">Tick icon X để xóa ảnh khi cập nhật</small>
                    @endif
                    <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" multiple>
                    <small class="text-muted">Thêm ảnh mới (tối đa 4 ảnh, mỗi ảnh &lt; 2MB)</small>
                </div>
                <div class="alert alert-light border small mb-3 mb-md-4 text-muted">
                    <strong class="text-dark">Giá, tối đa khách, giường, tắm</strong> khi có <strong>loại phòng</strong> luôn lấy từ cấu hình loại phòng (cập nhật tại <a href="{{ route('admin.roomtypes.index') }}" class="link-primary">Quản lý loại phòng</a>). Phần lưu DB phòng được đồng bộ khi bạn Lưu — không cần (và không nên) sửa tay các ô đã khóa.
                    <span class="d-block mt-1">Diện tích và mô tả bên dưới là thông tin riêng từng phòng vật lý.</span>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Giá cơ bản (VNĐ/đêm)</label>
                        <input type="number" name="base_price" id="base_price" class="form-control js-room-catalogue-input"
                               value="{{ old('base_price', $room->base_price) }}" min="0" step="1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tối đa khách</label>
                        <input type="number" name="max_guests" id="max_guests" class="form-control js-room-catalogue-input"
                               value="{{ old('max_guests', $room->max_guests) }}" min="1" step="1">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Số giường</label>
                        <input type="number" name="beds" id="beds" class="form-control js-room-catalogue-input"
                               value="{{ old('beds', $room->beds) }}" min="1" step="1">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Số phòng tắm</label>
                        <input type="number" name="baths" id="baths" class="form-control js-room-catalogue-input"
                               value="{{ old('baths', $room->baths) }}" min="0" step="1">
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
                <button type="submit" class="btn btn-primary" title="Cập nhật"><i class="bi bi-check2-lg"></i> Cập nhật</button>
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-secondary btn-admin-icon" title="Quay lại"><i class="bi bi-arrow-left"></i></a>
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

    // Toggle chọn ảnh cần xóa (mobile-friendly)
    document.querySelectorAll('.room-remove-image-btn').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const checkboxId = this.getAttribute('data-checkbox-id');
            const checkbox = document.getElementById(checkboxId);
            if (!checkbox) return;

            // Xóa ảnh tức thì khỏi giao diện (nhưng vẫn giữ input để gửi lên server)
            if (confirm('Xóa ảnh này khỏi danh sách? (Sẽ được lưu vĩnh viễn khi bạn nhấn Cập nhật)')) {
                checkbox.checked = true;
                const item = this.closest('.room-image-item');
                if (item) {
                    item.style.transition = 'opacity 0.3s, transform 0.3s';
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            }
        });
    });
    
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
        
        // Cập nhật lại tên phòng nếu đã có số phòng
        let currentNameValue = nameInput.value.trim();
        if (currentNameValue) {
            // Lấy số từ tên hiện tại
            let numberOnly = currentNameValue.replace(/[^0-9]/g, '');
            if (numberOnly) {
                // Format lại với prefix mới
                let formattedNumber = numberOnly.padStart(3, '0');
                let prefix = 'Phòng';
                if (roomTypeSelect.value) {
                    const selectedOption = roomTypeSelect.options[roomTypeSelect.selectedIndex];
                    prefix = selectedOption.dataset.name || 'Phòng';
                }
                nameInput.value = prefix + ' ' + formattedNumber;
            }
        }
    });
    applyCatalogueFromType();
});
</script>
@endsection
