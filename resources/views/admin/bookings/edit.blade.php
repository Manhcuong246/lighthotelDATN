@extends('layouts.admin')

@section('title', 'Sửa đơn #' . $booking->id)

@php
    $statusMap = [
        'pending'   => ['label' => 'Chờ thanh toán', 'color' => 'warning'],
        'confirmed' => ['label' => 'Đã thanh toán',  'color' => 'success'],
        'completed' => ['label' => 'Hoàn thành',     'color' => 'success'],
        'cancelled' => ['label' => 'Đã hủy',         'color' => 'danger'],
    ];
    $st = $statusMap[$booking->status] ?? ['label' => $booking->status, 'color' => 'secondary'];
    $nights = $booking->check_in && $booking->check_out ? $booking->check_in->diffInDays($booking->check_out) : 0;
@endphp

@section('content')
<div class="container-fluid" style="max-width: 900px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
            </a>
            <div>
                <h1 class="h4 fw-bold mb-0">Sửa đơn #{{ $booking->id }}</h1>
                <small class="text-muted">Tạo {{ $booking->created_at?->format('d/m/Y H:i') }}</small>
            </div>
        </div>
        <span class="badge bg-{{ $st['color'] }}">{{ $st['label'] }}</span>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Khách hàng --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:42px;height:42px">
                            <i class="bi bi-person-fill text-primary"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fw-semibold">{{ $booking->user?->full_name ?? '—' }}</div>
                        <small class="text-muted">{{ $booking->user?->email ?? '' }} {{ $booking->user?->phone ? '· ' . $booking->user->phone : '' }}</small>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-light text-muted border">Không thể sửa</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Phòng --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-header bg-white border-bottom-0 pb-0 pt-3 px-4">
                <h6 class="fw-bold text-muted mb-0"><i class="bi bi-door-open me-2"></i>Phòng đã đặt</h6>
            </div>
            <div class="card-body pt-2">
                @foreach($booking->bookingRooms as $br)
                <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-primary">{{ $br->room?->name ?? 'Phòng #' . $br->room_id }}</span>
                        <small class="text-muted">{{ $br->room?->roomType?->name ?? '' }}</small>
                    </div>
                    <div class="text-end small">
                        <span class="text-muted">{{ $br->adults }} NL</span>
                        @if($br->children_0_5 > 0)<span class="text-muted ms-1">· {{ $br->children_0_5 }} trẻ 0–5 tuổi</span>@endif
                        @if($br->children_6_11 > 0)<span class="text-muted ms-1">· {{ $br->children_6_11 }} trẻ 6–11 tuổi</span>@endif
                        <span class="ms-2 fw-semibold">{{ number_format($br->subtotal, 0, ',', '.') }} ₫</span>
                    </div>
                </div>
                @endforeach
                <div class="text-muted small mt-2">
                    <i class="bi bi-lock me-1"></i>Phòng không thể sửa. Hủy đơn và tạo mới nếu cần đổi phòng.
                </div>
            </div>
        </div>

        {{-- Ngày & Giá & Trạng thái --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-header bg-white border-bottom-0 pb-0 pt-3 px-4">
                <h6 class="fw-bold text-muted mb-0"><i class="bi bi-pencil me-2"></i>Thông tin có thể sửa</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="check_in" class="form-label">Nhận phòng</label>
                        <input type="date" class="form-control @error('check_in') is-invalid @enderror"
                               id="check_in" name="check_in"
                               value="{{ old('check_in', $booking->check_in?->format('Y-m-d')) }}" required>
                        @error('check_in')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="check_out" class="form-label">Trả phòng</label>
                        <input type="date" class="form-control @error('check_out') is-invalid @enderror"
                               id="check_out" name="check_out"
                               value="{{ old('check_out', $booking->check_out?->format('Y-m-d')) }}" required>
                        @error('check_out')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="total_price" class="form-label">Tổng tiền (tự tính)</label>
                        <input type="text" class="form-control bg-light"
                               id="total_price"
                               value="{{ number_format((float) old('total_price', $booking->total_price), 0, ',', '.') }} ₫" readonly>
                        <small class="text-muted">Giá được hệ thống tính tự động.</small>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="pending" @selected(old('status', $booking->status) === 'pending')>Chờ thanh toán</option>
                            <option value="confirmed" @selected(old('status', $booking->status) === 'confirmed')>Đã thanh toán</option>
                            <option value="completed" @selected(old('status', $booking->status) === 'completed')>Hoàn thành</option>
                            <option value="cancelled" @selected(old('status', $booking->status) === 'cancelled')>Đã hủy</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if($booking->actual_check_in || $booking->actual_check_out)
                <div class="row g-3 mt-1 pt-3 border-top">
                    @if($booking->actual_check_in)
                    <div class="col-md-6">
                        <small class="text-muted">Check-in thực tế</small>
                        <div class="fw-semibold small">{{ $booking->actual_check_in->format('d/m/Y H:i') }}</div>
                    </div>
                    @endif
                    @if($booking->actual_check_out)
                    <div class="col-md-6">
                        <small class="text-muted">Check-out thực tế</small>
                        <div class="fw-semibold small">{{ $booking->actual_check_out->format('d/m/Y H:i') }}</div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Nút --}}
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i>Lưu thay đổi
            </button>
            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline-secondary px-4">Hủy bỏ</a>
        </div>
    </form>
</div>
@endsection
