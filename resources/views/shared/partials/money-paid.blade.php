{{-- Đã thanh toán / đã cọc (số dương, không dấu ±). --}}
@include('shared.partials.lh-money-styles')
@php
    $a = (float) ($amount ?? 0);
    $extraClass = trim((string) ($class ?? ''));
    $fmt = number_format(max(0, $a), 0, ',', '.');
@endphp
<span class="lh-money-paid {{ $extraClass }}">{{ $fmt }} ₫</span>
