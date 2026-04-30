@extends('layouts.admin')

@section('title', 'Chi tiết đặt phòng #' . $booking->id)

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="bi bi-file-text me-2"></i>
                        Chi tiết đặt phòng #{{ $booking->id }}
                    </h4>
                    <div class="d-flex align-items-center gap-2">
                        @if($booking->actual_check_out)
                            <span class="badge bg-success">Đã check-out</span>
                        @elseif($booking->actual_check_in)
                            <span class="badge bg-info">Đã check-in</span>
                        @elseif($booking->status === 'pending')
                            <span class="badge bg-warning text-dark">Chờ thanh toán</span>
                        @else
                            <span class="badge bg-primary">Chờ check-in</span>
                        @endif

                        @if($booking->isAdminCheckinAllowed())
                            <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#checkinModal{{ $booking->id }}">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Check-in
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Thông tin chung -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                Thông tin chung
                            </h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Mã đặt phòng:</strong></td>
                                    <td>#{{ $booking->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Khách hàng:</strong></td>
                                    <td>{{ $booking->user->full_name ?? $booking->user->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $booking->user->email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Điện thoại:</strong></td>
                                    <td>{{ $booking->user->phone }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phòng:</strong></td>
                                    <td>
                                        @php
                                            $firstBR = $booking->bookingRooms->first();
                                            $roomNum = $firstBR?->room?->room_number ?? 'NULL';
                                            $roomName = $firstBR?->room?->name ?? 'NULL';
                                        @endphp
                                        @if($booking->bookingRooms->count() > 0)
                                            @php
                                                $processedTypes = $booking->bookingRooms->map(function($br) {
                                                    $typeName = $br->room->roomType?->name ?? '';
                                                    if (str_contains($typeName, ' ')) {
                                                        $parts = explode(' ', $typeName);
                                                        $typeName = end($parts);
                                                    }
                                                    return $typeName;
                                                });
                                                $typeCounts = $processedTypes->countBy();
                                                $roomList = $typeCounts->map(function($count, $name) {
                                                    return $count . ' ' . $name;
                                                })->values()->implode(', ');
                                            @endphp
                                            {{ $roomList }}
                                        @else
                                            @php
                                                $typeName = $booking->room->roomType?->name ?? '';
                                                if (str_contains($typeName, ' ')) {
                                                    $parts = explode(' ', $typeName);
                                                    $typeName = end($parts);
                                                }
                                            @endphp
                                            {{ $typeName }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày nhận phòng (dự kiến):</strong></td>
                                    <td>{{ $booking->formatted_check_in }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày trả phòng (dự kiến):</strong></td>
                                    <td>{{ $booking->formatted_check_out }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Thời gian check-in thực tế:</strong></td>
                                    <td>{{ $booking->formatted_actual_check_in ?? 'Chưa check-in' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Thời gian check-out thực tế:</strong></td>
                                    <td>{{ $booking->formatted_actual_check_out ?? 'Chưa check-out' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Số đêm:</strong></td>
                                    <td>{{ $booking->nights }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tổng tiền:</strong></td>
                                    <td class="text-primary fw-bold">{{ number_format($booking->total_price, 0, ',', '.') }} VNĐ</td>
                                </tr>
                                <tr>
                                    <td><strong>Phương thức thanh toán:</strong></td>
                                    <td>{{ $booking->payment_method === 'cash' ? 'Tiền mặt' : 'VNPay' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="bi bi-people-fill me-2"></i>
                                Thông tin khách hàng
                            </h5>

                            <div class="table-responsive" style="max-width: 100%; overflow-x: auto;">
                                <table class="table table-hover table-sm mb-0 align-middle w-100">
                                    <thead class="table-light small">
                                        <tr>
                                            <th class="ps-3">Tên khách hàng</th>
                                            <th>CCCD</th>
                                            <th>Phòng</th>
                                            <th>Loại khách</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Người đại diện --}}
                                        @php
                                            // Lấy từ bookingGuests mới (ưu tiên cao nhất)
                                            $repGuest = $booking->bookingGuests()->where('is_representative', 1)->first();

                                            // Lấy tên người đại diện (ưu tiên: bookingGuests > representative_name > user)
                                            $repName = $repGuest?->name ?? $booking->representative_name ?? $booking->user?->full_name ?? '—';

                                            // Lấy CCCD (ưu tiên: bookingGuests > booking > user)
                                            $repCccd = $repGuest?->cccd ?? $booking->cccd;

                                            if (!$repCccd && $booking->user_id) {
                                                $user = \App\Models\User::find($booking->user_id);
                                                $repCccd = $user?->cccd ?? $user?->identity_card ?? $user?->cmnd ?? null;
                                            }

                                            // Force load bookingRooms với room để tra cứu nhanh
                                            $bookingRoomsLoaded = $booking->bookingRooms()->with('room.roomType')->get();
                                            $bookingRoomsMap = $bookingRoomsLoaded->keyBy('id');
                                        @endphp
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center" style="white-space: nowrap; gap: 8px;">
                                                    <span>{{ $repName }}</span>
                                                    <span class="badge bg-primary">Người đại diện</span>
                                                </div>
                                            </td>
                                            <td>
                                                @if($repCccd)
                                                    {{ $repCccd }}
                                                @else
                                                    <span class="text-danger fw-bold">Chưa có CCCD!</span>
                                                @endif
                                            </td>
                                            <td>
                                                        @php
                                                            // Lấy phòng từ repGuest dùng bookingRoomsMap
                                                            $repRoom = null;
                                                            $repBookingRoomId = $repGuest?->booking_room_id;

                                                            if ($repBookingRoomId && isset($bookingRoomsMap[$repBookingRoomId])) {
                                                                $repRoom = $bookingRoomsMap[$repBookingRoomId]->room;
                                                            } elseif ($repGuest?->bookingRoom) {
                                                                $repRoom = $repGuest->bookingRoom->room;
                                                            } else {
                                                                $repRoom = $booking->bookingRooms->first()?->room;
                                                            }

                                                            $fullTypeName = $repRoom?->roomType?->name ?? '';
                                                            $typeName = $fullTypeName;
                                                            // Lấy từ cuối nếu có dấu cách (VD: "Coastal Garden Standard" -> "Standard")
                                                            if (str_contains($typeName, ' ')) {
                                                                $parts = explode(' ', $typeName);
                                                                $typeName = end($parts);
                                                            }
                                                            $roomNum = $repRoom?->room_number ?? '';
                                                        @endphp
                                                        <span style="white-space: nowrap">{{ $typeName }}&nbsp;{{ $roomNum }}</span>
                                                    </td>
                                            <td>
                                                <span class="badge bg-info">Người lớn</span>
                                            </td>
                                            <td>
                                                @php
                                                    $isCheckedOut = $booking->actual_check_out !== null;
                                                    $isCheckedIn = $booking->actual_check_in || $booking->bookingGuests?->contains('status', 'checked_in');
                                                @endphp
                                                @if($isCheckedOut)
                                                    <span class="badge bg-secondary">Đã check-out</span>
                                                @elseif($isCheckedIn)
                                                    <span class="badge bg-success">Đã check-in</span>
                                                @else
                                                    <span class="badge bg-warning">Chờ check-in</span>
                                                @endif
                                            </td>
                                        </tr>

                                        {{-- Danh sách khách (không bao gồm người đại diện) --}}
                                        @php
                                            // Lấy danh sách khách không phải người đại diện
                                            $bookingGuestsList = $booking->bookingGuests()
                                                ->with(['bookingRoom' => function($q) {
                                                    $q->with('room.roomType');
                                                }])
                                                ->get();
                                            // $bookingRoomsMap đã được tạo ở trên
                                        @endphp
                                        @if($bookingGuestsList->count() > 0)
                                            @php
                                                // Lọc bỏ người đại diện
                                                $nonRepGuests = $bookingGuestsList->where('is_representative', 0);
                                            @endphp
                                            @foreach($nonRepGuests as $guest)
                                                <tr>
                                                    <td class="ps-3">{{ $guest->name }}</td>
                                                    <td>{{ $guest->cccd ?? '-' }}</td>
                                                    <td>
                                                        @php
                                                            // Lấy phòng từ booking_room_id của khách
                                                            $guestBookingRoomId = $guest->booking_room_id;
                                                            $guestRoom = null;

                                                            // DEBUG: Hiển thị booking_room_id
                                                            // echo "<small class='text-muted'>(br_id: " . ($guestBookingRoomId ?? 'null') . ")</small> ";

                                                            if ($guestBookingRoomId && isset($bookingRoomsMap[$guestBookingRoomId])) {
                                                                $guestRoom = $bookingRoomsMap[$guestBookingRoomId]->room;
                                                            } elseif ($guest->bookingRoom) {
                                                                $guestRoom = $guest->bookingRoom->room;
                                                            }

                                                            $fullTypeName = $guestRoom?->roomType?->name ?? '';
                                                            $roomTypeName = $fullTypeName;
                                                            if (str_contains($fullTypeName, ' ')) {
                                                                $parts = explode(' ', $fullTypeName);
                                                                $roomTypeName = end($parts);
                                                            }
                                                            $roomNumber = $guestRoom?->room_number ?? '';
                                                            echo $roomTypeName . '&nbsp;' . $roomNumber;
                                                        @endphp
                                                    </td>
                                                    <td>
                                                        @if(($guest->type ?? 'adult') === 'adult')
                                                            <span class="badge bg-info">Người lớn</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark">Trẻ em</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $guestCheckedOut = $booking->actual_check_out !== null;
                                                            $guestCheckedIn = ($guest->status ?? $guest->checkin_status) === 'checked_in' || $booking->actual_check_in;
                                                        @endphp
                                                        @if($guestCheckedOut)
                                                            <span class="badge bg-secondary">Đã check-out</span>
                                                        @elseif($guestCheckedIn)
                                                            <span class="badge bg-success">Đã check-in</span>
                                                        @else
                                                            <span class="badge bg-warning">Chờ check-in</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Check-out khách hàng
                            </h5>

                            @if($booking->actual_check_out)
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Đã check-out lúc {{ $booking->actual_check_out->format('d/m/Y H:i') }}
                                </div>
                            @else
                                <form method="POST" action="{{ route('admin.bookings.checkout', $booking->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-warning"
                                            onclick="return confirm('Bạn có chắc chắn muốn check-out khách hàng này?')">
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Check-out
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Quay lại danh sách
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($booking->isAdminCheckinAllowed())
    @include('admin.bookings._checkin_modal', ['booking' => $booking])
@endif

<style>
.table-responsive {
    max-width: 100%;
    overflow-x: auto;
}
.table-responsive table {
    width: 100% !important;
}
.table-responsive th {
    white-space: nowrap;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function() {
        const el = document.querySelector('.table-responsive');
        if (el) el.scrollLeft = 0;
    }, 100);
});
</script>

@endsection
