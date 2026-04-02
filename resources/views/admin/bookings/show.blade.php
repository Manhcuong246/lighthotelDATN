@extends('layouts.admin')

@section('title', 'Chi tiết đặt phòng')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <!-- Header -->
    <div class="mb-4">
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 mb-3">
            ← Quay lại danh sách
        </a>
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h2 fw-bold mb-0">📋 Đơn #{{ $booking->id }}</h1>
            @php
                $statusColors = [
                    'pending' => 'warning',
                    'confirmed' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                ];
                $statusLabels = [
                    'pending' => 'Chờ xác nhận',
                    'confirmed' => 'Đã xác nhận',
                    'completed' => 'Hoàn thành',
                    'cancelled' => 'Đã hủy',
                ];
            @endphp
            <span class="badge bg-{{ $statusColors[$booking->status] ?? 'secondary' }} px-4 py-2 fs-6">
                {{ $statusLabels[$booking->status] ?? '—' }}
            </span>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <strong>✅ Thành công!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <strong>❌ Lỗi!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        <!-- Main Content - Compact Layout -->
        <div class="col-12">
            <!-- Comprehensive Info Card -->
            <div class="card border-0 rounded-3 shadow-sm">
                <div class="card-body p-4">
                    <!-- Top Row: Customer and Room Info -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <p class="text-uppercase small fw-bold text-muted mb-1">👤 Khách hàng</p>
<<<<<<< HEAD
                            <p class="mb-0 fw-bold">{{ $booking->user?->full_name ?? '—' }}</p>
                            <small class="text-muted">{{ $booking->user?->email ?? '—' }}</small>
                        </div>
                        <div class="col-md-2">
                            <p class="text-uppercase small fw-bold text-muted mb-1">🏨 Phòng</p>
                            <span class="badge bg-primary px-2 py-1">{{ $booking->room?->name }}</span>
=======
                            <p class="mb-0 fw-bold text-primary">{{ $booking->user?->full_name ?? '—' }}</p>
                            <small class="text-muted d-block">{{ $booking->user?->email ?? '—' }}</small>
                            <small class="text-muted">{{ $booking->user?->phone ?? '—' }}</small>
>>>>>>> vinam
                        </div>
                        <div class="col-md-2">
                            <p class="text-uppercase small fw-bold text-muted mb-1">📅 Check-in</p>
                            <p class="mb-0 fw-bold">{{ $booking->check_in?->format('d/m/Y') ?? '—' }}</p>
                        </div>
                        <div class="col-md-2">
                            <p class="text-uppercase small fw-bold text-muted mb-1">📅 Check-out</p>
                            <p class="mb-0 fw-bold">{{ $booking->check_out?->format('d/m/Y') ?? '—' }}</p>
                        </div>
<<<<<<< HEAD
                        <div class="col-md-1">
                            <p class="text-uppercase small fw-bold text-muted mb-1">👥</p>
                            <span class="badge bg-secondary px-2 py-1">{{ $booking->guests ?? 0 }}</span>
                        </div>
                        <div class="col-md-2">
                            <p class="text-uppercase small fw-bold text-muted mb-1">💰 Tổng tiền</p>
                            <p class="mb-0 fw-bold text-success">{{ number_format($booking->total_price ?? 0, 0, ',', '.') }} ₫</p>
                        </div>
                    </div>

=======
                        <div class="col-md-2">
                            <p class="text-uppercase small fw-bold text-muted mb-1">🏨 Số lượng phòng</p>
                            <span class="badge bg-primary px-3 py-2">{{ $booking->rooms->count() }} phòng</span>
                        </div>
                        <div class="col-md-3">
                            <p class="text-uppercase small fw-bold text-muted mb-1">💰 Tổng tiền</p>
                            <p class="mb-0 fw-bold text-success fs-5">{{ number_format($booking->total_price ?? 0, 0, ',', '.') }} ₫</p>
                            @if($booking->discount_amount > 0)
                                <small class="text-danger">Đã giảm: {{ number_format($booking->discount_amount, 0, ',', '.') }} ₫ ({{ $booking->coupon_code }})</small>
                            @endif
                        </div>
                    </div>

                    <!-- Room List Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <p class="text-uppercase small fw-bold text-muted mb-2">🏨 Chi tiết phòng</p>
                            <div class="table-responsive rounded-2 border shadow-sm">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Tên phòng</th>
                                            <th>Loại phòng</th>
                                            <th class="text-center">Người lớn</th>
                                            <th class="text-center">Trẻ em</th>
                                            <th class="text-end">Giá/đêm</th>
                                            <th class="text-end pe-3">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($booking->bookingRooms as $br)
                                        <tr>
                                            <td class="ps-3 fw-bold">{{ $br->room->name ?? '—' }}</td>
                                            <td>{{ $br->room->roomType->name ?? '—' }}</td>
                                            <td class="text-center">{{ $br->adults }}</td>
                                            <td class="text-center">{{ $br->children_0_5 + $br->children_6_11 }}</td>
                                            <td class="text-end text-muted">{{ number_format($br->price_per_night, 0, ',', '.') }} ₫</td>
                                            <td class="text-end pe-3 fw-bold text-secondary">{{ number_format($br->subtotal, 0, ',', '.') }} ₫</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if($booking->bookingServices->isNotEmpty())
                    <div class="row mt-3 pt-3 border-top">
                        <div class="col-12">
                            <p class="text-uppercase small fw-bold text-muted mb-2">Dịch vụ kèm theo</p>
                            <div class="table-responsive rounded-2 border bg-white">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Tên dịch vụ</th>
                                            <th class="text-end">SL</th>
                                            <th class="text-end">Đơn giá</th>
                                            <th class="text-end pe-3">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($booking->bookingServices as $bs)
                                        @php
                                            $line = (float) $bs->price * (int) $bs->quantity;
                                        @endphp
                                        <tr>
                                            <td class="ps-3">{{ $bs->service?->name ?? 'Dịch vụ #' . $bs->service_id }}</td>
                                            <td class="text-end">{{ $bs->quantity }}</td>
                                            <td class="text-end text-muted">{{ number_format((float) $bs->price, 0, ',', '.') }} ₫</td>
                                            <td class="text-end pe-3 fw-semibold">{{ number_format($line, 0, ',', '.') }} ₫</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

>>>>>>> vinam
                    @php
                        $hotelInfo = \App\Models\HotelInfo::first();
                        $payment = $booking->payment;
                    @endphp
                    @if($hotelInfo && $hotelInfo->bank_id && $hotelInfo->bank_account && $payment && $payment->method === 'bank_transfer' && in_array($payment->status, ['pending', 'partial']))
                    <!-- QR Code Payment Section -->
                    <div class="row mt-4 pt-3 border-top">
                        <div class="col-12">
                            <h6 class="fw-bold mb-3">📱 Thanh toán qua QR Code</h6>
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    @php
                                        $qrUrl = "https://img.vietqr.io/image/{$hotelInfo->bank_id}-{$hotelInfo->bank_account}-print.png?amount={$payment->amount}&addInfo=BOOKING{$booking->id}&accountName=" . urlencode($hotelInfo->bank_account_name);
                                    @endphp
                                    <img src="{{ $qrUrl }}" alt="QR Code Thanh toán" class="img-fluid border rounded" style="max-width: 200px;">
                                </div>
                                <div class="col-md-8">
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-2"><strong>Ngân hàng:</strong> {{ strtoupper($hotelInfo->bank_id) }}</p>
                                        <p class="mb-2"><strong>Số tài khoản:</strong> {{ $hotelInfo->bank_account }}</p>
                                        <p class="mb-2"><strong>Chủ tài khoản:</strong> {{ $hotelInfo->bank_account_name }}</p>
                                        <p class="mb-2"><strong>Số tiền:</strong> <span class="text-success fw-bold">{{ number_format($payment->amount, 0, ',', '.') }} ₫</span></p>
                                        <p class="mb-0"><strong>Nội dung CK:</strong> <code>BOOKING{{ $booking->id }}</code></p>
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">Khách hàng có thể quét mã QR bằng ứng dụng ngân hàng để thanh toán nhanh chóng.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($booking->actual_check_in || $booking->actual_check_out)
                    <!-- Actual Times Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="bg-light p-3 rounded-2">
                                <div class="row g-3">
                                    <div class="col-auto">
                                        <small class="text-uppercase fw-bold text-muted">⏱️ Thực tế:</small>
                                    </div>
                                    @if($booking->actual_check_in)
                                    <div class="col-auto">
                                        <small class="text-muted">Check-in:</small>
                                        <strong>{{ $booking->actual_check_in->format('d/m H:i') }}</strong>
                                    </div>
                                    @endif
                                    @if($booking->actual_check_out)
                                    <div class="col-auto">
                                        <small class="text-muted">Check-out:</small>
                                        <strong>{{ $booking->actual_check_out->format('d/m H:i') }}</strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Actions Row -->
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex flex-wrap gap-2">
                                @if($booking->isCheckinAllowed())
                                <form action="{{ route('admin.bookings.checkIn', $booking) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm rounded-2">🚪 Check-in Khách</button>
                                </form>
                                @endif

                                @if($booking->isCheckoutAllowed())
                                <form action="{{ route('admin.bookings.checkOut', $booking) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm rounded-2">🚪 Check-out Khách</button>
                                </form>
                                @endif

                                <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-outline-primary btn-sm rounded-2">✏️ Sửa thông tin</a>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <!-- Status Change -->
                            <form action="{{ route('admin.bookings.updateStatus', $booking) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <select name="status" class="form-select form-select-sm rounded-2">
                                    <option value="pending" {{ $booking->status=='pending'?'selected':'' }}>⏳ Chờ xác nhận</option>
                                    <option value="confirmed" {{ $booking->status=='confirmed'?'selected':'' }}>✓ Đã xác nhận</option>
                                    <option value="cancelled" {{ $booking->status=='cancelled'?'selected':'' }}>✕ Hủy đơn</option>
                                </select>
                                <button type="submit" class="btn btn-outline-primary btn-sm rounded-2" title="Cập nhật">💾</button>
                            </form>
                        </div>

                        <div class="col-md-3">
                            <!-- Info and Actions -->
                            <div class="d-flex align-items-center justify-content-between">
                                <small class="text-muted">
                                    ID: #{{ $booking->id }} |
                                    Tạo: {{ $booking->created_at?->format('d/m/Y') ?? '—' }}
                                </small>
                                @if(auth()->user() && auth()->user()->role === 'admin')
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-2" data-bs-toggle="modal" data-bs-target="#deleteModal">🗑️</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Section - Collapsible -->
        <div class="col-12">
            <div class="card border-0 rounded-3 shadow-sm">
                <div class="card-header bg-light border-0 rounded-top-3 py-2">
                    <h6 class="mb-0 fw-bold">
                        <button class="btn btn-link p-0 text-decoration-none fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#historyCollapse">
                            📝 Lịch sử thay đổi
                        </button>
                    </h6>
                </div>
                <div class="collapse" id="historyCollapse">
                    <div class="card-body py-3">
                        @if($booking->logs && $booking->logs->count())
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($booking->logs as $log)
                                    <div class="d-flex align-items-center gap-2 bg-light px-3 py-2 rounded-2">
                                        <span class="badge bg-light text-dark small">{{ ucfirst($log->old_status) }}</span>
                                        <span class="text-muted small">→</span>
                                        <span class="badge bg-primary small">{{ ucfirst($log->new_status) }}</span>
                                        <small class="text-muted">{{ $log->changed_at?->format('d/m H:i') ?? '—' }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center py-3 mb-0">📭 Chưa có lịch sử thay đổi</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
@if(auth()->user() && auth()->user()->role === 'admin')
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-3 border-0">
            <div class="modal-header bg-danger text-white border-0 rounded-top-3">
                <h5 class="modal-title fw-bold">⚠️ Xác nhận xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xóa đơn #{{ $booking->id }}? <strong>Không thể hoàn tác.</strong>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal">Hủy</button>
                <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded-2">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<style>
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12) !important;
    }
    .rounded-2 { border-radius: 8px !important; }
    .rounded-3 { border-radius: 12px !important; }
    .sticky-top { position: sticky; z-index: 100; }
</style>
@endsection
