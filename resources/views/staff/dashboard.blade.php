@extends('layouts.admin')

@section('title', 'Staff Dashboard')

@section('content')

<div class="container-fluid px-3 px-lg-4">

    <!-- Tiêu đề -->
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1">
            Dashboard Nhân viên
        </h1>

        <div class="text-muted small">
            Tổng quan tình trạng phòng và khách hôm nay.
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <div class="row g-3 mb-4">

        <!-- Check-in -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="small text-muted">
                        Check-in hôm nay
                    </div>

                    <div class="h4 fw-bold text-primary">
                        {{ $checkInToday }}
                    </div>

                </div>
            </div>
        </div>

        <!-- Check-out -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="small text-muted">
                        Check-out hôm nay
                    </div>

                    <div class="h4 fw-bold text-danger">
                        {{ $checkOutToday }}
                    </div>

                </div>
            </div>
        </div>

        <!-- Khách đang ở -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="small text-muted">
                        Khách đang ở
                    </div>

                    <div class="h4 fw-bold text-info">
                        {{ $guestsStaying }}
                    </div>

                </div>
            </div>
        </div>

        <!-- Phòng trống -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="small text-muted">
                        Phòng trống
                    </div>

                    <div class="h4 fw-bold text-success">
                        {{ $roomsAvailable }}
                    </div>

                </div>
            </div>
        </div>

        <!-- Phòng bảo trì -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="small text-muted">
                        Phòng bảo trì
                    </div>

                    <div class="h4 fw-bold text-secondary">
                        {{ $roomsMaintenance }}
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Danh sách check-in hôm nay -->
    <div class="card border-0 shadow-sm">

        <div class="card-header fw-bold">
            Danh sách check-in hôm nay
        </div>

        <div class="card-body p-0">

            <table class="table mb-0">

                <thead>
                    <tr>
                        <th>Khách</th>
                        <th>Phòng</th>
                        <th>Ngày check-in</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($todayBookings as $booking)

                    <tr>

                        <td>
                            {{ $booking->user->name ?? 'Khách lẻ' }}
                        </td>

                        <td>
                            {{ $booking->room->room_number ?? '' }}
                        </td>

                        <td>
                            {{ $booking->check_in_date }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="3" class="text-center text-muted">
                            Không có check-in hôm nay
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection