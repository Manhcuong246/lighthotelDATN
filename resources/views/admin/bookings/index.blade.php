@extends('layouts.admin')

@section('title', 'Đơn đặt phòng & thanh toán')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold mb-0">Đơn đặt phòng &amp; thanh toán</h1>
        <div class="d-flex flex-wrap gap-2">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.bookings.create-multi') }}" class="btn btn-primary">
                <i class="bi bi-layers me-1"></i> Tạo đơn nhiều phòng
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
                                                    <form action="{{ route('admin.bookings.checkout', $booking) }}" method="POST">
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

<!-- Modal: Check-in khách hàng (mới) -->
@if(isset($bookings) && $bookings->count())
    @foreach($bookings as $booking)
        @if($booking->isAdminCheckinAllowed())
            @include('admin.bookings._checkin_modal', ['booking' => $booking])
        @endif
    @endforeach
@endif

@endsection
