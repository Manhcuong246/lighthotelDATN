{{--
    Lịch sử đổi phòng — @include('admin.bookings.partials.room-change-history', ['booking' => $booking])
--}}

@php
    $changeHistories = \App\Models\RoomChangeHistory::with(['fromRoom', 'toRoom', 'changedBy'])
        ->where('booking_id', $booking->id)
        ->orderByDesc('changed_at')
        ->get();
@endphp

@if($changeHistories->count() > 0)
<div class="abs-panel mb-4 border-secondary-subtle" style="border-left: 4px solid #64748b;">
    <div class="rounded-top px-3 py-2 border-bottom bg-light d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-color: rgba(148,163,184,0.35);">
        <h3 class="h6 fw-bold mb-0 text-secondary">
            <i class="bi bi-arrow-left-right me-2 text-muted"></i>Lịch sử đổi phòng
        </h3>
        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border">{{ $changeHistories->count() }} lần</span>
    </div>
    <div class="table-responsive rounded-bottom">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Thời gian</th>
                    <th>Từ phòng</th>
                    <th class="text-center" style="width:2rem;"></th>
                    <th>Đến phòng</th>
                    <th class="text-nowrap">Chênh giá</th>
                    <th>Lý do</th>
                    <th class="pe-3 text-nowrap">Thực hiện</th>
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
                        <span class="badge bg-light text-secondary border">
                            {{ $history->fromRoom?->name ?? 'N/A' }}
                        </span>
                        @if($history->fromRoom?->roomType)
                            <div class="small text-muted">{{ $history->fromRoom->roomType->name }}</div>
                        @endif
                    </td>
                    <td class="text-center text-muted">
                        <i class="bi bi-arrow-right"></i>
                    </td>
                    <td>
                        <span class="badge rounded-pill" style="background: rgba(15,118,110,0.12); border: 1px solid rgba(15,118,110,0.25);">
                            {{ $history->toRoom?->name ?? 'N/A' }}
                        </span>
                        @if($history->toRoom?->roomType)
                            <div class="small text-muted">{{ $history->toRoom->roomType->name }}</div>
                        @endif
                    </td>
                    <td>
                        @include('shared.partials.room-change-price-diff', [
                            'diff' => $history->price_difference ?? 0,
                            'class' => 'fw-semibold small text-nowrap',
                        ])
                    </td>
                    <td>
                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $history->reason }}">
                            {{ Str::limit($history->reason, 45) }}
                        </span>
                    </td>
                    <td class="pe-3 small text-muted">
                        <i class="bi bi-person me-1"></i>{{ $history->changedBy?->full_name ?? 'Hệ thống' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
