@extends('layouts.app')

@section('title', 'Thông tin đặt phòng')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        Điền thông tin đặt phòng
                    </h4>
                </div>
                <div class="card-body">
                    <form id="bookingForm" method="POST" action="{{ route('bookings.internal.store') }}">
                        @csrf
                        <input type="hidden" name="check_in" value="{{ $check_in }}">
                        <input type="hidden" name="check_out" value="{{ $check_out }}">

                        <!-- Thông tin khách hàng chính -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Họ tên *</label>
                                <input type="text" name="full_name" class="form-control"
                                       value="{{ old('full_name') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control"
                                       value="{{ old('email') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Điện thoại *</label>
                                <input type="text" name="phone" class="form-control"
                                       value="{{ old('phone') }}" required>
                            </div>
                        </div>

                        <!-- Chọn số phòng - chỉ để tính giá -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Số lượng phòng *</label>
                                <select name="rooms" id="rooms" class="form-select" required>
                                    @for($i = 1; $i <= 10; $i++)
                                        <option value="{{ $i }}" {{ $i == 1 ? 'selected' : '' }}>
                                            {{ $i }} phòng
                                        </option>
                                    @endfor
                                </select>
                                <div class="form-text">Chọn số lượng phòng để tính giá</div>
                            </div>
                        </div>

                        <!-- Thông tin người đại diện - chỉ 1 form duy nhất -->
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-person-fill me-2"></i>Thông tin người đại diện</h5>
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
                                            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   name="name"
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   value="{{ old('name') }}"
                                                   placeholder="Nhập họ tên người đại diện"
                                                   required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">CCCD/CMND <span class="text-danger">*</span></label>
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
                                            <div class="form-text text-muted">CCCD phải gồm 12 số</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Phương thức thanh toán -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Phương thức thanh toán *</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Chọn phương thức</option>
                                    <option value="cash">Tiền mặt</option>
                                    <option value="vnpay">VNPay</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-check-circle me-2"></i>
                                    ĐẶT PHÒNG NGAY
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
