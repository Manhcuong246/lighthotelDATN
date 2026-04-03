@extends('layouts.admin')

@section('title', 'Sửa đơn đặt phòng (Compact)')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <!-- Header -->
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary rounded-2">
                <span class="me-1">←</span> Quay lại
            </a>
            <h1 class="h4 fw-bold mb-0">✏️ Sửa đơn #{{ $booking->id }}</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-3" role="alert">
            <h6 class="alert-heading fw-bold mb-2">❌ Có lỗi xảy ra!</h6>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <!-- Thông tin khách hàng & phòng - Nằm ngang -->
        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                <h5 class="mb-0 fw-bold">👤 Khách hàng & 🏨 Phòng</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-sm-3">
                        <label class="form-label small fw-bold text-muted mb-1">Khách hàng</label>
                           <input type="text" class="form-control form-control-sm rounded-2" disabled
                               value="{{ $booking->user?->full_name ?? '' }}" />
                           <small class="text-muted">{{ $booking->user?->email ?? '' }}</small>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">Phòng</label>
                        <input type="text" class="form-control form-control-sm rounded-2" disabled
                               value="{{ $booking->room?->name }}" />
                    </div>
                    <div class="col-sm-1">
                        <label for="guests" class="form-label small fw-bold text-muted mb-1">Khách</label>
                        <input type="number" class="form-control form-control-sm rounded-2 @error('guests') is-invalid @enderror"
                               id="guests" name="guests" min="1" value="{{ old('guests', $booking->guests) }}" required />
                        @error('guests')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label for="check_in" class="form-label small fw-bold text-muted mb-1">Ngày nhận phòng</label>
                        <input type="date" class="form-control form-control-sm rounded-2 @error('check_in') is-invalid @enderror"
                               id="check_in" name="check_in" value="{{ old('check_in', $booking->check_in?->format('Y-m-d')) }}" required />
                        @error('check_in')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label for="check_out" class="form-label small fw-bold text-muted mb-1">Ngày trả phòng</label>
                        <input type="date" class="form-control form-control-sm rounded-2 @error('check_out') is-invalid @enderror"
                               id="check_out" name="check_out" value="{{ old('check_out', $booking->check_out?->format('Y-m-d')) }}" required />
                        @error('check_out')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">Phòng type</label>
                        <input type="text" class="form-control form-control-sm rounded-2" disabled
                               value="{{ $booking->room?->type }}" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Giá & Trạng thái - Nằm ngang -->
        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                <h5 class="mb-0 fw-bold">💰 Giá & 📊 Trạng thái</h5>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-sm-3">
                        <label for="total_price" class="form-label small fw-bold text-muted mb-1">Tổng tiền (VNĐ)</label>
                        <input type="number" class="form-control form-control-sm rounded-2 @error('total_price') is-invalid @enderror"
                               id="total_price" name="total_price" min="0" step="1000"
                               value="{{ old('total_price', $booking->total_price) }}" required />
                        @error('total_price')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-3">
                        <label for="status" class="form-label small fw-bold text-muted mb-1">Trạng thái</label>
                        <select class="form-select form-select-sm rounded-2 @error('status') is-invalid @enderror"
                                id="status" name="status" required>
                            <option value="pending" {{ old('status', $booking->status)=='pending'?'selected':'' }}>⏳ Chờ xác nhận</option>
                            <option value="confirmed" {{ old('status', $booking->status)=='confirmed'?'selected':'' }}>✓ Đã xác nhận</option>
                            <option value="completed" {{ old('status', $booking->status)=='completed'?'selected':'' }}>✓✓ Hoàn thành</option>
                            <option value="cancelled" {{ old('status', $booking->status)=='cancelled'?'selected':'' }}>✕ Đã hủy</option>
                        </select>
                        @error('status')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">ID Đơn</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text rounded-2">#{{ $booking->id }}</span>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">Tạo lúc</label>
                        <small class="d-block text-muted">{{ $booking->created_at?->format('d/m H:i') ?? '—' }}</small>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">Cập nhật</label>
                        <small class="d-block text-muted">{{ $booking->updated_at?->format('d/m H:i') ?? '—' }}</small>
                    </div>
                </div>

                @if($booking->actual_check_in || $booking->actual_check_out)
                <div class="mt-2 pt-2 border-top">
                    <div class="row g-2">
                        @if($booking->actual_check_in)
                        <div class="col-sm-6">
                            <small class="text-muted d-block">✓ Ngày nhận phòng thực tế</small>
                            <code class="text-dark">{{ $booking->actual_check_in->format('d/m/Y H:i') }}</code>
                        </div>
                        @endif
                        @if($booking->actual_check_out)
                        <div class="col-sm-6">
                            <small class="text-muted d-block">✓ Ngày trả phòng thực tế</small>
                            <code class="text-dark">{{ $booking->actual_check_out->format('d/m/Y H:i') }}</code>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Hướng dẫn - Nằm ngang -->
        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                <h5 class="mb-0 fw-bold">ℹ️ Hướng dẫn</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <h6 class="fw-bold text-danger mb-2">🔒 Bị khóa (không thay đổi):</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-1"><span class="badge bg-danger">👤</span> Khách hàng</li>
                            <li class="mb-1"><span class="badge bg-danger">🏨</span> Phòng</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6 class="fw-bold text-success mb-2">✏️ Được sửa:</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-1"><span class="badge bg-success">📅</span> Ngày nhận/trả phòng</li>
                            <li class="mb-1"><span class="badge bg-success">👥</span> Số khách</li>
                            <li class="mb-1"><span class="badge bg-success">💰</span> Tổng tiền</li>
                            <li class="mb-1"><span class="badge bg-success">📊</span> Trạng thái</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6 class="fw-bold text-info mb-2">📋 Ghi chú:</h6>
                        <p class="small text-muted mb-0">
                            Xóa & tạo mới để thay đổi khách hàng hoặc phòng. Các thông tin khác có thể sửa trực tiếp ở trên.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary rounded-2 fw-bold px-4">
                💾 Lưu thay đổi
            </button>
            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline-secondary rounded-2 fw-bold px-4">
                ❌ Hủy
            </a>
        </div>
    </form>
</div>

<style>
    .bg-gradient {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .rounded-3 {
        border-radius: 12px !important;
    }

    .rounded-2 {
        border-radius: 8px !important;
    }

    .form-control, .form-select, .form-control-sm, .form-select-sm {
        border: 1px solid #dee2e6;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus, .form-select:focus, .form-control-sm:focus, .form-select-sm:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    .card {
        transition: box-shadow 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    }

    /* Bootstrap validation feedback */
    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    .needs-validation.was-validated .form-control:valid,
    .needs-validation.was-validated .form-select:valid {
        border-color: #28a745;
    }
</style>

<script>
    // Bootstrap validation
    (function () {
        'use strict';
        window.addEventListener('load', function () {
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>
@endsection
