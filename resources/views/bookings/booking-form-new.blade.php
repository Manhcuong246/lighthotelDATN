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
                    <form id="bookingForm" method="POST" action="{{ route('bookings.store') }}">
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

                        <!-- Chọn số phòng -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Số lượng phòng *</label>
                                <select id="roomCount" class="form-select" onchange="renderRooms()" required>
                                    <option value="">Chọn số phòng</option>
                                    <option value="1">1 phòng</option>
                                    <option value="2">2 phòng</option>
                                    <option value="3">3 phòng</option>
                                    <option value="4">4 phòng</option>
                                    <option value="5">5 phòng</option>
                                </select>
                            </div>
                        </div>

                        <!-- Container cho các phòng -->
                        <div id="roomsContainer" class="mb-4">
                            <!-- Rooms sẽ được render bằng JavaScript -->
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

<!-- JavaScript cho dynamic room forms -->
<script src="{{ asset('js/booking-form-final.js') }}" defer></script>
@endsection
