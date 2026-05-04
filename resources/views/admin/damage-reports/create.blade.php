@extends('layouts.admin')

@section('title', 'Tạo báo cáo hư hỏng')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('admin.damage-reports.index') }}" class="btn btn-outline-secondary btn-sm btn-admin-icon" title="Quay lại"><i class="bi bi-arrow-left"></i></a>
    </div>

    <h1 class="text-dark fw-bold mb-4">🚨 Tạo báo cáo hư hỏng</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.damage-reports.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <!-- Room Selection -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">🏨 Chọn Phòng</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Phòng *</label>
                            <select name="room_id" class="form-select @error('room_id') is-invalid @enderror" required>
                                <option value="">-- Chọn phòng --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                        Phòng {{ $room->room_number }} - {{ $room->roomType->name ?? 'Không xác định' }}
                                        @if($room->status === 'maintenance')
                                            (Đang bảo trì)
                                        @elseif($room->status === 'booked')
                                            (Có khách)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('room_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Lưu ý:</strong> Mức <strong>Cao</strong> hoặc <strong>Khẩn cấp</strong> sẽ tự chuyển phòng sang trạng thái bảo trì.
                        </div>
                    </div>
                </div>

                <!-- Damage Details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">🔧 Chi tiết hư hỏng</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Loại lỗi *</label>
                                <select name="damage_type" class="form-select @error('damage_type') is-invalid @enderror" required>
                                    <option value="">-- Chọn loại lỗi --</option>
                                    @foreach($damageTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('damage_type') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('damage_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mức độ *</label>
                                <select name="severity" class="form-select @error('severity') is-invalid @enderror" required>
                                    <option value="low" {{ old('severity') == 'low' ? 'selected' : '' }}>🟢 Thấp - Không ảnh hưởng sử dụng</option>
                                    <option value="medium" {{ old('severity') == 'medium' ? 'selected' : '' }}>🟡 Trung bình - Có thể sử dụng</option>
                                    <option value="high" {{ old('severity') == 'high' ? 'selected' : '' }}>🟠 Cao - Không nên sử dụng</option>
                                    <option value="urgent" {{ old('severity') == 'urgent' ? 'selected' : '' }}>🔴 Khẩn cấp - Không thể sử dụng</option>
                                </select>
                                @error('severity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả chi tiết *</label>
                            <textarea name="description" rows="5" class="form-control @error('description') is-invalid @enderror"
                                placeholder="Mô tả chi tiết vấn đề hư hỏng, ví dụ: Giường bị gãy chân trước bên trái, nệm rơi xuống sàn..." required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Action Buttons -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">⚡ Hành động</h5>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-danger w-100 mb-3 d-flex align-items-center justify-content-center btn-admin-icon" style="height: 2.5rem;" title="Tạo báo cáo">
                            <i class="bi bi-exclamation-triangle"></i>
                        </button>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading"><i class="bi bi-bell"></i> Hệ thống sẽ:</h6>
                            <ul class="mb-0 small">
                                <li>Ghi nhận báo cáo hư hỏng</li>
                                <li>Với lỗi nghiêm trọng: Đặt phòng vào bảo trì</li>
                                <li>Kiểm tra nếu phòng có khách đang ở</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Quick Guide -->
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">📋 Hướng dẫn</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2">
                                <span class="badge bg-danger">Khẩn cấp</span>
                                <br>Phòng không thể sử dụng, cần sửa ngay
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-warning">Cao</span>
                                <br>Không nên đặt phòng, ưu tiên sửa chữa
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-secondary">Trung bình</span>
                                <br>Có thể sử dụng nhưng cần ghi nhận
                            </li>
                            <li>
                                <span class="badge bg-light text-dark">Thấp</span>
                                <br>Lỗi nhỏ, không ảnh hưởng
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
