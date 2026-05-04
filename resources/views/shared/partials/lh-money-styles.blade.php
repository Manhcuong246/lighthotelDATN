@once
@push('styles')
<style>
    /*
      Quy ước góc nhìn khách / công nợ:
      .lh-money-debit   — phải trả thêm, phụ thu, tăng nợ (hiển thị − số tuyệt đối)
      .lh-money-credit  — giảm phải trả, hoàn, giảm giá (hiển thị + số tuyệt đối)
      .lh-money-debt-due — số tiền còn phải thu (magnitude, không thêm dấu ±)
      .lh-money-paid    — đã thanh toán / đã cọc (magnitude)
    */
    .lh-money-debit { color: var(--bs-danger) !important; font-variant-numeric: tabular-nums; }
    .lh-money-credit { color: var(--bs-success) !important; font-variant-numeric: tabular-nums; }
    .lh-money-debt-due { color: var(--bs-danger) !important; font-weight: 700; font-variant-numeric: tabular-nums; }
    .lh-money-paid { color: var(--bs-success) !important; font-weight: 600; font-variant-numeric: tabular-nums; }
</style>
@endpush
@endonce
