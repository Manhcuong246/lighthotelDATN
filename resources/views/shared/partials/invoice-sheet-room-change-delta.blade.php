{{--
  Trên phiếu thanh toán / biên lai: hiển thị chênh đổi phòng rõ nghĩa, không dùng đỏ/xanh
  (tránh lệch với các dòng tiền phòng đen).
  $delta = price_difference (giá mới − giá cũ): dương → khách trả thêm; âm → được hoàn.
--}}
@php
    $d = (float) ($delta ?? 0);
    $fmtAbs = number_format(abs($d), 0, ',', '.');
@endphp
@if(abs($d) < 0.009)
    <span class="text-muted">0 ₫</span>
@elseif($d > 0)
    <span class="text-dark fw-semibold">Trả thêm {{ $fmtAbs }} ₫</span>
    <span class="d-block small text-muted fw-normal">Đã gộp trong tiền phòng.</span>
@else
    <span class="text-dark fw-semibold">Được hoàn {{ $fmtAbs }} ₫</span>
    <span class="d-block small text-muted fw-normal">Đã gộp trong tiền phòng.</span>
@endif
