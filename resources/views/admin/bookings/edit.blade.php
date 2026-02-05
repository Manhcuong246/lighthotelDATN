@extends('layouts.admin')

@section('title', 'Sửa đơn đặt phòng')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <!-- Header -->
    <div class="mb-4 d-flex align-items-center gap-3">
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary rounded-2">
            <span class="me-1">←</span> Quay lại
        </a>
        <h1 class="h3 fw-bold mb-0">✏️ Sửa đơn #{{ $booking->id }}</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3" role="alert">
            <h6 class="alert-heading fw-bold mb-2">❌ Có lỗi xảy ra!</h6>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row gap-3 g-lg-4">
        <div class="col-lg-8">
            <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')

                <!-- Khách hàng Section -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                        <h5 class="mb-0 fw-bold">👤 Thông tin khách hàng</h5>
                    </div>
                    <div class="card-body">
                        <div>
                            <label class="form-label text-uppercase small fw-bold text-muted">Khách hàng</label>
                            <input type="text" class="form-control rounded-2" disabled
                                   value="{{ $booking->user?->full_name ?? '(Không xác định)' }} — {{ $booking->user?->email ?? '—' }}" />
                            <small class="form-text text-muted mt-2 d-block">
                                ℹ️ Không thể thay đổi khách hàng. <a href="{{ route('admin.bookings.index') }}" class="text-primary">Xóa & tạo mới nếu cần.</a>
                            </small>
                    </div>
                </div>

                <!-- Phòng & Ngày Section -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                        <h5 class="mb-0 fw-bold">🏨 Thông tin phòng & ngày</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-uppercase small fw-bold text-muted">Phòng</label>
                                <input type="text" class="form-control rounded-2" disabled
                                       value="{{ $booking->room?->name }} ({{ $booking->room?->type }})" />
                                <small class="form-text text-muted mt-2 d-block">Không thể thay đổi phòng</small>
                            </div>
                            <div class="col-md-6">
                                <label for="guests" class="form-label text-uppercase small fw-bold text-muted">Số khách</label>
                                <input type="number" class="form-control rounded-2 @error('guests') is-invalid @enderror"
                                       id="guests" name="guests" min="1" value="{{ old('guests', $booking->guests) }}" required />
                                @error('guests')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-uppercase small fw-bold text-muted">📅 Check-in (dự kiến)</label>
                                <input type="date" class="form-control rounded-2" disabled
                                       value="{{ $booking->check_in?->format('Y-m-d') }}" />
                                <small class="form-text text-muted mt-2 d-block">Không thể thay đổi ngày check-in</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-uppercase small fw-bold text-muted">📅 Check-out (dự kiến)</label>
                                <input type="date" class="form-control rounded-2" disabled
                                       value="{{ $booking->check_out?->format('Y-m-d') }}" />
                                <small class="form-text text-muted mt-2 d-block">Không thể thay đổi ngày check-out</small>
                        </div>
                    </div>
                </div>

                <!-- Giá Section -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                        <h5 class="mb-0 fw-bold">💰 Giá cả</h5>
                    </div>
                    <div class="card-body">
                        <div>
                            <label for="total_price" class="form-label text-uppercase small fw-bold text-muted">Tổng tiền (VNĐ)</label>
                            <input type="number" class="form-control rounded-2 @error('total_price') is-invalid @enderror"
                                   id="total_price" name="total_price" min="0" step="1000"
                                   value="{{ old('total_price', $booking->total_price) }}" required />
                            @error('total_price')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>

                <!-- Trạng thái Section -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                        <h5 class="mb-0 fw-bold">📊 Trạng thái</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label text-uppercase small fw-bold text-muted">Trạng thái đơn</label>
                            <select class="form-select rounded-2 @error('status') is-invalid @enderror"
                                    id="status" name="status" required>
                                <option value="pending" {{ old('status', $booking->status)=='pending'?'selected':'' }}>⏳ Chờ xác nhận</option>
                                <option value="confirmed" {{ old('status', $booking->status)=='confirmed'?'selected':'' }}>✓ Đã xác nhận</option>
                                <option value="completed" {{ old('status', $booking->status)=='completed'?'selected':'' }}>✓✓ Hoàn thành</option>
                                <option value="cancelled" {{ old('status', $booking->status)=='cancelled'?'selected':'' }}>✕ Đã hủy</option>
                            </select>
                            @error('status')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>

                        @if($booking->actual_check_in || $booking->actual_check_out)
                        <div class="mt-3 p-3 bg-light rounded-2">
                            <label class="form-label text-uppercase small fw-bold text-muted d-block mb-2">⏱️ Thực tế</label>
                            <div class="row g-3">
                                @if($booking->actual_check_in)
                                <div class="col-md-6">
                                    <small class="text-muted d-block mb-1">✓ Check-in lúc</small>
                                    <code class="text-dark fs-6">{{ $booking->actual_check_in->format('d/m H:i') }}</code>
                                </div>
                                @endif
                                @if($booking->actual_check_out)
                                <div class="col-md-6">
                                    <small class="text-muted d-block mb-1">✓ Check-out lúc</small>
                                    <code class="text-dark fs-6">{{ $booking->actual_check_out->format('d/m H:i') }}</code>
                                </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Ghi chú Section (nếu cần) -->
                <!-- DISABLED: notes field not yet in database schema -->

                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-2 fw-bold px-4">
                        💾 Lưu thay đổi
                    </button>
                    <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline-secondary rounded-2 fw-bold px-4">
                        ❌ Hủy
                    </a>
                </div>
            </form>
        </div>

        <!-- Right: Info Panel -->
        <div class="col-lg-4">
            <!-- Edit Info Card -->
            <div class="card shadow-sm border-0 rounded-3 mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                    <h5 class="mb-0 fw-bold">ℹ️ Hướng dẫn</h5>
                </div>
                <div class="card-body small">
                    <h6 class="fw-bold text-uppercase text-muted mb-2">Trường bị khóa (không thể sửa):</h6>
                    <ul class="ps-3 mb-3">
                        <li class="mb-1">👤 Khách hàng</li>
                        <li class="mb-1">🏨 Phòng</li>
                        <li class="mb-1">📅 Ngày check-in/check-out</li>
                    </ul>
                    <p class="text-muted mb-0">
                        📝 Những trường này được xác định khi tạo đơn. Để thay đổi, vui lòng xóa và tạo đơn mới.
                    </p>

                    <hr class="my-3">

                    <h6 class="fw-bold text-uppercase text-muted mb-2">Có thể sửa được:</h6>
                    <ul class="ps-3 mb-0">
                        <li class="mb-1 text-success">👥 Số khách</li>
                        <li class="mb-1 text-success">💰 Tổng tiền</li>
                        <li class="text-success">📊 Trạng thái</li>
                    </ul>
                </div>
            </div>

            <!-- Booking Details Card -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-gradient text-dark border-0 rounded-top-3">
                    <h5 class="mb-0 fw-bold">📋 Chi tiết đơn</h5>
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <label class="text-uppercase small fw-bold text-muted d-block mb-1">ID Đơn</label>
                        <p class="mb-0 fs-6 fw-bold">#{{ $booking->id }}</p>
                    </div>
                    <div class="mb-2">
                        <label class="text-uppercase small fw-bold text-muted d-block mb-1">Tạo lúc</label>
                        <p class="mb-0">{{ $booking->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-uppercase small fw-bold text-muted d-block mb-1">Cập nhật lúc</label>
                        <p class="mb-0">{{ $booking->updated_at?->format('d/m/Y H:i') ?? '—' }}</p>
                    </div>

                    <hr class="my-3">

                    <div>
                        <label class="text-uppercase small fw-bold text-muted d-block mb-1">Trạng thái hiện tại</label>
                        @php
                            $statusLabels = [
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'completed' => 'Hoàn thành',
                                'cancelled' => 'Đã hủy',
                            ];
                        @endphp
                        <span class="badge bg-secondary px-3 py-2">{{ $statusLabels[$booking->status] ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

    .form-control, .form-select {
        border: 1px solid #dee2e6;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .card {
        transition: box-shadow 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    }

    .sticky-top {
        position: sticky;
        z-index: 100;
    }

    /* Bootstrap validation feedback */
    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875rem;
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
