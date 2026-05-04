{{--
  Chênh giá đổi phòng — đồng bộ với money-customer-flow (DB: giá mới − giá cũ).
  Truyền: $diff hoặc $amount, $class, $zeroAsDash.
--}}
@include('shared.partials.money-customer-flow', [
    'amount' => $diff ?? $amount ?? 0,
    'class' => $class ?? '',
    'zeroAsDash' => ! empty($zeroAsDash),
])
