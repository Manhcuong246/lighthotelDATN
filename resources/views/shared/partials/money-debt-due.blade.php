{{-- Số tiền còn phải thu / còn nợ (luôn ≥ 0): đỏ khi > 0, xanh khi đã hết nợ. --}}
@include('shared.partials.lh-money-styles')
@php
    $a = (float) ($amount ?? 0);
    $extraClass = trim((string) ($class ?? ''));
    $fmt = number_format(max(0, $a), 0, ',', '.');
@endphp
@if($a > 0.009)
    <span class="lh-money-debt-due {{ $extraClass }}" title="Còn phải thu">{{ $fmt }} ₫</span>
@else
    <span class="lh-money-paid {{ $extraClass }}" title="Không còn nợ">0 ₫</span>
@endif
