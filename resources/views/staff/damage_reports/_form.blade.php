<div class="row g-3">

    <div class="col-12">
        <label class="form-label">Tiêu đề *</label>
        <input type="text" name="title"
               class="form-control @error('title') is-invalid @enderror"
               placeholder="Nhập tiêu đề..."
               value="{{ old('title', $report->title ?? '') }}">

        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Mô tả</label>
        <textarea name="description" rows="4"
                  class="form-control"
                  placeholder="Mô tả chi tiết...">{{ old('description', $report->description ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
    <label class="form-label">Phòng *</label>
    <select name="room_id" class="form-select @error('room_id') is-invalid @enderror">

        <option value="">-- Chọn phòng --</option>

        @foreach($rooms as $room)
            <option value="{{ $room->id }}"
                {{ old('room_id', $report->room_id ?? '') == $room->id ? 'selected' : '' }}>
                Phòng {{ $room->room_number ?? $room->id }}
            </option>
        @endforeach

    </select>

    @error('room_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

    <div class="col-md-6">
        <label class="form-label">Trạng thái</label>
        <select name="status" class="form-select">

    <option value="reported">Chờ xử lý</option>
    <option value="in_progress">Đang xử lý</option>
    <option value="resolved">Hoàn thành</option>
    <option value="cancelled">Đã huỷ</option>

</select>
    </div>

    <div class="col-12 text-end mt-3">
        <a href="{{ route('staff.damage-reports.index') }}" class="btn btn-secondary">
            Huỷ
        </a>
        <button class="btn btn-primary">
            Lưu
        </button>
    </div>

</div>