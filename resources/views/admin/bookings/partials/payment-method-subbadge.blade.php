@php
    $labels = config('booking.admin_payment_method_labels', []);
    $pm = $method ?? null;
@endphp
@if($pm && isset($labels[$pm]))
    <br><span class="badge bg-{{ $labels[$pm]['color'] }} mt-1">{{ $labels[$pm]['text'] }}</span>
@endif
