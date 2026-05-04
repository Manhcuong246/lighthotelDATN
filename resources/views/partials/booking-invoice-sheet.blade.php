{{-- Biến: $booking, $hotel, $roomLines, $discountAmount, $invoiceNo; tuỳ chọn: roomSubtotal, roomChangeLines, roomChangeDeltaTotal, extrasSubtotal, totalPaidFromPayments, balanceDue, invoiceRemaining --}}
@php
    $roomSubtotal = $roomSubtotal ?? null;
    $roomChangeLines = $roomChangeLines ?? [];
    $roomChangeDeltaTotal = (float) ($roomChangeDeltaTotal ?? 0);
    $extrasSubtotal = $extrasSubtotal ?? null;
    $totalPaidFromPayments = $totalPaidFromPayments ?? (float) ($booking->payments ?? collect())->where('status', 'paid')->sum('amount');
    $balanceDue = $balanceDue ?? max(0, (float) $booking->total_price - $totalPaidFromPayments);
    $invoiceRemaining = $invoiceRemaining ?? null;
    $brandName = 'Light Hotel';
    $brandEmail = 'info@lighthotel.vn';
    $displayPhone = optional($hotel)->phone;
    $displayAddress = optional($hotel)->address;
@endphp
<div class="invoice-sheet border p-4 p-md-5">
    <div class="row align-items-start mb-4 pb-3 border-bottom">
        <div class="col-md-7">
            <h1 class="h4 fw-bold mb-1">{{ $brandName }}</h1>
            @if($displayAddress)
                <p class="mb-1 small text-muted">{{ $displayAddress }}</p>
            @endif
            <p class="mb-0 small text-muted">
                @if($displayPhone)<span>ĐT: {{ $displayPhone }}</span>@endif
                <span class="{{ $displayPhone ? 'ms-2' : '' }}">Email: {{ $brandEmail }}</span>
            </p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <p class="mb-1 text-uppercase small text-muted fw-semibold">Hóa đơn thanh toán</p>
            <p class="mb-1 fw-bold fs-5">{{ $invoiceNo }}</p>
            @if($booking->invoice)
                <p class="mb-0 small text-muted">Số hóa đơn nội bộ: {{ $booking->invoice->invoice_number }}</p>
            @endif
            <p class="mb-0 small text-muted">Mã đặt phòng: #{{ $booking->id }}</p>
            <p class="mb-0 small text-muted">Ngày lập: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <h2 class="h6 text-muted text-uppercase fw-semibold mb-2">Khách hàng</h2>
            <p class="mb-1 fw-semibold">{{ $booking->user?->full_name ?? '—' }}</p>
            <p class="mb-0 small text-muted">{{ $booking->user?->email ?? '—' }}</p>
            @if($booking->user?->phone)
                <p class="mb-0 small text-muted">SĐT: {{ $booking->user->phone }}</p>
            @endif
        </div>
        <div class="col-md-6">
            <h2 class="h6 text-muted text-uppercase fw-semibold mb-2">Thời gian lưu trú</h2>
            <p class="mb-1 small">Nhận phòng (dự kiến): <strong>{{ $booking->check_in?->format('d/m/Y') ?? '—' }}</strong></p>
            <p class="mb-1 small">Trả phòng (dự kiến): <strong>{{ $booking->check_out?->format('d/m/Y') ?? '—' }}</strong></p>
            @if($booking->actual_check_in)
                <p class="mb-1 small">Check-in thực tế: <strong>{{ $booking->actual_check_in->format('d/m/Y H:i') }}</strong></p>
            @endif
            @if($booking->actual_check_out)
                <p class="mb-0 small">Check-out thực tế: <strong>{{ $booking->actual_check_out->format('d/m/Y H:i') }}</strong></p>
            @endif
        </div>
    </div>

    <h2 class="h6 text-muted text-uppercase fw-semibold mb-2">Chi tiết phòng</h2>
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Phòng / loại</th>
                    <th class="text-center">Số đêm</th>
                    <th class="text-end">Đơn giá/đêm</th>
                    <th class="text-end pe-3">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roomLines as $line)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $line['label'] }}</div>
                            @if(!empty($line['detail']))
                                <div class="small text-muted">{{ $line['detail'] }}</div>
                            @endif
                            @if(!empty($line['quantity_note']))
                                <div class="small text-muted mt-1">{{ $line['quantity_note'] }}</div>
                            @endif
                            @if(!empty($line['nights_calendar']) && isset($line['nights']) && (int) $line['nights_calendar'] !== (int) $line['nights'])
                                <div class="small text-muted mt-1">Đêm trên lịch đặt: {{ (int) $line['nights_calendar'] }} (gia hạn nằm ở mục phụ phí)</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $line['nights'] !== null ? $line['nights'] : '—' }}</td>
                        <td class="text-end text-muted">
                            @if($line['unit_price'] !== null)
                                {{ number_format($line['unit_price'], 0, ',', '.') }} ₫
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end pe-3 fw-semibold text-dark">{{ number_format($line['line_total'], 0, ',', '.') }} ₫</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Không có dòng phòng.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($booking->bookingServices->isNotEmpty())
        <h2 class="h6 text-muted text-uppercase fw-semibold mb-2">Dịch vụ kèm đặt phòng</h2>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Dịch vụ</th>
                        <th class="text-end">Đơn giá</th>
                        <th class="text-center">SL</th>
                        <th class="text-end pe-3">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($booking->bookingServices as $bs)
                        @php $line = (float) $bs->price * (int) $bs->quantity; @endphp
                        <tr>
                            <td class="ps-3">{{ $bs->service?->name ?? 'Dịch vụ #' . $bs->service_id }}</td>
                            <td class="text-end text-muted">{{ number_format((float) $bs->price, 0, ',', '.') }} ₫</td>
                            <td class="text-center">{{ $bs->quantity }}</td>
                            <td class="text-end pe-3 fw-semibold text-dark">{{ number_format($line, 0, ',', '.') }} ₫</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($booking->surcharges->isNotEmpty())
        <h2 class="h6 text-muted text-uppercase fw-semibold mb-2">Phụ phí / bồi thường (không cố định)</h2>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Mô tả</th>
                        <th class="text-center">SL</th>
                        <th class="text-end pe-3">Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($booking->surcharges as $s)
                        <tr>
                            <td class="ps-3">
                                <div>{{ $s->reason }}</div>
                                @if($s->service)
                                    <div class="small text-muted">(Dữ liệu cũ — danh mục: {{ $s->service->name }})</div>
                                @endif
                            </td>
                            <td class="text-center">{{ (int) ($s->quantity ?? 1) }}</td>
                            <td class="text-end pe-3 fw-semibold text-dark">{{ number_format((float) $s->amount, 0, ',', '.') }} ₫</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($roomChangeLines))
        <h2 class="h6 text-muted text-uppercase fw-semibold mb-2">Lịch sử đổi phòng (điều chỉnh tiền)</h2>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Thời gian</th>
                        <th>Đổi phòng</th>
                        <th>Lý do</th>
                        <th class="text-end pe-3">Ảnh hưởng tiền (đối chiếu)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roomChangeLines as $line)
                        @php $delta = (float) ($line['delta'] ?? 0); @endphp
                        <tr>
                            <td class="ps-3 text-nowrap">
                                {{ optional($line['at'] ?? null)?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td>
                                <div>{{ $line['from_room'] ?? '—' }} → {{ $line['to_room'] ?? '—' }}</div>
                                <div class="small text-muted">Thực hiện: {{ $line['changed_by'] ?? 'Hệ thống' }}</div>
                            </td>
                            <td>{{ $line['reason'] ?: '—' }}</td>
                            <td class="text-end pe-3 text-nowrap">
                                @include('shared.partials.invoice-sheet-room-change-delta', ['delta' => $delta])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-semibold">Tổng điều chỉnh do đổi phòng</td>
                        <td class="text-end pe-3 text-nowrap">
                            @include('shared.partials.invoice-sheet-room-change-delta', ['delta' => $roomChangeDeltaTotal])
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <div class="row align-items-center justify-content-center g-3 g-md-4">
        <div class="col-md-5 mb-2 mb-md-0 d-flex justify-content-center">
            <div class="text-center">
                <img src="{{ asset('images/fake-hotel-qr.png') }}" alt="QR khách sạn (trang trí)" width="220" height="220" class="img-fluid border rounded p-1 bg-white">
                <p class="mb-0 mt-2 small text-muted">Quét để xem thông tin khách sạn</p>
            </div>
        </div>
        <div class="col-md-7 ps-md-4" style="border-left: 1px solid #e9ecef;">
            <table class="table table-sm mb-0">
                @if($discountAmount > 0)
                    <tr>
                        <td class="text-muted border-0">Giảm giá
                            @if($booking->coupon_code)
                                <span class="text-muted">(mã {{ $booking->coupon_code }})</span>
                            @endif
                        </td>
                        <td class="text-end border-0"><span class="text-success fw-semibold">− {{ number_format((float) $discountAmount, 0, ',', '.') }} ₫</span></td>
                    </tr>
                @endif
                @if($roomSubtotal !== null && $roomSubtotal > 0)
                    <tr>
                        <td class="text-muted border-0 small">
                            Tiền lưu trú (phòng)
                            @if(abs($roomChangeDeltaTotal) > 0.009)
                                <span class="d-block small text-muted lh-sm mt-1">Đã gồm điều chỉnh sau đổi phòng — xem bảng «Lịch sử đổi phòng».</span>
                            @endif
                        </td>
                        <td class="text-end border-0 small fw-semibold text-dark">{{ number_format((float) $roomSubtotal, 0, ',', '.') }} ₫</td>
                    </tr>
                @endif
                @if($extrasSubtotal !== null && abs($extrasSubtotal) > 0.009)
                    <tr>
                        <td class="text-muted border-0 small">Dịch vụ &amp; phụ thu (sau giảm giá đặt phòng)</td>
                        <td class="text-end border-0 small fw-semibold text-dark">{{ number_format((float) $extrasSubtotal, 0, ',', '.') }} ₫</td>
                    </tr>
                @endif
                <tr>
                    <td class="border-0 pt-2 fs-5 fw-bold">Tổng giá trị đơn</td>
                    <td class="border-0 pt-2 text-end fs-5 fw-bold text-dark">{{ number_format((float) $booking->total_price, 0, ',', '.') }} ₫</td>
                </tr>
                <tr>
                    <td class="text-muted border-0">Đã thanh toán</td>
                    <td class="text-end border-0 fw-semibold">
                        @include('shared.partials.money-paid', ['amount' => $totalPaidFromPayments, 'class' => 'fw-semibold'])
                    </td>
                </tr>
                <tr class="border-top">
                    <td class="border-0 pt-2 fs-6 fw-bold">Số tiền cần thanh toán còn lại</td>
                    <td class="border-0 pt-2 text-end fs-6 fw-bold">
                        @include('shared.partials.money-debt-due', ['amount' => $balanceDue, 'class' => 'fs-6'])
                    </td>
                </tr>
                @if($invoiceRemaining !== null && $booking->invoice)
                    <tr>
                        <td class="text-muted border-0 small">Theo hóa đơn nội bộ {{ $booking->invoice->invoice_number }} (còn lại)</td>
                        <td class="text-end border-0 small fw-semibold">
                            @if($invoiceRemaining > 0.009)
                                @include('shared.partials.money-debt-due', ['amount' => $invoiceRemaining, 'class' => 'fw-semibold small'])
                            @else
                                <span class="text-muted">{{ number_format((float) $invoiceRemaining, 0, ',', '.') }} ₫</span>
                            @endif
                        </td>
                    </tr>
                @endif
            </table>
            <p class="small text-muted mb-0 mt-2 pt-1 border-top border-light-subtle">
                <strong>Đọc nhanh:</strong> số <span class="text-dark fw-semibold">đen</span> là các phần cộng vào tổng đơn.
                <span class="text-success fw-semibold">Xanh</span> — đã thanh toán hoặc giảm giá.
                <span class="text-danger fw-semibold">Đỏ</span> — số tiền còn phải thu.
            </p>
        </div>
    </div>

    <hr class="my-4">

    <h2 class="h6 text-muted text-uppercase fw-semibold mb-2">Thanh toán</h2>
    @php $pay = $booking->latestPayment; @endphp
    @if($pay)
        @if($pay->transaction_id)
            <p class="mb-1 small text-muted">Mã giao dịch: {{ $pay->transaction_id }}</p>
        @endif
        @if($pay->paid_at)
            <p class="mb-0 small text-muted">Thời điểm thanh toán: {{ $pay->paid_at->format('d/m/Y H:i') }}</p>
        @endif
    @else
        <p class="mb-0 small text-muted">Không có bản ghi thanh toán chi tiết.</p>
    @endif

    <p class="mt-4 mb-0 small text-muted text-center">
        Cảm ơn quý khách đã lưu trú. Hóa đơn có giá trị tham khảo; mọi thắc mắc xin liên hệ lễ tân.
    </p>
</div>
