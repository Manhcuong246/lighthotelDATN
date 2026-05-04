{{--
  Dòng tiền có hướng (signed): dương = khách phải trả thêm / ghi nợ → đỏ −abs.
  Âm = khách được giảm phải trả / hoàn → xanh +abs.

  Truyền: $amount hoặc $diff (float có dấu). Tuỳ chọn: $class, $zeroAsDash (bool).
--}}
@include('shared.partials.lh-money-styles')
@php
    $signed = (float) ($amount ?? $diff ?? 0);
    $zeroAsDash = ! empty($zeroAsDash);
    $extraClass = trim((string) ($class ?? ''));
    $fmtAbs = number_format(abs($signed), 0, ',', '.');
@endphp
@if(abs($signed) < 0.009 && $zeroAsDash)
    <span class="text-muted {{ $extraClass }}">—</span>
@elseif(abs($signed) < 0.009)
    <span class="text-muted {{ $extraClass }}">0 ₫</span>
@elseif($signed > 0)
    <span class="lh-money-debit {{ $extraClass }}" title="Phải trả thêm / ghi nợ">− {{ $fmtAbs }} ₫</span>
@else
    <span class="lh-money-credit {{ $extraClass }}" title="Giảm phải trả / hoàn">+ {{ $fmtAbs }} ₫</span>
@endif
