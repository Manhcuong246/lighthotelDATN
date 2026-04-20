@extends('layouts.admin')

@section('title', 'Đơn đặt phòng & thanh toán')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold mb-0">Đơn đặt phòng &amp; thanh toán</h1>
        <div class="d-flex flex-wrap gap-2">
            @if(
    Auth::user()->roles->where('name','admin')->count()
    || 
    Auth::user()->roles->where('name','staff')->count()
)

<a href="{{ route('admin.bookings.create') }}" class="btn btn-primary">
    Tạo booking mới
</a>

@endif

            <div class="dropdown">
                <button class="btn btn-light btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell-fill fs-5" style="color: #ff6b6b;"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $counts['total'] ?? 0 }}
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 280px;">
                    <li><h6 class="dropdown-header">Đơn đặt phòng</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.bookings.index') }}">
                            <div class="d-flex justify-content-between">
                                <span><strong>Tổng đơn:</strong></span>
                                <span class="badge bg-primary">{{ $counts['total'] ?? 0 }}</span>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.bookings.index') }}?status=pending">
                            <div class="d-flex justify-content-between">
                                <span><strong>Chờ thanh toán:</strong></span>
                                <span class="badge bg-warning">{{ $counts['pending'] ?? 0 }}</span>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.bookings.index') }}?status=confirmed">
                            <div class="d-flex justify-content-between">
                                <span><strong>Đã thanh toán:</strong></span>
                                <span class="badge bg-success">{{ $counts['confirmed'] ?? 0 }}</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>✅ Thành công!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>❌ Lỗi!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background: linear-gradient(90deg, #3b49d6 0%, #4b3bd6 100%);">
            <h5 class="mb-0 text-white fw-semibold">Đơn &amp; giao dịch thanh toán</h5>
            <form action="{{ route('admin.bookings.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Khách, phòng, mã đơn, mã GD…" style="width: 220px;">
                <span class="text-white small align-self-center text-nowrap me-1">Nhận từ</span>
                <input type="date" name="check_in_from" value="{{ request('check_in_from') }}" class="form-control form-control-sm" style="width: 150px;" aria-label="Nhận phòng từ ngày" title="Nhận phòng — từ">
                <span class="text-white-50 small align-self-center text-nowrap mx-1">→</span>
                <input type="date" name="check_in_to" value="{{ request('check_in_to') }}" class="form-control form-control-sm" style="width: 150px;" aria-label="Nhận phòng đến ngày" title="Nhận phòng — đến">
                <span class="text-white small align-self-center text-nowrap ms-2 me-1">Trả từ</span>
                <input type="date" name="check_out_from" value="{{ request('check_out_from') }}" class="form-control form-control-sm" style="width: 150px;" aria-label="Trả phòng từ ngày" title="Trả phòng — từ">
                <span class="text-white-50 small align-self-center text-nowrap mx-1">→</span>
                <input type="date" name="check_out_to" value="{{ request('check_out_to') }}" class="form-control form-control-sm" style="width: 150px;" aria-label="Trả phòng đến ngày" title="Trả phòng — đến">
                <select name="status" class="form-select form-select-sm" style="width: 170px;" title="Trạng thái">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Đã thanh toán</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
                <button type="submit" class="btn btn-light btn-sm btn-admin-icon" title="Tìm"><i class="bi bi-search"></i></button>
                @if(request()->hasAny(['q','status','check_in_from','check_in_to','check_out_from','check_out_to']))
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-light btn-sm btn-admin-icon" title="Xóa bộ lọc"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow: visible;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 170px;">Khách hàng</th>
                            <th style="width: 110px;">Phòng</th>
                            <th style="width: 90px;">Nhận phòng</th>
                            <th style="width: 90px;">Trả phòng</th>
                            <th style="width: 52px;" class="text-center"><span class="cursor-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Cột trạng thái lưu trú: di chuột vào từng icon hàng bên dưới để xem chú thích.">Lưu trú</span></th>
                            <th style="width: 45px;" class="text-center">SL</th>
                            <th style="width: 120px;" class="text-end">Tổng tiền</th>
                            <th style="width: 145px;">Trạng thái</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            @php
                                $lp = $booking->latestPayment;
                                $method = $lp ? $lp->method : $booking->payment_method;
                                $paySt  = $lp ? $lp->status : ($booking->payment_status ?? 'pending');
                                $ledgerPs = (string) ($booking->payment_status ?? 'pending');

                                $methodLabels = [
                                    'vnpay'         => ['text' => 'VNPay',       'color' => 'dark'],
                                    'cash'          => ['text' => 'Tiền mặt',    'color' => 'secondary'],
                                    'credit_card'   => ['text' => 'Thẻ',         'color' => 'info'],
                                    'bank_transfer' => ['text' => 'Chuyển khoản','color' => 'primary'],
                                ];
                            @endphp
                            <tr>
                                <td><strong>#{{ $booking->id }}</strong></td>
                                <td>
                                    <div class="fw-semibold">{{ $booking->user?->full_name ?? '—' }}</div>
                                    <small class="text-muted">{{ $booking->user?->email ?? '' }}</small>
                                </td>
                                <td>
                                    @if($booking->rooms->count() > 1)
                                        <div class="fw-semibold text-primary">{{ $booking->rooms->count() }} phòng</div>
                                        <small class="text-muted">{{ $booking->rooms->pluck('name')->implode(', ') }}</small>
                                    @else
                                        <span class="badge bg-primary">{{ $booking->rooms->first()->name ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>{{ $booking->check_in?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $booking->check_out?->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-center">
                                    @switch($booking->adminStayPhase())
                                        @case('cancelled')
                                            <span class="text-danger cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Đã hủy đơn" title="Icon cấm (đỏ): Đơn đã hủy — không áp dụng theo dõi check-in/check-out."><i class="bi bi-slash-circle fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('pending_payment')
                                            <span class="text-muted cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Chờ thanh toán" title="Icon đồng hồ cát: Chưa thanh toán / chưa xác nhận — chưa thể coi là lưu trú thực tế cho đến khi đơn được xác nhận."><i class="bi bi-hourglass-split fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('not_checked_in')
                                            <span class="text-warning cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Chưa check-in" title="Icon cửa đóng (vàng): Đơn đã xác nhận, còn trong kỳ đặt — chưa ghi nhận check-in."><i class="bi bi-door-closed fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('stay_overdue')
                                            <span class="text-danger cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Quá hạn check-in" title="Icon lịch X (đỏ): Đã quá ngày trả phòng mà vẫn chưa check-in — cần xử lý ngoại lệ, cập nhật ngày hoặc hủy đơn."><i class="bi bi-calendar-x fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('checked_in')
                                            <span class="text-primary cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Đã check-in" title="Icon nhà + tích (xanh dương): Khách đã check-in — chưa ghi nhận check-out."><i class="bi bi-house-check fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('checked_out')
                                            <span class="text-success cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Đã check-out" title="Icon mũi tên ra (xanh lá): Đã check-out / hoàn thành lưu trú."><i class="bi bi-box-arrow-right fs-5" aria-hidden="true"></i></span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="text-center" title="Tổng khách (NL + trẻ 6–11 + trẻ 0–5)">
                                    @php
                                        $effOcc = $booking->bookingRooms->sum(fn($br) => $br->adults + $br->children_6_11);
                                        $infants = $booking->bookingRooms->sum(fn($br) => $br->children_0_5);
                                    @endphp
                                    <span class="badge bg-secondary">{{ $effOcc }}</span>
                                    @if($infants > 0)<small class="text-muted d-block" style="font-size:.65rem">+{{ $infants }} trẻ nhỏ</small>@endif
                                </td>
                                <td class="text-end">
                                    <strong class="text-success">{{ number_format($booking->total_price ?? 0, 0, ',', '.') }} ₫</strong>
                                </td>
                                <td>
                                    @if($booking->status === 'cancelled')
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Đã hủy</span>
                                    @elseif($booking->status === 'completed')
                                        <span class="badge bg-success"><i class="bi bi-check-all me-1"></i>Hoàn thành</span>
                                    @elseif(in_array($ledgerPs, ['refunded', 'partial_refunded'], true))
                                        <span class="badge bg-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>{{ $ledgerPs === 'partial_refunded' ? 'Hoàn tiền một phần' : 'Đã hoàn tiền' }}</span>
                                    @elseif($ledgerPs === 'paid')
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Đã ghi nhận thanh toán</span>
                                        @if($method && isset($methodLabels[$method]))
                                            <br><span class="badge bg-{{ $methodLabels[$method]['color'] }} mt-1" style="font-size:.7rem">{{ $methodLabels[$method]['text'] }}</span>
                                        @endif
                                    @elseif($ledgerPs === 'partial')
                                        <span class="badge bg-info text-dark"><i class="bi bi-piggy-bank me-1"></i>Đặt cọc / một phần</span>
                                        @if($method && isset($methodLabels[$method]))
                                            <br><span class="badge bg-{{ $methodLabels[$method]['color'] }} mt-1" style="font-size:.7rem">{{ $methodLabels[$method]['text'] }}</span>
                                        @endif
                                    @elseif($paySt === 'failed')
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>TT thất bại</span>
                                    @elseif($paySt === 'paid' && $ledgerPs === 'pending')
                                        <span class="badge bg-info text-dark"><i class="bi bi-bank me-1"></i>Cổng đã thanh toán</span>
                                        <br><small class="text-muted" style="font-size:.65rem">Chưa ghi nhận sổ</small>
                                        @if($method && isset($methodLabels[$method]))
                                            <br><span class="badge bg-{{ $methodLabels[$method]['color'] }} mt-1" style="font-size:.7rem">{{ $methodLabels[$method]['text'] }}</span>
                                        @endif
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Chờ thanh toán</span>
                                        @if($method && isset($methodLabels[$method]))
                                            <br><span class="badge bg-{{ $methodLabels[$method]['color'] }} mt-1" style="font-size:.7rem">{{ $methodLabels[$method]['text'] }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 align-items-center">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary btn-admin-icon" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-admin-icon dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Thao tác">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" style="position: fixed; inset: 0px auto auto 0px; transform: translate3d(0px, 38px, 0px); z-index: 9999;">
                                                @if($booking->status === 'pending')
                                                <li>
                                                    <form action="{{ route('admin.bookings.updateStatus', $booking) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="status" value="confirmed">
                                                        <button class="dropdown-item text-success">
                                                            <i class="bi bi-check-circle me-2"></i> Xác nhận thanh toán
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif

                                                @if($booking->isAdminCheckinAllowed())
                                                <li>
                                                    <button type="button" class="dropdown-item text-info" data-bs-toggle="modal" data-bs-target="#checkinModal{{ $booking->id }}">
                                                        <i class="bi bi-box-arrow-in-right me-2"></i> Check-in
                                                    </button>
                                                </li>
                                                @endif

                                                @if($booking->isAdminCheckoutAllowed())
                                                <li>
                                                    <form action="{{ route('admin.bookings.checkOut', $booking) }}" method="POST">
                                                        @csrf
                                                        <button class="dropdown-item text-warning" type="submit">
                                                            <i class="bi bi-box-arrow-right me-2"></i> Check-out
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif

                                                @if($booking->status !== 'cancelled')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.bookings.show', $booking) }}#booking-extras-form">
                                                        <i class="bi bi-plus-circle me-2"></i> Phát sinh / phụ thu
                                                    </a>
                                                </li>
                                                @endif

                                                @if($booking->status === 'pending')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $booking->id }}">
                                                        <i class="bi bi-x-circle me-2"></i> Hủy đơn chưa thanh toán
                                                    </button>
                                                </li>
                                                @endif

                                                @if(auth()->user()->isAdmin())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn đơn #{{ $booking->id }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i> Xóa vĩnh viễn
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                    📭 Chưa có đơn đặt phòng nào
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if(method_exists($bookings, 'hasPages') && $bookings->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $bookings->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<style>
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    .card-header {
        border-radius: 12px 12px 0 0 !important;
    }
    .table th {
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .table td {
        vertical-align: middle;
    }
    .badge {
        font-size: 0.85rem;
        padding: 0.4em 0.7em;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
    
    /* Modal check-in custom styles */
    .modal-lg {
        max-width: 800px;
    }
    .guest-name {
        font-weight: 500;
    }
    .guest-cccd {
        font-family: monospace;
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
    }
    .guest-list-container {
        max-height: 400px;
        overflow-y: auto;
    }
    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
    .alert-light {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
</style>

<!-- Modal: hủy đơn chờ (chưa thanh toán) — đơn đã thanh toán xử lý qua Quản lý hoàn tiền -->
@if(isset($bookings) && $bookings->count())
@foreach($bookings as $booking)
<div class="modal fade" id="cancelModal{{ $booking->id }}" tabindex="-1" aria-labelledby="cancelModalLabel{{ $booking->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelModalLabel{{ $booking->id }}">
                    <i class="bi bi-x-circle me-2"></i>Hủy đơn chưa thanh toán #{{ $booking->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.bookings.cancel', $booking) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Chỉ dùng cho đơn chờ xác nhận / chưa thanh toán.</strong> Đơn khách đã trả tiền: xử lý hoàn tiền trong mục Hoàn tiền.
                    </div>
                    
                    <div class="mb-3">
                        <label for="cancelReason{{ $booking->id }}" class="form-label fw-bold">
                            Lý do hủy đơn <span class="text-danger">*</span>
                        </label>
                        <textarea name="cancel_reason" id="cancelReason{{ $booking->id }}" class="form-control" rows="4" 
                                  placeholder="Nhập lý do hủy đơn đặt phòng..." required></textarea>
                        <div class="form-text">Lý do sẽ được hiển thị cho khách hàng trong lịch sử đặt phòng.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Khách hàng:</strong><br>
                            {{ $booking->user?->full_name ?? '—' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Phòng:</strong><br>
                            @if($booking->rooms->count() > 1)
                                {{ $booking->rooms->count() }} phòng
                            @else
                                {{ $booking->rooms->first()->name ?? '—' }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-admin-icon" data-bs-dismiss="modal" title="Đóng"><i class="bi bi-x-lg"></i></button>
                    <button type="submit" class="btn btn-danger btn-admin-icon" title="Xác nhận hủy"><i class="bi bi-x-octagon"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endif

<!-- Modal: Check-in khách hàng -->
@if(isset($bookings) && $bookings->count())
@foreach($bookings as $booking)
@if($booking->isAdminCheckinAllowed())
<div class="modal fade" id="checkinModal{{ $booking->id }}" tabindex="-1" aria-labelledby="checkinModalLabel{{ $booking->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="checkinModalLabel{{ $booking->id }}">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Kiểm tra thông tin khách hàng - Đơn #{{ $booking->id }}
                </h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light btn-sm" id="editGuestsBtn{{ $booking->id }}">
                        <i class="bi bi-pencil me-1"></i>Sửa thông tin
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <form action="{{ route('admin.bookings.checkIn', $booking) }}" method="POST" id="checkinForm{{ $booking->id }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Kiểm tra thông tin khách hàng trước khi check-in:</strong> Vui lòng xác nhận thông tin CCCD và tên của khách hàng cho từng phòng.
                    </div>
                    
                    <!-- Thông tin booking -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Khách hàng:</strong><br>
                            {{ $booking->user?->full_name ?? '—' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Phòng:</strong><br>
                            @if($booking->rooms->count() > 1)
                                {{ $booking->rooms->count() }} phòng: {{ $booking->rooms->pluck('name')->implode(', ') }}
                            @else
                                {{ $booking->rooms->first()->name ?? '—' }}
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ngày nhận phòng:</strong> {{ $booking->check_in?->format('d/m/Y') ?? '—' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Ngày trả phòng:</strong> {{ $booking->check_out?->format('d/m/Y') ?? '—' }}
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Danh sách khách hàng -->
                    <div class="guest-list-container">
                        <div class="text-center py-3">
                            <div class="spinner-border text-info" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p class="mt-2 text-muted">Đang tải thông tin khách hàng...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Đóng
                    </button>
                    <button type="submit" class="btn btn-info" id="checkinSubmitBtn{{ $booking->id }}" disabled style="display: none;">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Xác nhận Check-in
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('checkinModal{{ $booking->id }}');
    if (modal) {
        let confirmCheckboxes = [];
        let submitBtn = document.getElementById('checkinSubmitBtn{{ $booking->id }}');
        let confirmedCount = document.getElementById('confirmedCount{{ $booking->id }}');
        let remainingCount = document.getElementById('remainingCount{{ $booking->id }}');
        
        // Load guest info when modal is shown
        modal.addEventListener('show.bs.modal', function () {
            const url = `/admin/bookings/{{ $booking->id }}/guest-info`;
            console.log('Fetching guest info from:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response ok:', response.ok);
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.error) {
                        console.error('Server error:', data.error);
                        return;
                    }
                    
                    // Update booking info
                    const bookingInfo = data.booking;
                    const guestList = data.guests;
                    
                    // Update guest list
                    const guestListContainer = modal.querySelector('.guest-list-container');
                    if (guestList.length === 0) {
                        guestListContainer.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Chưa có thông tin khách hàng. Vui lòng thêm thông tin khách hàng trước khi check-in.
                            </div>
                        `;
                        if (submitBtn) submitBtn.style.display = 'none';
                        return;
                    }
                    
                    // Build guest table
                    let guestTableHTML = `
                        <h6 class="mb-3">
                            <i class="bi bi-people-fill me-2"></i>Danh sách khách hàng
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30px;">STT</th>
                                        <th>Tên khách hàng</th>
                                        <th>Loại</th>
                                        <th>CCCD</th>
                                        <th style="width: 100px;">Xác nhận</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    guestList.forEach((guest, index) => {
                        guestTableHTML += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>
                                    <span class="guest-name">${guest.name}</span>
                                </td>
                                <td>
                                    <span class="badge bg-${guest.type === 'adult' ? 'primary' : 'info'}">
                                        ${guest.type === 'adult' ? 'Người lớn' : 'Trẻ em'}
                                    </span>
                                </td>
                                <td>
                                    <span class="guest-cccd">${guest.cccd || '-'}</span>
                                </td>
                                <td class="text-center">
                                    <div class="form-check">
                                        <input class="form-check-input guest-confirmation" 
                                               type="checkbox" 
                                               id="guestConfirm${guest.id}" 
                                               data-guest-id="${guest.id}"
                                               data-guest-name="${guest.name}"
                                               data-guest-cccd="${guest.cccd || ''}">
                                        <label class="form-check-label" for="guestConfirm${guest.id}">
                                            Xác nhận
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    guestTableHTML += `
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Tóm tắt xác nhận -->
                        <div class="alert alert-light mt-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Tổng khách:</strong> ${guestList.length}
                                </div>
                                <div class="col-md-4">
                                    <strong>Đã xác nhận:</strong> <span id="confirmedCount{{ $booking->id }}">0</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Cần xác nhận:</strong> <span id="remainingCount{{ $booking->id }}">${guestList.length}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    guestListContainer.innerHTML = guestTableHTML;
                    
                    // Re-initialize checkboxes and event listeners
                    confirmCheckboxes = modal.querySelectorAll('.guest-confirmation');
                    confirmedCount = document.getElementById('confirmedCount{{ $booking->id }}');
                    remainingCount = document.getElementById('remainingCount{{ $booking->id }}');
                    
                    function updateConfirmationStatus() {
                        const totalGuests = confirmCheckboxes.length;
                        const confirmed = Array.from(confirmCheckboxes).filter(cb => cb.checked).length;
                        const remaining = totalGuests - confirmed;
                        
                        if (confirmedCount) confirmedCount.textContent = confirmed;
                        if (remainingCount) remainingCount.textContent = remaining;
                        
                        if (submitBtn) {
                            submitBtn.disabled = confirmed < totalGuests;
                            if (confirmed >= totalGuests) {
                                submitBtn.classList.remove('btn-info');
                                submitBtn.classList.add('btn-success');
                            } else {
                                submitBtn.classList.remove('btn-success');
                                submitBtn.classList.add('btn-info');
                            }
                        }
                    }
                    
                    confirmCheckboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', updateConfirmationStatus);
                    });
                    
                    // Initialize status
                    updateConfirmationStatus();
                    
                })
                .catch(error => {
                    console.error('Error loading guest info:', error);
                    console.error('Error details:', error.message);
                    
                    // Show error message to user
                    const guestListContainer = modal.querySelector('.guest-list-container');
                    if (guestListContainer) {
                        guestListContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Lỗi tải dữ liệu:</strong> ${error.message || 'Không thể tải thông tin khách hàng. Vui lòng thử lại.'}
                                <br><small>Vui lòng kiểm tra console để biết chi tiết lỗi.</small>
                            </div>
                        `;
                    }
                });
        });
    }
});
</script>
@endif
@endforeach
@endif

@endsection
