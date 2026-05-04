{{--
  Chi tiết lần đổi phòng — layout thống nhất với màn create (5 | 7).
  Biến: $history (RoomChangeHistory), $canRevert, $bookingHistories, $routePrefix ('admin'|'staff')
--}}
@php
    $booking = $history->booking;
    $guestCount = $booking ? $booking->guests()->count() : 0;
    $totalDiff = (float) ($history->price_difference ?? 0);
    $perNightDiff = (float) ($history->new_price_per_night ?? 0) - (float) ($history->old_price_per_night ?? 0);
@endphp
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-4 px-0">
                    <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($routePrefix . '.room-changes.index') }}">Đổi phòng</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chi tiết #{{ $history->id }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold text-primary mb-1"><i class="bi bi-arrow-left-right me-2"></i>Chi tiết đổi phòng #{{ $history->id }}</h1>
            <div class="text-muted small">{{ $history->changed_at->format('d/m/Y H:i') }}</div>
        </div>
        <a href="{{ route($routePrefix . '.room-changes.index') }}" class="btn btn-outline-secondary btn-sm rounded-2">
            <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
        </a>
    </div>

    <div class="row">
        {{-- Cột trái: như bước 1–2 create --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary fw-bold">1. Thông tin đơn &amp; phòng sau đổi</h5>
                </div>
                <div class="card-body">
                    @if($booking)
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Mã Booking:</div>
                            <div class="col-sm-7 fw-bold">
                                @if($routePrefix === 'admin')
                                    <a href="{{ route('admin.bookings.show', $booking->id) }}">#{{ $booking->id }}</a>
                                @else
                                    #{{ $booking->id }}
                                @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Khách hàng:</div>
                            <div class="col-sm-7 fw-bold">{{ $booking->user->full_name ?? 'Khách lẻ' }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Số điện thoại:</div>
                            <div class="col-sm-7">{{ $booking->user->phone ?? '—' }}</div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Thời gian ở:</div>
                            <div class="col-sm-7">
                                {{ $booking->check_in->format('d/m/Y') }} — {{ $booking->check_out->format('d/m/Y') }}
                                <br><span class="badge bg-info-soft text-info mt-1">{{ $booking->nights }} đêm trên đơn</span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Đêm còn lại (lúc đổi):</div>
                            <div class="col-sm-7 fw-bold text-danger">{{ (int) ($history->remaining_nights ?? 0) }} đêm</div>
                        </div>
                        <div class="row mb-3 align-items-center">
                            <div class="col-sm-5 text-muted">Đang ở sau đổi:</div>
                            <div class="col-sm-7 fw-bold">
                                @if($history->toRoom)
                                    {{ $history->toRoom->displayLabel() }}
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">Giá áp dụng / đêm (phòng mới):</div>
                            <div class="col-sm-7">{{ number_format((float) ($history->new_price_per_night ?? 0), 0, ',', '.') }} ₫</div>
                        </div>
                        <div class="row mb-0">
                            <div class="col-sm-5 text-muted">Số khách:</div>
                            <div class="col-sm-7"><span class="badge bg-secondary">{{ $guestCount }} người</span></div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Không tải được đơn (đã xóa?).</p>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary fw-bold">2. Lý do đổi phòng</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $history->reason ?: '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Cột phải: như bước 3 create — tóm tất & thao tác --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary fw-bold">3. Kết quả đổi phòng</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="small text-muted text-uppercase fw-bold mb-2">Từ phòng</div>
                                <div class="fw-bold fs-5">{{ $history->fromRoom ? $history->fromRoom->displayLabel() : '—' }}</div>
                                @if($history->old_price_per_night)
                                    <div class="mt-2 badge bg-secondary">{{ number_format((float) $history->old_price_per_night, 0, ',', '.') }} ₫ / đêm</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <div class="text-center px-1">
                                <i class="bi bi-arrow-right-circle-fill text-primary fs-2"></i>
                                <div class="mt-2">
                                    @if($totalDiff > 0)
                                        <span class="badge bg-danger" title="Khách phải trả thêm">-{{ number_format(abs($totalDiff), 0, ',', '.') }} ₫</span>
                                    @elseif($totalDiff < 0)
                                        <span class="badge bg-success" title="Khách được hoàn / nhận lại">+{{ number_format(abs($totalDiff), 0, ',', '.') }} ₫</span>
                                    @else
                                        <span class="badge bg-secondary">Không chênh tổng</span>
                                    @endif
                                </div>
                                <div class="small text-muted mt-1">Trên toàn bộ đêm tính phí</div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="border border-primary rounded-3 p-3 h-100" style="background: rgba(13, 110, 253, 0.04);">
                                <div class="small text-primary text-uppercase fw-bold mb-2">Sang phòng</div>
                                <div class="fw-bold fs-5 text-primary">{{ $history->toRoom ? $history->toRoom->displayLabel() : '—' }}</div>
                                @if($history->new_price_per_night)
                                    <div class="mt-2 badge bg-primary">{{ number_format((float) $history->new_price_per_night, 0, ',', '.') }} ₫ / đêm</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="text-muted">Loại đổi:</span>
                        <span class="badge {{ $history->change_type_badge }}">{{ $history->change_type_label }}</span>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted w-50">Giá cũ / đêm</td>
                                    <td class="fw-bold">{{ number_format((float) ($history->old_price_per_night ?? 0), 0, ',', '.') }} ₫</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Giá mới / đêm</td>
                                    <td class="fw-bold">{{ number_format((float) ($history->new_price_per_night ?? 0), 0, ',', '.') }} ₫</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Chênh / đêm</td>
                                    <td>
                                        @include('shared.partials.room-change-price-diff', [
                                            'diff' => $perNightDiff,
                                            'class' => 'fw-bold',
                                        ])
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tổng chênh lệch đã áp dụng</td>
                                    <td>
                                        @include('shared.partials.room-change-price-diff', [
                                            'diff' => $totalDiff,
                                            'class' => 'fw-bold',
                                        ])
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Trạng thái phòng cũ trước đổi</td>
                                    <td><span class="badge bg-light text-dark">{{ $history->old_room_status_label }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @php
                        $changeType = $history->change_type ?? 'same_grade';
                        $upgradePolicy = config('room_changes.upgrade_policy', 'add_to_folio');
                        $downgradePolicy = config('room_changes.downgrade_policy', 'credit');
                    @endphp
                    @if($changeType === 'upgrade' || $changeType === 'downgrade')
                        <div class="alert alert-light border small mb-4">
                            @if($changeType === 'upgrade')
                                <strong>Nâng hạng:</strong>
                                @if($upgradePolicy === 'pay_now') Khách thanh toán bổ sung ngay.
                                @elseif($upgradePolicy === 'add_to_folio') Phí ghi vào hóa đơn / folio.
                                @elseif($upgradePolicy === 'auto_confirm') Tự động xác nhận (admin).
                                @endif
                            @else
                                <strong>Hạ hạng:</strong>
                                @if($downgradePolicy === 'refund') Hoàn tiền ngay.
                                @elseif($downgradePolicy === 'credit') Ghi credit folio.
                                @elseif($downgradePolicy === 'none') Không hoàn tiền.
                                @endif
                            @endif
                        </div>
                    @endif

                    <div class="border rounded-3 p-3 mb-4 bg-white">
                        <div class="small text-muted text-uppercase fw-bold mb-2">Người thực hiện</div>
                        @if($history->changedBy)
                            <div class="fw-semibold">{{ $history->changedBy->full_name }}</div>
                            <div class="small text-muted">{{ $history->changedBy->email }}</div>
                        @else
                            <span class="text-muted">Hệ thống</span>
                        @endif
                    </div>

                    @if($history->damageReport)
                        <div class="alert alert-warning small mb-4">
                            <strong>Báo cáo hỏng hóc:</strong> #{{ $history->damageReport->id }}
                            — {{ \Illuminate\Support\Str::limit($history->damageReport->description ?? '', 120) }}
                            <a href="{{ route($routePrefix . '.damage-reports.show', $history->damageReport) }}" class="alert-link ms-1">Xem</a>
                        </div>
                    @endif

                    @if($bookingHistories->isNotEmpty())
                        <div class="mb-4">
                            <h6 class="fw-bold small text-uppercase text-muted mb-2">Lần đổi khác cùng đơn</h6>
                            <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                                @foreach($bookingHistories as $bh)
                                    <a href="{{ route($routePrefix . '.room-changes.show', $bh->id) }}" class="list-group-item list-group-item-action py-2 small">
                                        #{{ $bh->id }} · {{ $bh->changed_at->format('d/m/Y H:i') }}
                                        <span class="text-muted ms-1">{{ $bh->fromRoom?->displayLabel() ?? '—' }} → {{ $bh->toRoom?->displayLabel() ?? '—' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($canRevert)
                        <div class="rounded-3 border border-warning p-4 bg-warning bg-opacity-10">
                            <p class="small mb-3">
                                <strong>Hoàn tác đổi phòng</strong> — đưa khách về <strong>{{ $history->fromRoom?->displayLabel() ?? 'phòng cũ' }}</strong>
                                nếu phòng đó vẫn <strong>trống</strong>. Chỉ một nút xác nhận (giống flow xác nhận bước 3 khi đổi phòng).
                            </p>
                            <button type="button" class="btn btn-warning px-4" data-bs-toggle="modal" data-bs-target="#revertModal">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Hoàn tác đổi phòng
                            </button>
                        </div>
                    @else
                        <div class="text-muted small border rounded-3 p-3 text-center">
                            <i class="bi bi-lock me-1"></i>Không thể hoàn tác (phòng cũ không trống hoặc điều kiện không đủ).
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($canRevert)
<div class="modal fade" id="revertModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-25 border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-counterclockwise me-2"></i>Xác nhận hoàn tác</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route($routePrefix . '.room-changes.revert', $history->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="small mb-3">
                        Khách sẽ được chuyển từ <strong>{{ $history->toRoom?->displayLabel() ?? '—' }}</strong>
                        về <strong>{{ $history->fromRoom?->displayLabel() ?? '—' }}</strong>.
                    </p>
                    <label class="form-label small">Ghi chú hoàn tác (tuỳ chọn)</label>
                    <textarea name="reason" class="form-control form-control-sm" rows="2" placeholder="Ví dụ: Khách đổi lại ý, nhập sai phòng…"></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-counterclockwise me-1"></i>Hoàn tác</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
</style>
@endpush
