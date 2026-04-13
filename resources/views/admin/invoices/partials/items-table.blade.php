{{--
  Bảng chi tiết + tổng hợp (một bảng duy nhất).
  Biến: $invoice
  Tùy chọn: $tableClass, $theadClass, $withSummaryFooter (mặc định true), $showEmptyDetailSections (mặc định true)
--}}
@php
    $withSummaryFooter = $withSummaryFooter ?? true;
    $showEmptyDetailSections = $showEmptyDetailSections ?? true;

    $typeOrder = [
        'room' => 10,
        'service' => 20,
        'surcharge' => 30,
        'coupon' => 40,
        'adjustment' => 45,
        'discount' => 50,
        'fee' => 60,
    ];
    $sectionLabels = [
        'room' => 'Lưu trú',
        'service' => 'Dịch vụ đặt kèm',
        'surcharge' => 'Phụ thu & phát sinh',
        'coupon' => 'Giảm giá (đặt phòng)',
        'adjustment' => 'Điều chỉnh',
        'discount' => 'Giảm giá (trên hóa đơn)',
        'fee' => 'Thuế & phí',
    ];
    $sorted = $invoice->items->sortBy(static fn ($i) => $typeOrder[$i->item_type] ?? 99)->values();
    $byType = $sorted->groupBy('item_type');

    $detailTypesFirst = ['room', 'service', 'surcharge'];
    $tailTypes = ['coupon', 'adjustment', 'discount', 'fee'];

    $surchargesAmt = (float) ($invoice->surcharges_amount ?? 0);
    $bookingCoupon = (float) ($invoice->booking?->discount_amount ?? 0);
    $hasCouponLine = $invoice->items->contains(static fn ($i) => $i->item_type === 'coupon');
@endphp
<table class="{{ $tableClass ?? 'table table-bordered table-sm align-middle mb-0' }}">
    <thead class="{{ $theadClass ?? 'table-light' }}">
        <tr>
            <th style="width:38%">Mô tả</th>
            <th class="text-center" style="width:12%" title="Người lớn · Trẻ 6–11t · Trẻ 0–5t">Khách<br><span class="small fw-normal text-muted">NL / 6–11 / 0–5</span></th>
            <th class="text-end" style="width:8%">SL</th>
            <th class="text-end" style="width:19%">Đơn giá (₫)</th>
            <th class="text-end" style="width:23%">Thành tiền (₫)</th>
        </tr>
    </thead>
    <tbody>
        @if($sorted->isEmpty())
            <tr><td colspan="5" class="text-center text-muted py-4">Chưa có dòng chi tiết.</td></tr>
        @else
            @foreach($detailTypesFirst as $type)
                <tr class="table-secondary">
                    <td colspan="5" class="fw-bold small text-uppercase py-2">{{ $sectionLabels[$type] }}</td>
                </tr>
                @php $rows = $byType->get($type, collect()); @endphp
                @if($rows->isEmpty() && $showEmptyDetailSections)
                    <tr class="text-muted">
                        <td class="ps-3 fst-italic">Không có khoản trong mục này.</td>
                        <td class="text-center">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                    </tr>
                @else
                    @foreach($rows as $item)
                        <tr>
                            <td class="text-wrap small">{!! nl2br(e($item->description)) !!}</td>
                            <td class="text-center small align-top">@include('admin.invoices.partials.item-guest-cell', ['item' => $item])</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold @if((float) $item->total_price < 0) text-danger @endif">
                                {{ number_format((float) $item->total_price, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach

            @foreach($tailTypes as $type)
                @php $rows = $byType->get($type, collect()); @endphp
                @if($rows->isNotEmpty())
                    <tr class="table-secondary">
                        <td colspan="5" class="fw-bold small text-uppercase py-2">{{ $sectionLabels[$type] }}</td>
                    </tr>
                    @foreach($rows as $item)
                        <tr>
                            <td class="text-wrap small">{!! nl2br(e($item->description)) !!}</td>
                            <td class="text-center small align-top">@include('admin.invoices.partials.item-guest-cell', ['item' => $item])</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold @if((float) $item->total_price < 0) text-danger @endif">
                                {{ number_format((float) $item->total_price, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        @endif
    </tbody>
    @if($withSummaryFooter)
        <tfoot>
            <tr class="table-secondary border-top border-2">
                <td colspan="5" class="small fw-bold text-uppercase py-2">Tổng hợp</td>
            </tr>
            <tr class="table-light">
                <td colspan="4" class="fw-semibold text-end pe-2">Cộng tiền lưu trú</td>
                <td class="text-end fw-bold">{{ number_format((float) $invoice->room_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="table-light">
                <td colspan="4" class="fw-semibold text-end pe-2">Cộng dịch vụ đặt kèm</td>
                <td class="text-end fw-bold">{{ number_format((float) $invoice->services_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="table-light">
                <td colspan="4" class="fw-semibold text-end pe-2">Cộng phụ thu / phát sinh</td>
                <td class="text-end fw-bold">{{ number_format($surchargesAmt, 0, ',', '.') }}</td>
            </tr>
            @if($bookingCoupon > 0.009 && ! $hasCouponLine)
                <tr class="table-light">
                    <td colspan="4" class="fw-semibold text-end pe-2 text-danger">Giảm khi đặt phòng (trên đơn)</td>
                    <td class="text-end fw-bold text-danger">− {{ number_format($bookingCoupon, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="table-light">
                <td colspan="4" class="fw-semibold text-end pe-2 text-danger">Giảm giá trên hóa đơn</td>
                <td class="text-end fw-bold text-danger">− {{ number_format((float) $invoice->discount_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="table-light">
                <td colspan="4" class="fw-semibold text-end pe-2">Thuế &amp; phí</td>
                <td class="text-end fw-bold">{{ number_format((float) $invoice->tax_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="fw-bold border-top border-2 border-dark">
                <td colspan="4" class="text-end pe-2">Tổng thanh toán</td>
                <td class="text-end">{{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    @endif
</table>
