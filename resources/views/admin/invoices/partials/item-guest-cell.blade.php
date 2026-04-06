@php
    $isRoom = ($item->item_type ?? '') === 'room';
    $hasBreakdown = $isRoom && (
        $item->guest_adults !== null
        || $item->guest_children_6_11 !== null
        || $item->guest_children_0_5 !== null
    );
@endphp
@if($hasBreakdown)
    <div class="lh-sm">NL <strong>{{ $item->guest_adults ?? '—' }}</strong></div>
    <div class="lh-sm text-muted">6–11t <strong>{{ $item->guest_children_6_11 ?? '—' }}</strong></div>
    <div class="lh-sm text-muted">0–5t <strong>{{ $item->guest_children_0_5 ?? '—' }}</strong></div>
@else
    <span class="text-muted">—</span>
@endif
