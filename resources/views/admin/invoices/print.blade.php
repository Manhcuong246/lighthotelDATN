<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hóa đơn {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", system-ui, sans-serif; font-size: 11px; color: #1a1a1a; max-width: 800px; margin: 0 auto; padding: 20px 16px 40px; line-height: 1.45; }
        .brand { border-bottom: 2px solid #1d3557; padding-bottom: 12px; margin-bottom: 16px; }
        .brand h1 { font-size: 1.1rem; margin: 0 0 4px; color: #1d3557; }
        .brand p { margin: 0; color: #444; }
        .doc-title { text-align: center; font-size: 1rem; font-weight: 700; letter-spacing: 0.04em; margin: 16px 0 12px; color: #1d3557; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin-bottom: 16px; }
        .box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 12px; }
        .box h3 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em; color: #6c757d; margin: 0 0 6px; }
        .box p { margin: 0 0 4px; }
        table.detail { width: 100%; border-collapse: collapse; margin: 12px 0 16px; font-size: 10.5px; }
        table.detail th, table.detail td { border: 1px solid #adb5bd; padding: 6px 8px; vertical-align: top; }
        table.detail th { background: #e9ecef; font-weight: 600; }
        table.detail tr.table-secondary td { background: #dee2e6; font-weight: 700; font-size: 10px; text-transform: uppercase; }
        table.detail tfoot td { border: 1px solid #adb5bd; padding: 6px 8px; vertical-align: middle; }
        table.detail tfoot tr.table-secondary td { background: #dee2e6; }
        table.detail tfoot tr.table-light td { background: #f8f9fa; }
        table.detail tfoot .border-top.border-2 td { background: #e9ecef; font-size: 11px; }
        .text-end { text-align: right; }
        .muted { color: #6c757d; font-size: 10px; margin-top: 20px; }
        @media print {
            body { padding: 12px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="brand">
        @if($hotel ?? null)
            <h1>{{ $hotel->name }}</h1>
            @if($hotel->address)<p>{{ $hotel->address }}</p>@endif
            <p>
                @if($hotel->phone)<span>ĐT: {{ $hotel->phone }}</span>@endif
                @if($hotel->email)<span> · Email: {{ $hotel->email }}</span>@endif
            </p>
        @else
            <h1>Light Hotel</h1>
            <p class="muted">Cập nhật thông tin khách sạn trong quản trị (Hotel info).</p>
        @endif
    </div>

    <div class="doc-title">HÓA ĐƠN THANH TOÁN LƯU TRÚ</div>

    <div class="grid">
        <div class="box">
            <h3>Hóa đơn</h3>
            <p><strong>Số:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Ngày lập:</strong> {{ $invoice->issued_at?->format('d/m/Y H:i') ?? '—' }}</p>
            <p><strong>Đơn đặt:</strong> #{{ $invoice->booking_id }}</p>
            <p><strong>Trạng thái HĐ:</strong>
                @if($invoice->status === 'paid') Đã thanh toán đủ
                @elseif($invoice->status === 'partially_paid') Thanh toán một phần
                @else Chờ thanh toán
                @endif
            </p>
        </div>
        <div class="box">
            <h3>Khách hàng</h3>
            <p><strong>Họ tên:</strong> {{ $invoice->booking?->user?->full_name ?? '—' }}</p>
            <p><strong>Email:</strong> {{ $invoice->booking?->user?->email ?? '—' }}</p>
            <p><strong>SĐT:</strong> {{ $invoice->booking?->user?->phone ?? '—' }}</p>
        </div>
        <div class="box">
            <h3>Thời gian lưu trú</h3>
            <p><strong>Nhận phòng:</strong> {{ $invoice->booking?->check_in?->format('d/m/Y') ?? '—' }}</p>
            <p><strong>Trả phòng:</strong> {{ $invoice->booking?->check_out?->format('d/m/Y') ?? '—' }}</p>
        </div>
        <div class="box">
            <h3>Thanh toán đơn</h3>
            <p><strong>PTTT:</strong> {{ $invoice->booking?->payment_method ?? '—' }}</p>
            @php $lp = $invoice->booking?->latestPayment; @endphp
            <p><strong>Phiếu thanh toán:</strong> {{ $lp?->status ?? '—' }} @if($lp?->transaction_id) · {{ $lp->transaction_id }} @endif</p>
        </div>
    </div>

    <p class="fw-bold" style="margin:0 0 6px;font-size:12px;">Chi tiết khoản phí</p>
    @include('admin.invoices.partials.items-table', [
        'invoice' => $invoice,
        'tableClass' => 'detail',
        'theadClass' => '',
        'withSummaryFooter' => true,
    ])

    @if($invoice->notes)
        <p style="margin-top:16px;"><strong>Ghi chú:</strong> {{ $invoice->notes }}</p>
    @endif

    <p class="muted no-print">Nhấn Ctrl+P để in — {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
