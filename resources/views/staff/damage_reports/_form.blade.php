@php
    $report = $report ?? null;
    $damageTypes = $damageTypes ?? \App\Models\DamageReport::getDamageTypes();
    $severityLabels = \App\Models\DamageReport::getSeverityLabels();
@endphp

<div class="row g-3">

    <div class="col-12">
        <label class="form-label">Loại hư hỏng *</label>
        <select name="damage_type" class="form-select @error('damage_type') is-invalid @enderror" required>
            <option value="">-- Chọn loại --</option>
            @foreach($damageTypes as $value => $label)
                <option value="{{ $value }}"
                    {{ old('damage_type', optional($report)->damage_type) === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('damage_type')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Mô tả chi tiết *</label>
        <textarea name="description" rows="4"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Mô tả chi tiết vấn đề..."
                  required>{{ old('description', optional($report)->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Mức độ nghiêm trọng *</label>
        <select name="severity" class="form-select @error('severity') is-invalid @enderror" required>
            @foreach($severityLabels as $value => $label)
                <option value="{{ $value }}"
                    {{ old('severity', optional($report)->severity ?? 'medium') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('severity')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Phòng *</label>
        <select name="room_id" class="form-select @error('room_id') is-invalid @enderror" required>
            <option value="">-- Chọn phòng --</option>
            @foreach($rooms as $room)
                @php
                    $rn = $room->room_number ?: $room->name;
                    $typeName = $room->roomType->name ?? null;
                    $roomLabel = $typeName ? "Phòng {$rn} — {$typeName}" : "Phòng {$rn}";
                @endphp
                <option value="{{ $room->id }}"
                    {{ (string) old('room_id', optional($report)->room_id) === (string) $room->id ? 'selected' : '' }}>
                    {{ $roomLabel }}
                </option>
            @endforeach
        </select>
        @error('room_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    @isset($report)
    <div class="col-md-6">
        <label class="form-label">Trạng thái *</label>
        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
            @php $st = old('status', optional($report)->status ?? 'reported'); @endphp
            <option value="reported" {{ $st === 'reported' ? 'selected' : '' }}>Chờ xử lý</option>
            <option value="in_progress" {{ $st === 'in_progress' ? 'selected' : '' }}>Đang xử lý</option>
            <option value="resolved" {{ $st === 'resolved' ? 'selected' : '' }}>Hoàn thành</option>
            <option value="cancelled" {{ $st === 'cancelled' ? 'selected' : '' }}>Đã huỷ</option>
        </select>
        @error('status')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    @endisset

    <div class="col-12 text-end mt-3">
        <a href="{{ route('staff.damage-reports.index') }}" class="btn btn-secondary">
            Huỷ
        </a>
        <button type="submit" class="btn btn-primary">
            Lưu
        </button>
    </div>

</div>
