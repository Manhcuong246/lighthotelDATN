{{-- Biến: $booking, $hotel, $roomLines, $discountAmount, $invoiceNo --}}
<div class="invoice-sheet border p-4 p-md-5">
    <div class="row align-items-start mb-4 pb-3 border-bottom">
        <div class="col-md-7">
            <h1 class="h4 fw-bold mb-1">{{ optional($hotel)->name ?? 'Light Hotel' }}</h1>
            @if(optional($hotel)->address)
                <p class="mb-1 small text-muted">{{ $hotel->address }}</p>
            @endif
            <p class="mb-0 small text-muted">
                @if(optional($hotel)->phone)<span>ĐT: {{ $hotel->phone }}</span>@endif
                @if(optional($hotel)->email)<span class="ms-2">Email: {{ $hotel->email }}</span>@endif
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
                        </td>
                        <td class="text-center">{{ $line['nights'] !== null ? $line['nights'] : '—' }}</td>
                        <td class="text-end text-muted">
                            @if($line['unit_price'] !== null)
                                {{ number_format($line['unit_price'], 0, ',', '.') }} ₫
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end pe-3 fw-semibold">{{ number_format($line['line_total'], 0, ',', '.') }} ₫</td>
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
                            <td class="text-end pe-3 fw-semibold">{{ number_format($line, 0, ',', '.') }} ₫</td>
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
                            <td class="text-end pe-3 fw-semibold">{{ number_format((float) $s->amount, 0, ',', '.') }} ₫</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="row justify-content-end">
        <div class="col-md-5">
            <table class="table table-sm mb-0">
                @if($discountAmount > 0)
                    <tr>
                        <td class="text-muted border-0">Giảm giá
                            @if($booking->coupon_code)
                                <span class="text-muted">(mã {{ $booking->coupon_code }})</span>
                            @endif
                        </td>
                        <td class="text-end border-0 fw-semibold text-danger">−{{ number_format($discountAmount, 0, ',', '.') }} ₫</td>
                    </tr>
                @endif
                <tr>
                    <td class="border-0 pt-2 fs-5 fw-bold">Tổng thanh toán</td>
                    <td class="border-0 pt-2 text-end fs-5 fw-bold text-success">{{ number_format((float) $booking->total_price, 0, ',', '.') }} ₫</td>
                </tr>
            </table>
        </div>
    </div>

    <hr class="my-4">

    <h2 class="h6 text-muted text-uppercase fw-semibold mb-2">Thanh toán</h2>
    @php $pay = $booking->latestPayment; @endphp
    @if($pay)
        <p class="mb-1 small">
            <span class="text-muted">Phương thức:</span>
            @if($pay->method === 'bank_transfer') Chuyển khoản
            @elseif($pay->method === 'vnpay') VNPay
            @elseif($pay->method === 'cash') Tiền mặt
            @else {{ $pay->method }}
            @endif
        </p>
        <p class="mb-1 small">
            <span class="text-muted">Trạng thái:</span>
            @if($pay->status === 'paid') <span class="text-success fw-semibold">Đã thanh toán</span>
            @elseif($pay->status === 'refunded') <span class="text-info fw-semibold">Đã hoàn tiền</span>
            @else {{ $pay->status }}
            @endif
        </p>
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
