@extends('layouts.app')

@section('title', 'Hóa đơn #' . $invoiceNo)

@push('styles')
<style>
    .invoice-sheet {
        max-width: 880px;
        margin: 0 auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(15, 23, 42, 0.08);
    }
    .invoice-sheet .table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        font-weight: 600;
    }
    @media print {
        body { background: #fff !important; }
        .no-print, .navbar, footer, .btn, .alert { display: none !important; }
        .invoice-sheet { box-shadow: none !important; border-radius: 0 !important; max-width: 100% !important; }
        main, .container, .container-fluid { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    }
</style>
@endpush

@section('content')
<div class="mb-3 no-print">
    <a href="{{ $guestPortalBookingShowUrl ?? route('bookings.show', $booking) }}" class="btn btn-sm btn-outline-secondary text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Chi tiết đơn
    </a>
    <button type="button" class="btn btn-sm btn-dark ms-2" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>In / Lưu PDF
    </button>
</div>

@include('partials.booking-invoice-sheet')
@endsection
