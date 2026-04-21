<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomChangeRequest;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Room;
use App\Models\RoomChangeHistory;
use App\Services\RoomChangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller riêng cho chức năng đổi phòng
 * 
 * Module độc lập không phụ thuộc vào BookingAdminController
 * Cho phép admin thực hiện đổi phòng từ giao diện chuyên biệt
 */
class RoomChangeController extends Controller
{
    protected RoomChangeService $roomChangeService;

    public function __construct(RoomChangeService $roomChangeService)
    {
        $this->middleware('admin');
        $this->roomChangeService = $roomChangeService;
    }

    /**
     * Danh sách tất cả lịch sử đổi phòng
     */
    public function index(Request $request)
    {
        $query = RoomChangeHistory::with(['booking.user', 'fromRoom.roomType', 'toRoom.roomType', 'changedBy'])
            ->latest('changed_at');

        // Bộ lọc theo booking
        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        // Bộ lọc theo phòng cũ
        if ($request->filled('from_room_id')) {
            $query->where('from_room_id', $request->from_room_id);
        }

        // Bộ lọc theo phòng mới
        if ($request->filled('to_room_id')) {
            $query->where('to_room_id', $request->to_room_id);
        }

        // Bộ lọc theo người đổi
        if ($request->filled('changed_by')) {
            $query->where('changed_by', $request->changed_by);
        }

        // Bộ lọc theo khoảng thời gian
        if ($request->filled('date_from')) {
            $query->whereDate('changed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('changed_at', '<=', $request->date_to);
        }

        // Bộ lọc theo loại đổi phòng
        if ($request->filled('change_type')) {
            $query->where('change_type', $request->change_type);
        }

        // Bộ lọc theo chênh lệch giá
        if ($request->filled('price_direction')) {
            if ($request->price_direction === 'increase') {
                $query->where('price_difference', '>', 0);
            } elseif ($request->price_direction === 'decrease') {
                $query->where('price_difference', '<', 0);
            } elseif ($request->price_direction === 'same') {
                $query->where('price_difference', 0);
            }
        }

        // Tìm kiếm theo lý do
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('reason', 'like', "%{$q}%")
                    ->orWhereHas('booking', fn($b) => $b->where('id', 'like', "%{$q}%"))
                    ->orWhereHas('fromRoom', fn($r) => $r->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('toRoom', fn($r) => $r->where('name', 'like', "%{$q}%"));
            });
        }

        $histories = $query->paginate(15)->withQueryString();

        // Thống kê tổng quan
        $stats = [
            'total' => RoomChangeHistory::count(),
            'today' => RoomChangeHistory::whereDate('changed_at', today())->count(),
            'this_month' => RoomChangeHistory::whereMonth('changed_at', now()->month)
                ->whereYear('changed_at', now()->year)
                ->count(),
            'total_price_increase' => RoomChangeHistory::where('price_difference', '>', 0)->sum('price_difference'),
            'total_price_decrease' => RoomChangeHistory::where('price_difference', '<', 0)->sum('price_difference'),
            'upgrades' => RoomChangeHistory::where('change_type', 'upgrade')->count(),
            'downgrades' => RoomChangeHistory::where('change_type', 'downgrade')->count(),
            'same_grade' => RoomChangeHistory::where('change_type', 'same_grade')->count(),
            'emergencies' => RoomChangeHistory::where('change_type', 'emergency')->count(),
        ];

        // Dữ liệu cho bộ lọc
        $rooms = Room::with('roomType')->orderBy('name')->get();

        return view('admin.room-changes.index', compact('histories', 'stats', 'rooms'));
    }

    /**
     * Hiển thị form đổi phòng
     */
    public function create(Request $request)
    {
        $booking = null;
        $currentBookingRooms = collect();
        $availableRooms = [];

        // Nếu có chọn booking, load thông tin
        if ($request->filled('booking_id')) {
            $booking = Booking::with(['bookingRooms.room.roomType', 'user'])
                ->findOrFail($request->booking_id);

            // Chỉ cho đổi phòng với booking đang hoạt động
            if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
                return back()->with('error', 'Chỉ có thể đổi phòng cho đơn đang hoạt động (pending, confirmed, checked_in).');
            }

            $currentBookingRooms = $booking->bookingRooms;
        }

        // Danh sách booking đang hoạt động để chọn
        $activeBookings = Booking::with(['user', 'bookingRooms.room'])
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->latest()
            ->get();

        return view('admin.room-changes.create', compact('booking', 'currentBookingRooms', 'availableRooms', 'activeBookings'));
    }

    /**
     * Thực hiện đổi phòng
     */
    public function store(RoomChangeRequest $request)
    {
        $booking = Booking::findOrFail($request->booking_id ?? $request->route('booking')?->id);

        try {
            $isEmergency = $request->boolean('is_emergency');

            $result = $this->roomChangeService->changeRoom(
                $booking,
                (int) $request->old_room_id,
                (int) $request->new_room_id,
                $request->reason,
                auth()->id(),
                $isEmergency
            );

            return redirect()
                ->route('admin.room-changes.show', $result['history_id'])
                ->with('success', 'Đổi phòng thành công! ' . $this->formatResultMessage($result));

        } catch (\Exception $e) {
            Log::error('Room change failed', [
                'booking_id' => $booking->id,
                'old_room_id' => $request->old_room_id,
                'new_room_id' => $request->new_room_id,
                'error' => $e->getMessage(),
            ]);
            return back()
                ->withInput()
                ->with('error', 'Đổi phòng thất bại: ' . $e->getMessage());
        }
    }

    /**
     * Xem chi tiết lần đổi phòng
     */
    public function show(int $id)
    {
        $history = RoomChangeHistory::with([
            'booking.user',
            'booking.bookingRooms.room.roomType',
            'fromRoom.roomType',
            'toRoom.roomType',
            'changedBy',
            'damageReport',
        ])->findOrFail($id);

        // Lấy tất cả lịch sử đổi phòng của booking này
        $bookingHistories = RoomChangeHistory::where('booking_id', $history->booking_id)
            ->where('id', '!=', $history->id)
            ->with(['fromRoom', 'toRoom', 'changedBy'])
            ->orderByDesc('changed_at')
            ->get();

        // Kiểm tra có thể hoàn tác không
        $canRevert = $this->canRevert($history);

        return view('admin.room-changes.show', compact('history', 'bookingHistories', 'canRevert'));
    }

    /**
     * Hoàn tác đổi phòng
     */
    public function revert(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->roomChangeService->revertRoomChange(
                $id,
                $request->reason,
                auth()->id()
            );

            return redirect()
                ->route('admin.room-changes.index')
                ->with('success', 'Hoàn tác đổi phòng thành công! ' . $this->formatPriceDifference($result['price_difference']));

        } catch (\Exception $e) {
            Log::error('Room change revert failed', [
                'history_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Hoàn tác thất bại: ' . $e->getMessage());
        }
    }

    /**
     * API: Lấy danh sách phòng trống để đổi
     */
    public function getAvailableRooms(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'current_room_id' => 'required|integer|exists:rooms,id',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        $rooms = $this->roomChangeService->getAvailableRoomsForChange(
            $booking,
            (int) $request->current_room_id
        );

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    /**
     * API: Lấy danh sách booking room của một booking
     */
    public function getBookingRooms(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        $booking = Booking::with(['bookingRooms.room.roomType', 'user'])
            ->findOrFail($request->booking_id);

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể đổi phòng cho đơn đang hoạt động',
            ], 400);
        }

        $rooms = $booking->bookingRooms->map(function ($br) {
            return [
                'id' => $br->id,
                'room_id' => $br->room_id,
                'room_name' => $br->room?->name ?? 'N/A',
                'room_type' => $br->room?->roomType?->name ?? 'N/A',
                'price_per_night' => $br->price_per_night,
                'nights' => $br->nights,
                'subtotal' => $br->subtotal,
                'adults' => $br->adults,
                'children_6_11' => $br->children_6_11,
                'children_0_5' => $br->children_0_5,
            ];
        });

        return response()->json([
            'success' => true,
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'check_in' => $booking->check_in?->format('d/m/Y'),
                'check_out' => $booking->check_out?->format('d/m/Y'),
                'guest_name' => $booking->user?->full_name ?? 'N/A',
            ],
            'data' => $rooms,
        ]);
    }

    /**
     * Kiểm tra xem có thể hoàn tác không
     */
    private function canRevert(RoomChangeHistory $history): bool
    {
        // Không thể hoàn tác nếu booking đã hoàn thành hoặc đã hủy
        $booking = Booking::find($history->booking_id);
        if (!$booking || in_array($booking->status, ['completed', 'cancelled'])) {
            return false;
        }

        // Kiểm tra phòng cũ có còn trống không
        return $this->roomChangeService->isRoomAvailable(
            $history->from_room_id,
            $booking->check_in->toDateString(),
            $booking->check_out->toDateString(),
            $booking->id
        );
    }

    /**
     * Format chênh lệch giá
     */
    private function formatPriceDifference(float $diff): string
    {
        if ($diff > 0) {
            return 'Giá tăng thêm: ' . number_format($diff, 0, ',', '.') . ' ₫';
        } elseif ($diff < 0) {
            return 'Giá giảm: ' . number_format(abs($diff), 0, ',', '.') . ' ₫';
        }
        return 'Giá không đổi';
    }

    /**
     * Format kết quả đổi phòng với đầy đủ thông tin nghiệp vụ
     */
    private function formatResultMessage(array $result): string
    {
        $parts = [];

        // Chênh lệch giá
        $diff = $result['price_difference'] ?? 0;
        if ($diff > 0) {
            $parts[] = 'Giá tăng thêm: ' . number_format($diff, 0, ',', '.') . ' ₫';
        } elseif ($diff < 0) {
            $parts[] = 'Giá giảm: ' . number_format(abs($diff), 0, ',', '.') . ' ₫';
        } else {
            $parts[] = 'Giá không đổi';
        }

        // Loại đổi phòng
        $changeTypeLabels = [
            'same_grade' => 'Cùng hạng',
            'upgrade'    => 'Nâng hạng',
            'downgrade'  => 'Hạ hạng',
            'emergency'  => 'Khẩn cấp',
        ];
        $changeType = $result['change_type'] ?? 'same_grade';
        $parts[] = 'Loại: ' . ($changeTypeLabels[$changeType] ?? $changeType);

        // Số đêm còn lại
        $remainingNights = $result['remaining_nights'] ?? 0;
        if ($remainingNights > 0) {
            $parts[] = $remainingNights . ' đêm còn lại';
        }

        return implode(' | ', $parts);
    }
}
