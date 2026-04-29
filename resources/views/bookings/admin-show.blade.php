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

                            @if($booking->bookingGuests->count() > 0)
                                @foreach($booking->bookingGuests as $guest)
                                    <div class="card mb-3 border-light shadow-sm">
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-sm mb-0">
                                                    <thead class="table-light small">
                                                        <tr>
                                                            <th class="ps-3">Tên khách hàng</th>
                                                            <th>CCCD</th>
                                                            <th>Phòng</th>
                                                            <th>Trạng thái</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="ps-3">
                                                                {{ $guest->name }}
                                                                @if($guest->is_representative)
                                                                    <span class="badge bg-primary ms-2">Người đại diện</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $guest->cccd }}</td>
                                                            <td>{{ $guest->bookingRoom?->room?->roomType?->name }} {{ $guest->bookingRoom?->room?->room_number }}</td>
                                                            <td>
                                                                <span class="badge bg-{{ $guest->status === 'checked_in' ? 'success' : 'warning' }}">
                                                                    {{ $guest->status === 'checked_in' ? 'Đã check-in' : 'Chờ check-in' }}
                                                                </span>
                                                            </td>
                                                        </tr>
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


                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card rounded-3 shadow-sm">
                                <div class="card-header bg-light border-0 rounded-top-3 py-2">
                                    <h6 class="mb-0 fw-bold">📝 Lịch sử thay đổi</h6>
                                </div>
                                <div class="card-body py-3">
                                    @if($booking->logs->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($booking->logs as $log)
                                                <div class="border rounded-2 px-3 py-2 bg-white shadow-sm">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-light text-dark small">{{ ucfirst($log->old_status) }}</span>
                                                        <span class="text-muted small">→</span>
                                                        <span class="badge bg-primary small">{{ ucfirst($log->new_status) }}</span>
                                                    </div>
                                                    <div class="small text-muted">{{ $log->changed_at?->format('d/m H:i') ?? '—' }}</div>
                                                    <div class="small text-muted">Người thực hiện: {{ $log->user?->full_name ?? 'Hệ thống' }}</div>
                                                    @if($log->notes)
                                                        <div class="small text-muted mt-1">{{ $log->notes }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">Chưa có lịch sử thay đổi.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Check-out khách hàng
                            </h5>

                            @if($booking->actual_check_out)
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Đã check-out lúc {{ $booking->actual_check_out->format('d/m/Y H:i') }}
                                </div>
                                <p class="mb-0 small text-muted">
                                    Người check-out: {{ optional(optional($booking->logs->where('new_status', 'completed')->first())->user)->full_name ?? 'Hệ thống' }}
                                </p>
                            @else
                                <form method="POST" action="{{ route('admin.bookings.checkout', $booking->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-warning"
                                            onclick="return confirm('Bạn có chắc chắn muốn check-out khách hàng này?')">
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Check-out
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="bi bi-bag-plus me-2"></i>
                                Dịch vụ kèm đặt phòng
                            </h5>

                            @if($booking->bookingServices->isNotEmpty())
                                <div class="table-responsive rounded-2 border bg-white mb-3">
                                    <table class="table table-sm mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">Tên dịch vụ</th>
                                                <th class="text-end">SL</th>
                                                <th class="text-end">Đơn giá</th>
                                                <th class="text-end pe-3">Thành tiền</th>
                                                <th class="text-center">Xóa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($booking->bookingServices as $bs)
                                                @php $line = (float) $bs->price * (int) $bs->quantity; @endphp
                                                <tr>
                                                    <td class="ps-3">{{ $bs->service?->name ?? 'Dịch vụ #' . $bs->service_id }}</td>
                                                    <td class="text-end">{{ $bs->quantity }}</td>
                                                    <td class="text-end text-muted">{{ number_format((float) $bs->price, 0, ',', '.') }} ₫</td>
                                                    <td class="text-end pe-3 fw-semibold">{{ number_format($line, 0, ',', '.') }} ₫</td>
                                                    <td class="text-center">
                                                        <form action="{{ route('admin.booking-services.delete', $bs->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa dịch vụ này?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-secondary">
                                    Chưa có dịch vụ được gán cho đơn này.
                                </div>
                            @endif

                            @if($booking->status !== 'cancelled' && ! $booking->actual_check_out)
                                <form method="POST" action="{{ route('admin.bookings.storeBookingServices', $booking->id) }}">
                                    @csrf
                                    @if($services->isNotEmpty())
                                        <p class="small text-muted mb-2">Chọn dịch vụ từ danh mục để gán vào đơn sau khi check-in.</p>
                                        @include('admin.bookings.partials.booking-catalog-service-lines', ['services' => $services])
                                        <button type="submit" class="btn btn-primary btn-sm mt-3">
                                            <i class="bi bi-save me-1"></i> Lưu dịch vụ kèm
                                        </button>
                                    @else
                                        <div class="alert alert-warning">Chưa có dịch vụ trong danh mục. Vui lòng thêm dịch vụ trước khi gán.</div>
                                    @endif
                                </form>
                            @endif
                        </div>
                    </div>

                    @php
                        $roomTotal = $booking->bookingRooms->sum('subtotal');
                        $serviceTotal = $booking->bookingServices->sum(function ($bs) {
                            return (float) $bs->price * (int) $bs->quantity;
                        });
                        $discountAmount = $booking->discount_amount ?? 0;
                        $invoiceSubtotal = max(0, $roomTotal + $serviceTotal - $discountAmount);
                        $depositAmount = $booking->payments->sum('amount');
                        $amountDue = max(0, $invoiceSubtotal - $depositAmount);
                    @endphp

                    <div class="row mt-4">
                        <div class="col-lg-6 mb-3">
                            <div class="card rounded-3 shadow-sm h-100">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Thông tin phòng</h5>
                                    @if($booking->bookingRooms->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-3">Phòng</th>
                                                        <th>Loại</th>
                                                        <th class="text-center">Đêm</th>
                                                        <th class="text-end">Giá/đêm</th>
                                                        <th class="text-end pe-3">Thành tiền</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($booking->bookingRooms as $br)
                                                        <tr>
                                                            <td class="ps-3">{{ $br->room?->name ?? '—' }}</td>
                                                            <td>{{ $br->room?->roomType?->name ?? '—' }}</td>
                                                            <td class="text-center">{{ $br->nights ?? $booking->nights }}</td>
                                                            <td class="text-end">{{ number_format($br->price_per_night, 0, ',', '.') }} ₫</td>
                                                            <td class="text-end pe-3">{{ number_format($br->subtotal, 0, ',', '.') }} ₫</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @elseif($booking->room)
                                        <div class="mb-2"><strong>Phòng:</strong> {{ $booking->room->name }}</div>
                                        <div class="mb-2"><strong>Loại phòng:</strong> {{ $booking->room->roomType->name ?? '—' }}</div>
                                        <div class="mb-2"><strong>Số đêm:</strong> {{ $booking->nights }}</div>
                                        <div class="mb-2"><strong>Giá phòng:</strong> {{ number_format($booking->total_price, 0, ',', '.') }} ₫</div>
                                    @else
                                        <p class="mb-0 text-muted">Không có thông tin phòng chi tiết.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <div class="card rounded-3 shadow-sm h-100">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Chi tiết hóa đơn</h5>
                                    <dl class="row mb-0">
                                        <dt class="col-7 text-muted">Tiền phòng</dt>
                                        <dd class="col-5 text-end">{{ number_format($roomTotal, 0, ',', '.') }} ₫</dd>

                                        <dt class="col-7 text-muted">Dịch vụ</dt>
                                        <dd class="col-5 text-end">{{ number_format($serviceTotal, 0, ',', '.') }} ₫</dd>

                                        @if($discountAmount > 0)
                                            <dt class="col-7 text-muted">Giảm giá</dt>
                                            <dd class="col-5 text-end text-danger">- {{ number_format($discountAmount, 0, ',', '.') }} ₫</dd>
                                        @endif

                                        <dt class="col-7 fw-semibold">Tổng trước cọc</dt>
                                        <dd class="col-5 text-end fw-semibold">{{ number_format($invoiceSubtotal, 0, ',', '.') }} ₫</dd>

                                        <dt class="col-7 text-muted">Đã cọc</dt>
                                        <dd class="col-5 text-end text-success">{{ number_format($depositAmount, 0, ',', '.') }} ₫</dd>

                                        <dt class="col-7 fw-semibold">Còn nợ</dt>
                                        <dd class="col-5 text-end fw-bold">{{ number_format($amountDue, 0, ',', '.') }} ₫</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

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
