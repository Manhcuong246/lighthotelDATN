@extends('layouts.app')

@section('title', 'Lịch sử đặt phòng')

@push('styles')
<style>
.booking-page .page-header {
    margin-bottom: 1.5rem;
}
.booking-page .page-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #111827;
}
.booking-page .page-subtitle {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 0.15rem;
}
.booking-page .booking-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.booking-page .booking-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: box-shadow 0.2s ease;
}
.booking-page .booking-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.booking-page a.booking-card {
    cursor: pointer;
}
.booking-page .booking-card-inner {
    padding: 1rem 1.25rem;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.75rem 1.5rem;
    align-items: center;
}
@media (max-width: 767px) {
    .booking-page .booking-card-inner {
        grid-template-columns: 1fr;
        padding: 1rem;
    }
}
.booking-page .booking-main {
    min-width: 0;
}
.booking-page .booking-room {
    font-weight: 600;
    font-size: 1rem;
    color: #111827;
}
.booking-page .booking-room i {
    color: #6366f1;
    margin-right: 0.35rem;
    font-size: 0.9em;
}
.booking-page .booking-details {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 0.4rem;
    font-size: 0.8rem;
    color: #6b7280;
}
.booking-page .booking-details span {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
.booking-page .booking-details i {
    color: #9ca3af;
    font-size: 0.9em;
}
.booking-page .booking-side {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem 1rem;
    justify-content: flex-end;
}
@media (max-width: 767px) {
    .booking-page .booking-side {
        justify-content: flex-start;
        padding-top: 0.5rem;
        border-top: 1px solid #f3f4f6;
    }
}
.booking-page .booking-price {
    font-weight: 600;
    font-size: 0.95rem;
    color: #059669;
}
.booking-page .booking-status {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}
.booking-page .booking-status.pending { background: #fef3c7; color: #92400e; }
.booking-page .booking-status.confirmed { background: #dbeafe; color: #1d4ed8; }
.booking-page .booking-status.completed { background: #d1fae5; color: #047857; }
.booking-page .booking-status.cancelled { background: #f3f4f6; color: #6b7280; }
.booking-page .empty-state {
    padding: 2.5rem 1.5rem;
    text-align: center;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    border: 1px solid #e5e7eb;
}
.booking-page .empty-state-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6366f1;
    font-size: 1.5rem;
}
.booking-page .empty-state h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.35rem;
}
.booking-page .empty-state p {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
.booking-page .pagination-wrap {
    margin-top: 1.25rem;
    display: flex;
    justify-content: center;
}
.booking-page .pagination-wrap .pagination {
    --bs-pagination-font-size: 0.875rem;
}
</style>
@endpush

@section('content')
<div class="booking-page">
    <div class="page-header">
        <h1 class="page-title">Lịch sử đặt phòng</h1>
        <p class="page-subtitle">Xem và quản lý các đơn đặt phòng của bạn</p>

        {{-- Debug Info --}}
        <div class="alert alert-info">
            <small><strong>Debug:</strong> User ID: {{ Auth::id() }} | Total bookings: {{ $bookings->count() }}</small>
        </div>
    </div>

    @if($bookings->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
            <h3>Chưa có đơn đặt phòng</h3>
            <p>Bạn chưa có đơn đặt phòng nào. Khám phá các phòng và đặt ngay.</p>
            <a href="{{ route('home') }}#rooms-section" class="btn btn-primary btn-sm px-3 py-2 rounded-pill">
                <i class="bi bi-search me-1"></i>Xem phòng & đặt ngay
            </a>
        </div>
    @else
        <div class="booking-list">
            @foreach($bookings as $b)
            <a href="{{ route('account.bookings.show', $b) }}" class="booking-card text-decoration-none text-body d-block">
                <div class="booking-card-inner">
                    <div class="booking-main">
                        <div class="booking-room">
                            <i class="bi bi-door-open"></i>{{ $b->room ? $b->room->name : '—' }}
                        </div>
                        <div class="booking-details">
                            <span><i class="bi bi-calendar-check"></i>{{ $b->check_in ? $b->check_in->format('d/m/Y') : '—' }}</span>
                            <span><i class="bi bi-arrow-right"></i></span>
                            <span><i class="bi bi-calendar-x"></i>{{ $b->check_out ? $b->check_out->format('d/m/Y') : '—' }}</span>
                            <span><i class="bi bi-people"></i>{{ $b->guests ?? '—' }} khách</span>
                            @if(($b->booking_services_count ?? 0) > 0)
                            <span><i class="bi bi-bag-check"></i>{{ $b->booking_services_count }} dịch vụ kèm</span>
                            @endif
                        </div>
                    </div>
                    <div class="booking-side">
                        <span class="booking-price">{{ $b->total_price ? number_format($b->total_price, 0, ',', '.') . ' ₫' : '—' }}</span>
                        <span class="booking-status {{ $b->status }}">
                            @if($b->status === 'pending') Chờ xác nhận
                            @elseif($b->status === 'confirmed') Đã xác nhận
                            @elseif($b->status === 'completed') Hoàn thành
                            @elseif($b->status === 'cancelled') Đã hủy
                            @else {{ $b->status }}
                            @endif
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        @if($bookings->hasPages())
        <div class="pagination-wrap">
            {{ $bookings->links() }}
        </div>
        @endif
    @endif
</div>
@endsection
