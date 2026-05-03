@extends('layouts.app')

@section('title', 'Đặt phòng')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Đặt phòng</h4>
                </div>

                <div class="card-body">
                    {{-- Thông tin loại phòng đã chọn --}}
                    <div class="alert alert-info">
                        <h6><i class="bi bi-house-door me-2"></i>Loại phòng đã chọn:</h6>
                        <div id="selectedRoomInfo">
                            <strong>{{ $roomType->name ?? 'Standard' }}</strong> -
                            <span class="text-muted">Số phòng sẽ được lễ tân bố trí khi check-in</span>
                        </div>
                    </div>

                    <form id="bookingForm" action="{{ route('bookings.store-simple') }}" method="POST">
                        @csrf
                        <input type="hidden" name="room_type_id" value="{{ $roomType->id }}">
                        <input type="hidden" name="check_in" value="{{ $checkIn }}">
                        <input type="hidden" name="check_out" value="{{ $checkOut }}">

                        {{-- Chọn số phòng --}}
                        <div class="mb-4">
                            <label for="rooms" class="form-label fw-bold">
                                <i class="bi bi-door-open me-2"></i>Số lượng phòng
                            </label>
                            <select name="rooms" id="rooms" class="form-select form-select-lg" required>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ $i == 1 ? 'selected' : '' }}>{{ $i }} phòng</option>
                                @endfor
                            </select>
                            <div class="form-text">Chọn số lượng phòng cần đặt (tính giá)</div>
                        </div>

                        {{-- Thông tin người đại diện - chỉ 1 form duy nhất --}}
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-person-fill me-2"></i>Thông tin người đại diện
                            </h5>

                            <div class="card border-primary">
                                <div class="card-header bg-primary bg-opacity-10">
                                    <h6 class="mb-0">
                                        <i class="bi bi-person-fill me-2"></i>Người đại diện
                                        <span class="badge bg-primary ms-2">Bắt buộc</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Họ và tên <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   name="name"
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   value="{{ old('name') }}"
                                                   placeholder="Nhập họ tên người đại diện"
                                                   required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text text-danger">Bắt buộc nhập</div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                CCCD/CMND <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   name="cccd"
                                                   class="form-control @error('cccd') is-invalid @enderror"
                                                   value="{{ old('cccd') }}"
                                                   placeholder="Nhập số CCCD"
                                                   maxlength="12"
                                                   required>
                                            @error('cccd')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text text-danger">Bắt buộc nhập 12 số</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        @if(!str_contains($error, 'name') && !str_contains($error, 'cccd'))
                                            <li>{{ $error }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Nút submit --}}
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Quay lại
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Xác nhận đặt phòng
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
