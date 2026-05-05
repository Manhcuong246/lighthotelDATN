<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Admin\RoomChangeController as AdminRoomChangeController;
use App\Models\Booking;
use App\Models\RoomChangeHistory;
use App\Services\RoomChangeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RoomChangeController extends AdminRoomChangeController
{
    public function __construct()
    {
        // Staff routes dùng middleware auth + staff — không gắn middleware admin.
    }

    protected function roomChangesRoutePrefix(): string
    {
        return 'staff';
    }

    /**
     * Danh sách lịch sử đổi phòng (staff view)
     */
    public function index(Request $request)
    {
        $query = RoomChangeHistory::with(['booking.user', 'fromRoom.roomType', 'toRoom.roomType', 'changedBy'])
            ->orderByDesc('changed_at')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('id', 'like', "%{$q}%")
                    ->orWhere('booking_id', 'like', "%{$q}%")
                    ->orWhereHas('fromRoom', fn ($r) => $r->where('room_number', 'like', "%{$q}%"))
                    ->orWhereHas('toRoom', fn ($r) => $r->where('room_number', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('price_direction')) {
            $dir = $request->price_direction;
            if ($dir === 'increase') {
                $query->where('price_difference', '>', 0);
            } elseif ($dir === 'decrease') {
                $query->where('price_difference', '<', 0);
            } elseif ($dir === 'same') {
                $query->where('price_difference', 0);
            }
        }

        if ($request->filled('change_type')) {
            $type = $request->change_type;
            if (in_array($type, [
                RoomChangeHistory::TYPE_UPGRADE,
                RoomChangeHistory::TYPE_DOWNGRADE,
                RoomChangeHistory::TYPE_SAME_GRADE,
                RoomChangeHistory::TYPE_EMERGENCY,
            ], true)) {
                $query->where('change_type', $type);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('changed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('changed_at', '<=', $request->date_to);
        }

        $histories = $query->paginate(15);

        $stats = [
            'total' => RoomChangeHistory::count(),
            'today' => RoomChangeHistory::whereDate('changed_at', Carbon::today())->count(),
            'upgrades' => RoomChangeHistory::where('change_type', RoomChangeHistory::TYPE_UPGRADE)->count(),
            'downgrades' => RoomChangeHistory::where('change_type', RoomChangeHistory::TYPE_DOWNGRADE)->count(),
            'total_price_increase' => RoomChangeHistory::where('price_difference', '>', 0)->sum('price_difference'),
            'total_price_decrease' => RoomChangeHistory::where('price_difference', '<', 0)->sum('price_difference'),
        ];

        return view('staff.room-changes.index', compact('histories', 'stats'));
    }

    /**
     * Chi tiết một lần đổi phòng (staff view)
     */
    public function show($id)
    {
        $history = RoomChangeHistory::with([
            'booking.user',
            'fromRoom.roomType',
            'toRoom.roomType',
            'changedBy',
            'damageReport',
        ])->findOrFail($id);

        $canRevert = $history->fromRoom && $history->fromRoom->status === 'available';
        $bookingHistories = RoomChangeHistory::where('booking_id', $history->booking_id)
            ->where('id', '!=', $history->id)
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->get();

        return view('staff.room-changes.show', compact('history', 'canRevert', 'bookingHistories'));
    }

    /**
     * Hiển thị giao diện đổi phòng (staff view)
     */
    public function create(Request $request, $bookingId = null)
    {
        $booking = null;
        $currentBookingRoom = null;
        $remainingNights = 0;

        if ($bookingId) {
            $booking = Booking::with(['bookingRooms.room.roomType', 'user', 'guests'])->findOrFail($bookingId);

            if ($booking->status !== 'checked_in') {
                return redirect()->route('staff.room-changes.create')
                    ->with('error', 'Chức năng đổi phòng chỉ áp dụng cho đơn đã Check-in.');
            }

            $roomId = $request->room_id;
            if ($roomId) {
                $currentBookingRoom = $booking->bookingRooms->where('room_id', $roomId)->first();
            }

            if (! $currentBookingRoom) {
                $currentBookingRoom = $booking->bookingRooms->first();
            }

            if (! $currentBookingRoom) {
                return back()->with('error', 'Không tìm thấy thông tin phòng cho đơn này.');
            }

            if (! $currentBookingRoom->room_id) {
                return redirect()
                    ->route('staff.room-changes.create')
                    ->with('error', 'Đơn #'.$bookingId.' chưa gán phòng vật lý. Vui lòng gán phòng trong mục Đặt phòng trước khi đổi phòng.');
            }

            $remainingNights = app(RoomChangeService::class)->calculateRemainingNights($booking);
        }

        return view('staff.room-changes.create', compact('booking', 'currentBookingRoom', 'remainingNights'));
    }

    /**
     * Xử lý xác nhận đổi phòng — cùng logic với admin (RoomChangeService).
     */
    public function store(Request $request, RoomChangeService $roomChangeService)
    {
        return $this->performRoomChangeStore($request, $roomChangeService);
    }
}
