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

                        <!-- Thông tin các phòng đã chọn -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-door-open me-2"></i>
                                Thông tin phòng
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Phòng</th>
                                            <th>Loại phòng</th>
                                            <th>Giá/đêm</th>
                                            <th>Số đêm</th>
                                            <th>Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rooms as $index => $room)
                                            <input type="hidden" name="rooms[{{ $index }}][room_id]" value="{{ $room->id }}">
                                            <tr>
                                                <td>{{ $room->name }}</td>
                                                <td>{{ $room->roomType->name ?? 'N/A' }}</td>
                                                <td>{{ number_format(1000000, 0, ',', '.') }} VNĐ</td>
                                                <td>{{ \Carbon\Carbon::parse($check_in)->diffInDays(\Carbon\Carbon::parse($check_out)) }}</td>
                                                <td>{{ number_format(1000000 * \Carbon\Carbon::parse($check_in)->diffInDays(\Carbon\Carbon::parse($check_out)), 0, ',', '.') }} VNĐ</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Thông tin khách hàng cho từng phòng -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="bi bi-people-fill me-2"></i>
                                Thông tin khách hàng
                            </h5>
                            <div id="guestFormsContainer">
                                @foreach($rooms as $index => $room)
                                    <div class="guest-room-section mb-4 p-3 border rounded" data-room-index="{{ $index }}">
                                        <h6 class="mb-3">
                                            <i class="bi bi-house-door me-2"></i>
                                            Phòng {{ $index + 1 }}: {{ $room->name }}
                                        </h6>

                                        <!-- Số lượng người -->
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label small">Người lớn</label>
                                                <input type="number" name="adults[{{ $index }}]" class="form-control form-control-sm"
                                                       value="{{ old("adults.{$index}", 1) }}" min="1" max="10">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Trẻ em (0–5 tuổi, tối đa 2)</label>
                                                <select name="children_0_5[{{ $index }}]" class="form-select form-select-sm">
                                                    @foreach ([0, 1, 2] as $n)
                                                        <option value="{{ $n }}" @selected((int) old("children_0_5.{$index}", 0) === $n)>{{ $n }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Trẻ em (6-11 tuổi)</label>
                                                <input type="number" name="children_6_11[{{ $index }}]" class="form-control form-control-sm"
                                                       value="{{ old("children_6_11.{$index}", 0) }}" min="0" max="5">
                                            </div>
                                        </div>

                                        <div class="guest-inputs-container" id="guestInputsContainer-{{ $index }}">
                                            <!-- Guest inputs sẽ được tạo bởi JavaScript -->
                                            <div class="text-muted text-center py-2 bg-light rounded">
                                                <small>Vui lòng chọn số lượng người cho phòng này</small>
                                            </div>
                                        </div>

                                        <!-- Controls để thêm/xóa guest -->
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addGuest({{ $index }})">
                                                <i class="bi bi-plus-circle me-1"></i>
                                                Thêm khách
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeGuest({{ $index }})">
                                                <i class="bi bi-dash-circle me-1"></i>
                                                Xóa khách
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
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

<!-- JavaScript cho dynamic guest forms -->
<script src="{{ asset('js/new-booking-form.js') }}" defer></script>
@endsection
