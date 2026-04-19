@extends('layouts.admin')

@section('title', 'Chi tiết đặt phòng #' . $booking->id)

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="bi bi-file-text me-2"></i>
                        Chi tiết đặt phòng #{{ $booking->id }}
                    </h4>
                    <div>
                        @if($booking->actual_check_out)
                            <span class="badge bg-success">Đã check-out</span>
                        @elseif($booking->actual_check_in)
                            <span class="badge bg-info">Đã check-in</span>
                        @elseif($booking->status === 'pending')
                            <span class="badge bg-warning text-dark">Chờ thanh toán</span>
                        @else
                            <span class="badge bg-primary">Chờ check-in</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Thông tin chung -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                Thông tin chung
                            </h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Mã đặt phòng:</strong></td>
                                    <td>#{{ $booking->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Khách hàng:</strong></td>
                                    <td>{{ $booking->user->full_name ?? $booking->user->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $booking->user->email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Điện thoại:</strong></td>
                                    <td>{{ $booking->user->phone }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phòng:</strong></td>
                                    <td>
                                        @if($booking->bookingRooms->count() > 0)
                                            {{ $booking->bookingRooms->map(function($br) { return $br->room->name ?? 'N/A'; })->implode(', ') }}
                                        @else
                                            {{ $booking->room->name ?? 'N/A' }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày nhận phòng (dự kiến):</strong></td>
                                    <td>{{ $booking->formatted_check_in }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày trả phòng (dự kiến):</strong></td>
                                    <td>{{ $booking->formatted_check_out }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Thời gian check-in thực tế:</strong></td>
                                    <td>{{ $booking->formatted_actual_check_in ?? 'Chưa check-in' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Thời gian check-out thực tế:</strong></td>
                                    <td>{{ $booking->formatted_actual_check_out ?? 'Chưa check-out' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Số đêm:</strong></td>
                                    <td>{{ $booking->nights }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tổng tiền:</strong></td>
                                    <td class="text-primary fw-bold">{{ number_format($booking->total_price, 0, ',', '.') }} VNĐ</td>
                                </tr>
                                <tr>
                                    <td><strong>Phương thức thanh toán:</strong></td>
                                    <td>{{ $booking->payment_method === 'cash' ? 'Tiền mặt' : 'VNPay' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5 class="mb-3">
                                <i class="bi bi-people-fill me-2"></i>
                                Thông tin khách hàng
                            </h5>

                            @php
                                $booking->refresh();
                                $groupedGuests = $booking->guests()->get()->groupBy(function($guest) {
                                    return $guest->room_type ?: 'room_' . ($guest->room_index + 1);
                                });
                            @endphp

                            @if($groupedGuests->count() > 0)
                                @foreach($groupedGuests as $roomKey => $guests)
                                    <div class="card mb-3 border-light shadow-sm">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">
                                                <i class="bi bi-door-closed me-2"></i>
                                                @if(str_starts_with($roomKey, 'room_'))
                                                    Phòng {{ intval(substr($roomKey, 5)) }}
                                                @else
                                                    Phòng {{ ucwords(str_replace(['_', '-'], ' ', $roomKey)) }}
                                                @endif
                                            </h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-sm mb-0">
                                                    <thead class="table-light small">
                                                        <tr>
                                                            <th class="ps-3">Tên khách hàng</th>
                                                            <th>CCCD</th>
                                                            <th>Trạng thái</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($guests as $guest)
                                                            <tr>
                                                                <td class="ps-3">{{ $guest->name }}</td>
                                                                <td>{{ $guest->cccd }}</td>
                                                                <td>
                                                                    @if($booking->actual_check_out)
                                                                        <span class="badge bg-secondary">Đã check-out</span>
                                                                    @else
                                                                        <span class="badge bg-{{ $guest->checkin_status === 'checked_in' ? 'success' : 'warning' }}">
                                                                            {{ $guest->checkin_status === 'checked_in' ? 'Đã check-in' : 'Chờ check-in' }}
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Chưa có thông tin khách hàng
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    @if($booking->status !== 'checked_out' && $booking->status !== 'cancelled' && $booking->status !== 'completed')
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Thao tác lưu trú
                                </h5>

                                <form method="POST" action="{{ route('admin.bookings.checkin', $booking->id) }}" class="mb-4">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Chọn khách hàng:</label>
                                            <select name="guest_id" class="form-select" required>
                                                <option value="">-- Chọn khách --</option>
                                                @foreach(($booking->guests ?? collect()) as $guest)
                                                    <option value="{{ $guest->id }}">{{ $guest->name }} - Phòng {{ $guest->room_index + 1 }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Nhập CCCD để xác nhận:</label>
                                            <input type="text" name="cccd_input" class="form-control"
                                                   placeholder="Nhập 12 số CCCD" maxlength="12" required>
                                            <div class="form-text small text-muted">Nhập chính xác CCCD của khách hàng</div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Check-in
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if($booking->status === 'checked_in')
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Check-out khách hàng
                                </h5>

                                <form method="POST" action="{{ route('admin.bookings.checkout', $booking->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-warning"
                                            onclick="return confirm('Bạn có chắc chắn muốn check-out khách hàng này?')">
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Check-out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="text-center mt-4">
                        <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Quay lại danh sách
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
