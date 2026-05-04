@extends('layouts.admin')

@section('title', 'Đơn đặt phòng')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <div>
            <h1 class="text-dark fw-bold mb-1">Đơn đặt phòng</h1>
            @php $qb = request()->except(['page', 'status']); @endphp
            <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                <a href="{{ route('admin.bookings.index', $qb) }}" class="btn btn-sm rounded-pill px-3 {{ ! request()->filled('status') ? 'btn-dark' : 'btn-outline-secondary' }}">Tất cả <span class="badge bg-white text-dark ms-1">{{ $counts['total'] ?? 0 }}</span></a>
                <a href="{{ route('admin.bookings.index', array_merge($qb, ['status' => 'checked_in'])) }}" class="btn btn-sm rounded-pill px-3 {{ request('status') === 'checked_in' ? 'btn-info' : 'btn-outline-info' }}">Đang lưu trú <span class="badge {{ request('status') === 'checked_in' ? 'bg-dark' : 'bg-info' }} ms-1">{{ $counts['checked_in'] ?? 0 }}</span></a>
                <a href="{{ route('admin.bookings.index', array_merge($qb, ['status' => 'confirmed'])) }}" class="btn btn-sm rounded-pill px-3 {{ request('status') === 'confirmed' ? 'btn-success' : 'btn-outline-success' }}">Đã xác nhận <span class="badge {{ request('status') === 'confirmed' ? 'bg-white text-success' : 'bg-success' }} ms-1">{{ $counts['confirmed'] ?? 0 }}</span></a>
            </div>
        </div>
        <div class="admin-action-row">
            @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
                <a href="{{ route('admin.bookings.create-multi') }}" class="btn btn-primary btn-sm px-3 rounded-3">
                    <i class="bi bi-plus-lg me-1"></i>Tạo đơn
                </a>
            @endif
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 rounded-3 shadow-sm" style="border: 1px solid rgba(0,0,0,.06) !important;">
        <div class="card-header py-3 px-4 bg-white border-bottom rounded-top-3">
            <form action="{{ route('admin.bookings.index') }}" method="GET" class="row g-2 g-xl-3 align-items-end flex-lg-nowrap">
                <div class="col-12 col-lg-auto flex-lg-grow-0">
                    <label class="form-label mb-1" for="q">Tìm kiếm</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Khách, phòng, mã đơn, mã GD…" style="min-width: 200px;">
                </div>
                <div class="col-12 col-lg-auto flex-lg-grow-0">
                    <label class="form-label mb-1" for="check_in_from">Nhận phòng</label>
                    <div class="d-flex gap-1 align-items-center">
                        <input type="date" name="check_in_from" value="{{ request('check_in_from') }}" class="form-control form-control-sm" aria-label="Nhận từ">
                        <span class="text-muted small">→</span>
                        <input type="date" name="check_in_to" value="{{ request('check_in_to') }}" class="form-control form-control-sm" aria-label="Nhận đến">
                    </div>
                </div>
                <div class="col-12 col-lg-auto flex-lg-grow-0">
                    <label class="form-label mb-1" for="check_out_from">Trả phòng</label>
                    <div class="d-flex gap-1 align-items-center">
                        <input type="date" name="check_out_from" value="{{ request('check_out_from') }}" class="form-control form-control-sm" aria-label="Trả từ">
                        <span class="text-muted small">→</span>
                        <input type="date" name="check_out_to" value="{{ request('check_out_to') }}" class="form-control form-control-sm" aria-label="Trả đến">
                    </div>
                </div>
                <div class="col-12 col-lg-auto flex-lg-grow-0">
                    <label class="form-label mb-1" for="booking-status-filter">Tiến trình đơn</label>
                    <select name="status" id="booking-status-filter" class="form-select form-select-sm" style="min-width: 11rem;">
                        @php $st = request('status'); @endphp
                        <option value="" {{ $st === null || $st === '' ? 'selected' : '' }}>Mọi trạng thái</option>
                        <option value="confirmed" {{ $st === 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                        <option value="checked_in" {{ $st === 'checked_in' ? 'selected' : '' }}>Đang lưu trú</option>
                        <option value="completed" {{ $st === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    </select>
                </div>
                <div class="col-12 col-lg-auto ms-lg-auto">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-3 px-3" title="Lọc"><i class="bi bi-funnel me-1"></i>Lọc</button>
                        @if(request()->hasAny(['q','status','check_in_from','check_in_to','check_out_from','check_out_to']))
                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-sm rounded-3" title="Xóa bộ lọc">Xóa lọc</a>
                        @endif
                    </div>
                </div>
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
                            <th style="width: 52px;" class="text-center"><span class="cursor-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Trạng thái lưu trú của đơn.">Lưu trú</span></th>
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
                                        <small class="text-muted">{{ $booking->rooms->map(fn ($r) => $r->room_number ?: $r->name)->filter()->implode(', ') }}</small>
                                    @elseif($booking->rooms->count() === 1)
                                        @php $r = $booking->rooms->first(); @endphp
                                        <span class="badge bg-primary">{{ $r->room_number ?: $r->name ?: '—' }}</span>
                                    @elseif($booking->bookingRooms->isNotEmpty())
                                        @php
                                            $lbls = $booking->bookingRooms->map(function ($br) {
                                                if ($br->room) {
                                                    return $br->room->room_number ?: $br->room->name ?: $br->room->displayLabel();
                                                }

                                                return $br->roomType?->name
                                                    ? ('Chưa gán · '.$br->roomType->name)
                                                    : 'Chưa gán phòng';
                                            })->unique()->values();
                                        @endphp
                                        <span class="badge {{ $lbls->every(fn ($t) => str_starts_with((string) $t, 'Chưa gán')) ? 'bg-warning text-dark' : 'bg-primary' }}">{{ $lbls->implode(', ') }}</span>
                                    @elseif($booking->room)
                                        <span class="badge bg-primary">{{ $booking->room->room_number ?: $booking->room->name ?: '—' }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $booking->check_in?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $booking->check_out?->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-center">
                                    @switch($booking->adminStayPhase())
                                        @case('cancelled')
                                            <span class="text-danger cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Đã hủy đơn" title="Đơn đã hủy."><i class="bi bi-slash-circle fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('pending_payment')
                                            <span class="text-muted cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Chờ thanh toán" title="Chờ thanh toán hoặc xác nhận."><i class="bi bi-hourglass-split fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('not_checked_in')
                                            <span class="text-warning cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Chưa check-in" title="Đã xác nhận, chưa check-in."><i class="bi bi-door-closed fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('stay_overdue')
                                            <span class="text-danger cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Quá hạn check-in" title="Quá hạn check-in, cần xử lý."><i class="bi bi-calendar-x fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('checked_in')
                                            <span class="text-primary cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Đã check-in" title="Đã check-in, chưa check-out."><i class="bi bi-house-check fs-5" aria-hidden="true"></i></span>
                                            @break
                                        @case('checked_out')
                                            <span class="text-success cursor-help d-inline-flex" role="img" data-bs-toggle="tooltip" data-bs-placement="left" aria-label="Đã check-out" title="Đã check-out."><i class="bi bi-box-arrow-right fs-5" aria-hidden="true"></i></span>
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
                                        @include('admin.bookings.partials.payment-method-subbadge')
                                    @elseif($ledgerPs === 'partial')
                                        <span class="badge bg-info text-dark"><i class="bi bi-piggy-bank me-1"></i>Đặt cọc / một phần</span>
                                        @include('admin.bookings.partials.payment-method-subbadge')
                                    @elseif($paySt === 'failed')
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>TT thất bại</span>
                                    @elseif($paySt === 'paid' && $ledgerPs === 'pending')
                                        <span class="badge bg-info text-dark"><i class="bi bi-bank me-1"></i>Cổng đã thanh toán</span>
                                        <br><small class="text-muted" style="font-size:.65rem">Chưa ghi nhận sổ</small>
                                        @include('admin.bookings.partials.payment-method-subbadge')
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Chờ thanh toán</span>
                                        @include('admin.bookings.partials.payment-method-subbadge')
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 align-items-center">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary btn-admin-icon" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-admin-icon dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" title="Thêm thao tác">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="min-width: 11.5rem; z-index: 1056;">
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
                                                    <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" onsubmit="return confirm('Xóa đơn #{{ $booking->id }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i> Xóa
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
                                    Chưa có đơn nào phù hợp.
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
                        <strong>Chỉ hủy đơn chưa thanh toán.</strong> Đơn đã thanh toán vui lòng xử lý ở mục Hoàn tiền.
                    </div>

                    <div class="mb-3">
                        <label for="cancelReason{{ $booking->id }}" class="form-label">
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
                            @elseif($booking->rooms->count() === 1)
                                {{ $booking->rooms->first()->room_number ?: $booking->rooms->first()->name ?: '—' }}
                            @elseif($booking->bookingRooms->isNotEmpty())
                                {{ $booking->bookingRooms->map(function ($br) {
                                    if ($br->room) {
                                        return $br->room->room_number ?: $br->room->name ?: $br->room->displayLabel();
                                    }
                                    return $br->roomType?->name ? ('Chưa gán · '.$br->roomType->name) : 'Chưa gán phòng';
                                })->unique()->implode(', ') }}
                            @else
                                {{ $booking->room?->room_number ?: $booking->room?->name ?: '—' }}
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
    @push('scripts')
        @include('admin.bookings._checkin_modal_scripts')
    @endpush
@endif

@endsection
