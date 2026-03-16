@extends('layouts.app')

@section('title', $roomType->name)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm mb-3">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
                <h1 class="h2 fw-bold">{{ $roomType->name }}</h1>
                @if($roomType->image)
                    <img src="{{ asset('storage/' . $roomType->image) }}" alt="{{ $roomType->name }}" class="img-fluid rounded shadow-sm my-3" style="max-height: 400px; width: 100%; object-fit: cover;">
                @endif
            </div>

            <!-- Info Cards -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-people fs-3 text-primary me-3"></i>
                                <div>
                                    <small class="text-muted">Sức chứa</small>
                                    <p class="mb-0 fw-semibold">{{ $roomType->capacity }} người</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-door-open fs-3 text-primary me-3"></i>
                                <div>
                                    <small class="text-muted">Giường</small>
                                    <p class="mb-0 fw-semibold">{{ $roomType->beds ?? 1 }} giường</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-droplet fs-3 text-primary me-3"></i>
                                <div>
                                    <small class="text-muted">Phòng tắm</small>
                                    <p class="mb-0 fw-semibold">{{ $roomType->baths ?? 1 }} phòng</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-currency-dollar fs-3 text-success me-3"></i>
                                <div>
                                    <small class="text-muted">Giá phòng</small>
                                    <p class="mb-0 fw-bold text-danger">{{ number_format($roomType->price, 0, ',', '.') }} VNĐ/đêm</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($roomType->description)
                        <hr class="my-4">
                        <div class="mt-3">
                            <h5 class="fw-semibold">Mô tả chi tiết</h5>
                            <p class="text-muted">{{ $roomType->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Booking Form -->
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Đặt phòng ngay</h5>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('bookings.store_by_type', $roomType) }}" method="POST">
                        @csrf
                        
                        <div class="row g-3">
                            <!-- Check-in/Check-out -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ngày nhận phòng</label>
                                <input type="date" name="check_in" class="form-control" 
                                       value="{{ old('check_in') }}" min="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ngày trả phòng</label>
                                <input type="date" name="check_out" class="form-control" 
                                       value="{{ old('check_out') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                            </div>

                            <!-- Guest info -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Họ và tên</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="{{ old('full_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="{{ old('email') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Số điện thoại</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="{{ old('phone') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Số khách</label>
                                <input type="number" name="guests" class="form-control" 
                                       value="{{ old('guests', 1) }}" min="1" max="{{ $roomType->capacity }}" required>
                            </div>

                            <!-- Preferred room number -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Yêu cầu số phòng (tùy chọn)
                                    <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" 
                                       title="Chúng tôi sẽ cố gắng xếp phòng theo yêu cầu của bạn nếu còn trống"></i>
                                </label>
                                <input type="text" name="preferred_room_number" class="form-control" 
                                       value="{{ old('preferred_room_number') }}" placeholder="Ví dụ: 101, 205...">
                                <small class="text-muted">Để trống nếu bạn không có yêu cầu cụ thể</small>
                            </div>

                            <!-- Submit -->
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-check-circle"></i> Đặt phòng ngay
                                </button>
                                <small class="text-muted d-block mt-2 text-center">
                                    * Bạn sẽ được xếp phòng tự động dựa trên loại phòng và khoảng thời gian đã chọn
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Available Rooms Info -->
            <div class="alert alert-info mt-4 mb-0">
                <i class="bi bi-info-circle"></i> 
                <strong>Thông tin về loại phòng này:</strong>
                <p class="mb-0 mt-2">
                    Chúng tôi có <strong>{{ $availableRoomsCount ?? 0 }} phòng</strong> thuộc loại {{ $roomType->name }}. 
                    Khi đặt phòng, hệ thống sẽ tự động xếp phòng trống phù hợp cho bạn.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})
</script>
@endsection
