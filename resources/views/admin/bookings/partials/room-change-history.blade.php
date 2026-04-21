{{--
    Component hiển thị lịch sử đổi phòng
    Sử dụng: @include('admin.bookings.partials.room-change-history', ['booking' => $booking])
--}}

@php
    $changeHistories = \App\Models\RoomChangeHistory::with(['fromRoom', 'toRoom', 'changedBy'])
        ->where('booking_id', $booking->id)
        ->orderByDesc('changed_at')
        ->get();
@endphp

@if($changeHistories->count() > 0)
<div class="card shadow-sm mb-4 border-info">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>Lịch sử đổi phòng
        </h5>
        <span class="badge bg-white text-info">{{ $changeHistories->count() }} lần đổi</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Thởi gian</th>
                        <th>Từ phòng</th>
                        <th>→</th>
                        <th>Đến phòng</th>
                        <th>Chênh lệch giá</th>
                        <th>Lý do</th>
                        <th>Ngườii thực hiện</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($changeHistories as $history)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $history->changed_at->format('d/m/Y') }}</div>
                            <small class="text-muted">{{ $history->changed_at->format('H:i') }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                {{ $history->fromRoom?->name ?? 'N/A' }}
                            </span>
                            @if($history->fromRoom?->roomType)
                                <br><small class="text-muted">{{ $history->fromRoom->roomType->name }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <i class="bi bi-arrow-right-circle-fill text-info fs-5"></i>
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                {{ $history->toRoom?->name ?? 'N/A' }}
                            </span>
                            @if($history->toRoom?->roomType)
                                <br><small class="text-muted">{{ $history->toRoom->roomType->name }}</small>
                            @endif
                        </td>
                        <td>
                            @php
                                $priceDiff = $history->price_difference ?? 0;
                            @endphp
                            @if($priceDiff > 0)
                                <span class="badge bg-danger">+{{ number_format($priceDiff, 0, ',', '.') }} ₫</span>
                            @elseif($priceDiff < 0)
                                <span class="badge bg-success">{{ number_format($priceDiff, 0, ',', '.') }} ₫</span>
                            @else
                                <span class="badge bg-secondary">0 ₫</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $history->reason }}">
                                {{ Str::limit($history->reason, 50) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-person-circle me-1"></i>
                                {{ $history->changedBy?->full_name ?? 'System' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
