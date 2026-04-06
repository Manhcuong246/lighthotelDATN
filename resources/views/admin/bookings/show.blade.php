@extends('layouts.admin')

@section('title', 'Chi tiết đặt phòng')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <!-- Header -->
    <div class="mb-4">
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary btn-admin-icon rounded-2 mb-3" title="Quay lại danh sách"><i class="bi bi-arrow-left"></i></a>
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
                    <!-- Top Row: Customer + Booking Info (editable) -->
                    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" id="bookingInfoForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="{{ $booking->status }}">
                        <div class="row g-3 mb-4 align-items-end">
                            <div class="col-md-3">
                                <p class="text-uppercase small fw-bold text-muted mb-1">Khách hàng</p>
                                <p class="mb-0 fw-bold text-primary">{{ $booking->user?->full_name ?? '—' }}</p>
                                <small class="text-muted d-block">{{ $booking->user?->email ?? '—' }}</small>
                                <small class="text-muted">{{ $booking->user?->phone ?? '—' }}</small>
                            </div>
                            <div class="col-md-2">
                                <label for="check_in" class="text-uppercase small fw-bold text-muted mb-1 d-block">Nhận phòng</label>
                                <input type="date" class="form-control form-control-sm" id="check_in" name="check_in"
                                       value="{{ $booking->check_in?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="check_out" class="text-uppercase small fw-bold text-muted mb-1 d-block">Trả phòng</label>
                                <input type="date" class="form-control form-control-sm" id="check_out" name="check_out"
                                       value="{{ $booking->check_out?->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="total_price" class="text-uppercase small fw-bold text-muted mb-1 d-block">Tổng tiền</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm" id="total_price" name="total_price"
                                           min="0" step="1000" value="{{ $booking->total_price }}">
                                    <span class="input-group-text">₫</span>
                                </div>
                                @if($booking->discount_amount > 0)
                                    <small class="text-danger">Giảm: {{ number_format($booking->discount_amount, 0, ',', '.') }} ₫</small>
                                @endif
                            </div>
                            <div class="col-md-1 text-center">
                                <p class="text-uppercase small fw-bold text-muted mb-1">Phòng</p>
                                <span class="badge bg-primary px-3 py-2">{{ $booking->rooms->count() }}</span>
                            </div>
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-sm btn-outline-primary rounded-2">
                                    <i class="bi bi-check-lg me-1"></i>Lưu
                                </button>
                            </div>
                        </div>
                    </form>

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
                                            <th class="text-center" title="Trẻ 6–11 tuổi + Trẻ 0–5 tuổi (miễn phí)">Trẻ em</th>
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
                                            <td class="text-center">
                                                @if($br->children_6_11 > 0)<span title="Trẻ 6–11 tuổi (tính vào occupancy)">{{ $br->children_6_11 }}<small class="text-muted ms-1">6–11t</small></span>@endif
                                                @if($br->children_0_5 > 0)<span title="Trẻ 0–5 tuổi (miễn phí, tính sức chứa)">{{ $br->children_6_11 > 0 ? ' + ' : '' }}{{ $br->children_0_5 }}<small class="text-muted ms-1">0–5t</small></span>@endif
                                                @if($br->children_6_11 + $br->children_0_5 === 0) — @endif
                                            </td>
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

                    @if($booking->surcharges && $booking->surcharges->isNotEmpty())
                    <div class="row mt-3 pt-3 border-top">
                        <div class="col-12">
                            <p class="text-uppercase small fw-bold text-muted mb-2">🔥 Phiếu phát sinh (Phụ thu)</p>
                            <div class="table-responsive rounded-2 border border-danger bg-white">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-danger text-danger">
                                        <tr>
                                            <th class="ps-3">Lý do phụ thu</th>
                                            <th class="text-end">Ngày giờ lập</th>
                                            <th class="text-end pe-3">Số tiền thu thêm</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($booking->surcharges as $surcharge)
                                        <tr>
                                            <td class="ps-3 fw-bold text-dark">{{ $surcharge->reason }}</td>
                                            <td class="text-end text-muted">{{ $surcharge->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="text-end pe-3 fw-semibold text-danger">+ {{ number_format($surcharge->amount, 0, ',', '.') }} ₫</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

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
                                        <small class="text-muted">Ngày nhận phòng:</small>
                                        <strong>{{ $booking->actual_check_in->format('d/m H:i') }}</strong>
                                    </div>
                                    @endif
                                    @if($booking->actual_check_out)
                                    <div class="col-auto">
                                        <small class="text-muted">Ngày trả phòng:</small>
                                        <strong>{{ $booking->actual_check_out->format('d/m H:i') }}</strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Trạng thái đơn & thanh toán (chỉnh thủ công) — neo #payment-booking-settings từ màn Thanh toán -->
                    <div id="payment-booking-settings" class="row g-3 mt-2 pt-3 border-top" style="scroll-margin-top: 5rem;">
                        <div class="col-12">
                            <h6 class="fw-bold mb-2">⚙️ Tiến trình đặt phòng &amp; ghi nhận thanh toán</h6>
                            <p class="small text-muted mb-3">
                                <strong>Hai cột khác nhau:</strong> <em>Tiến trình đơn</em> = đơn có được <strong>xác nhận giữ phòng</strong> hay chưa, có <strong>hoàn thành lưu trú</strong> hay <strong>hủy</strong>.
                                <em>Thu tiền / thanh toán</em> = đã <strong>ghi nhận tiền vào sổ</strong> hay vẫn <strong>chờ thu</strong> (có thể lệch với bản ghi VNPay đang pending).
                                <br>
                                <strong>Nghiệp vụ:</strong> Khách đổi cách trả (ví dụ có link VNPay nhưng trả <strong>tiền mặt</strong>): chọn <strong>Tiền mặt</strong>, đổi tiến trình sang <strong>Đã xác nhận</strong> và cột thanh toán sang <strong>Đã ghi nhận thanh toán</strong>, rồi <strong>Lưu</strong>.
                                Đơn <strong>đã hủy</strong> không khôi phục giữ phòng tại đây — cần đơn mới. Không chỉnh khi đơn đã <strong>hoàn tiền</strong>.
                            </p>
                            @php
                                $paymentLocked = in_array((string) $booking->payment_status, ['refunded', 'partial_refunded'], true);
                                $isCancelled = $booking->status === 'cancelled';
                            @endphp
                            @if($paymentLocked)
                                <div class="alert alert-warning mb-0">Đơn có hoàn tiền — không chỉnh thanh toán tại đây.</div>
                            @else
                            @if($booking->status === 'confirmed' && $booking->payment_status === 'pending')
                                <div class="alert alert-warning py-2 mb-3">
                                    Dữ liệu đang <strong>lệch</strong>: tiến trình là <strong>Đã xác nhận</strong> nhưng thanh toán vẫn <strong>Chưa ghi nhận</strong>.
                                    Hãy chỉnh cho khớp rồi Lưu (thường là chọn <strong>Đã ghi nhận thanh toán</strong> nếu khách đã trả).
                                </div>
                            @endif
                            <form action="{{ route('admin.bookings.update-payment-settings', $booking) }}" method="POST" class="row g-3 align-items-end">
                                @csrf
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Tiến trình đơn</label>
                                    <span class="d-block small text-muted mb-1">Đặt phòng: chờ TT → xác nhận giữ phòng → hoàn thành / hủy</span>
                                    @if($isCancelled)
                                        <input type="hidden" name="booking_status" value="cancelled">
                                        <input type="text" class="form-control form-control-sm bg-light" value="Đã hủy (không khôi phục giữ phòng)" disabled>
                                    @else
                                        <select name="booking_status" class="form-select form-select-sm" required>
                                            <option value="pending" @selected($booking->status === 'pending')>Chờ xác nhận (chưa giữ phòng)</option>
                                            <option value="confirmed" @selected($booking->status === 'confirmed')>Đã xác nhận — giữ phòng</option>
                                            <option value="completed" @selected($booking->status === 'completed')>Hoàn thành lưu trú</option>
                                            <option value="cancelled" @selected($booking->status === 'cancelled')>Hủy — mở ngày phòng</option>
                                        </select>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Ghi nhận thanh toán</label>
                                    <span class="d-block small text-muted mb-1">Sổ quỹ: tiền đã thu đủ hay vẫn chờ (độc lập bản ghi VNPay)</span>
                                    <select name="payment_status" class="form-select form-select-sm" required>
                                        <option value="pending" @selected($booking->payment_status === 'pending')>Chưa ghi nhận thanh toán</option>
                                        <option value="paid" @selected($booking->payment_status === 'paid')>Đã ghi nhận thanh toán</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Phương thức thanh toán</label>
                                    <select name="payment_method" class="form-select form-select-sm" required>
                                        <option value="cash" @selected($booking->payment_method === 'cash')>Tiền mặt</option>
                                        <option value="vnpay" @selected($booking->payment_method === 'vnpay')>VNPay</option>
                                        <option value="bank_transfer" @selected($booking->payment_method === 'bank_transfer')>Chuyển khoản (đơn cũ)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-2">
                                        <i class="bi bi-check2-lg me-1"></i>Lưu thay đổi
                                    </button>
                                </div>
                                @if(!empty($latestPayment))
                                <div class="col-12 small text-muted border-top pt-2">
                                    Bản ghi thanh toán mới nhất: mã <code>{{ $latestPayment->transaction_id ?? '—' }}</code>,
                                    số tiền <strong>{{ number_format((float) ($latestPayment->amount ?? 0), 0, ',', '.') }} ₫</strong>,
                                    PTTT <strong>{{ $latestPayment->method }}</strong> / trạng thái <strong>{{ $latestPayment->status }}</strong>
                                    @if($latestPayment->paid_at)
                                        / lúc {{ $latestPayment->paid_at->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                                @endif
                            </form>
                            @endif
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex flex-wrap gap-2">
                                @if($booking->isAdminCheckinAllowed())
                                <form action="{{ route('admin.bookings.checkIn', $booking) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm rounded-2 btn-admin-icon" title="Nhận phòng"><i class="bi bi-box-arrow-in-right"></i></button>
                                </form>
                                @endif

                                @if($booking->isAdminCheckoutAllowed())
                                <form action="{{ route('admin.bookings.checkOut', $booking) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm rounded-2 btn-admin-icon" title="Trả phòng"><i class="bi bi-box-arrow-right"></i></button>
                                </form>
                                @endif

                                @if(!$booking->invoice)
                                <a href="{{ route('admin.invoices.create', $booking) }}" class="btn btn-outline-secondary btn-sm rounded-2 btn-admin-icon" title="Tạo hóa đơn"><i class="bi bi-receipt"></i></a>
                                @else
                                <a href="{{ route('admin.invoices.show', $booking->invoice) }}" class="btn btn-outline-secondary btn-sm rounded-2 btn-admin-icon" title="Xem hóa đơn"><i class="bi bi-receipt-cutoff"></i></a>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Info and Actions -->
                            <div class="d-flex align-items-center justify-content-between">
                                <small class="text-muted">
                                    ID: #{{ $booking->id }} |
                                    Tạo: {{ $booking->created_at?->format('d/m/Y') ?? '—' }}
                                </small>
                                @if(auth()->user() && auth()->user()->role === 'admin')
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-2 btn-admin-icon" data-bs-toggle="modal" data-bs-target="#deleteModal" title="Xóa đơn"><i class="bi bi-trash"></i></button>
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
                <button type="button" class="btn btn-outline-secondary rounded-2 btn-admin-icon" data-bs-dismiss="modal" title="Hủy"><i class="bi bi-x-lg"></i></button>
                <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded-2 btn-admin-icon" title="Xóa đơn"><i class="bi bi-trash"></i></button>
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
