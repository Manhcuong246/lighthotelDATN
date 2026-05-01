@extends('layouts.app')

@section('title', 'Xác nhận đặt phòng')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-check-circle me-2"></i>
                        Đặt phòng thành công!
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Đặt phòng của bạn đã được ghi nhận!</strong>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Mã đặt phòng:</strong> #{{ $booking->id }}
                        </div>
                        <div class="col-md-6">
                            <strong>Trạng thái:</strong> 
                            <span class="badge bg-warning text-dark">
                                {{ $booking->status === 'pending' ? 'Chờ check-in' : $booking->status }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Ngày nhận phòng:</strong> {{ $booking->formatted_check_in }}
                        </div>
                        <div class="col-md-6">
                            <strong>Ngày trả phòng:</strong> {{ $booking->formatted_check_out }}
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Tổng tiền:</strong> 
                            <span class="text-primary">{{ number_format($booking->total_price, 0, ',', '.') }} VNĐ</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Phương thức thanh toán:</strong> 
                            {{ $booking->payment_method === 'cash' ? 'Tiền mặt' : 'VNPay' }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-3">
                            <i class="bi bi-people-fill me-2"></i>
                            Thông tin khách hàng
                        </h5>
                        
                        {{-- $booking->guests là cột số khách; danh sách khách lấy qua quan hệ guests() --}}
                        @php $confirmationGuests = $booking->guests()->get(); @endphp
                        @if($confirmationGuests->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Phòng</th>
                                            <th>Tên khách hàng</th>
                                            <th>CCCD</th>
                                            <th>Trạng thái check-in</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($confirmationGuests as $guest)
                                            <tr>
                                                <td>{{ $guest->room_display_name }}</td>
                                                <td>{{ $guest->name }}</td>
                                                <td>{{ $guest->masked_cccd }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $guest->checkin_status === 'checked_in' ? 'success' : 'warning' }}">
                                                        {{ $guest->checkin_status === 'checked_in' ? 'Đã check-in' : 'Chờ check-in' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Chưa có thông tin khách hàng
                            </div>
                        @endif
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Lưu ý:</strong> Vui lòng đến đúng giờ để check-in. Nhân viên lễ tân sẽ đối chiếu CCCD với thông tin bạn đã cung cấp.
                    </div>

                    <div class="text-center mt-4">
                        <a href="{{ route('bookings.index') }}" class="btn btn-primary">
                            <i class="bi bi-house me-2"></i>
                            Về trang chủ
                        </a>
                        <a href="{{ route('bookings.confirmation', $booking->id) }}" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-printer me-2"></i>
                            In xác nhận
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
