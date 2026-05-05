<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomChangeHistory;
use App\Models\BookingRoom;
use App\Models\RoomBookedDate;
use App\Services\RoomChangeService;
use App\Support\InvoiceBookingSynchronizer;
use App\Support\RoomOccupancyPricing;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomChangeController extends Controller
{
    /** Prefix route Laravel cho module đổi phòng (admin | staff). */
    protected function roomChangesRoutePrefix(): string
    {
        return 'admin';
    }

    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('only_admin');
    }

    /**
     * Danh sách lịch sử đổi phòng
     */
    public function index(Request $request)
    {
        $query = RoomChangeHistory::with(['booking.user', 'fromRoom.roomType', 'toRoom.roomType', 'changedBy'])
            ->orderByDesc('changed_at')
            ->orderByDesc('id');

        // Lọc theo từ khóa (Mã booking, số phòng)
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('id', 'like', "%{$q}%")
                    ->orWhere('booking_id', 'like', "%{$q}%")
                    ->orWhereHas('fromRoom', fn($r) => $r->where('room_number', 'like', "%{$q}%"))
                    ->orWhereHas('toRoom', fn($r) => $r->where('room_number', 'like', "%{$q}%"));
            });
        }

        // Lọc theo chênh lệch giá
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

        // Lọc theo loại đổi phòng (theo cột change_type)
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

        // Lọc theo ngày
        if ($request->filled('date_from')) {
            $query->whereDate('changed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('changed_at', '<=', $request->date_to);
        }

        $histories = $query->paginate(15);

        // Thống kê cơ bản
        $stats = [
            'total' => RoomChangeHistory::count(),
            'today' => RoomChangeHistory::whereDate('changed_at', Carbon::today())->count(),
            'upgrades' => RoomChangeHistory::where('change_type', RoomChangeHistory::TYPE_UPGRADE)->count(),
            'downgrades' => RoomChangeHistory::where('change_type', RoomChangeHistory::TYPE_DOWNGRADE)->count(),
            'total_price_increase' => RoomChangeHistory::where('price_difference', '>', 0)->sum('price_difference'),
            'total_price_decrease' => RoomChangeHistory::where('price_difference', '<', 0)->sum('price_difference'),
        ];

        return view('admin.room-changes.index', compact('histories', 'stats'));
    }

    /**
     * Chi tiết một lần đổi phòng
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

        return view('admin.room-changes.show', compact('history', 'canRevert', 'bookingHistories'));
    }

    /**
     * Hiển thị giao diện đổi phòng
     */
    public function create(Request $request, $bookingId = null)
    {
        $booking = null;
        $currentBookingRoom = null;
        $remainingNights = 0;

        if ($bookingId) {
            $booking = Booking::with(['bookingRooms.room.roomType', 'user', 'guests'])->findOrFail($bookingId);

            // Chỉ áp dụng cho booking đã check-in
            if ($booking->status !== 'checked_in') {
                return redirect()->route('admin.room-changes.create')
                    ->with('error', 'Chức năng đổi phòng chỉ áp dụng cho đơn đã Check-in.');
            }

            // Lấy phòng hiện tại (Ưu tiên theo room_id truyền vào request)
            $roomId = $request->room_id;
            if ($roomId) {
                $currentBookingRoom = $booking->bookingRooms->where('room_id', $roomId)->first();
            }
            
            if (!$currentBookingRoom) {
                $currentBookingRoom = $booking->bookingRooms->first();
            }

            if (!$currentBookingRoom) {
                return back()->with('error', 'Không tìm thấy thông tin phòng cho đơn này.');
            }

            if (! $currentBookingRoom->room_id) {
                return redirect()
                    ->route('admin.room-changes.create')
                    ->with('error', 'Đơn #'.$bookingId.' chưa gán phòng vật lý. Vui lòng gán phòng trong mục Đặt phòng trước khi đổi phòng.');
            }

            $remainingNights = app(RoomChangeService::class)->calculateRemainingNights($booking);
        }

        return view('admin.room-changes.create', compact('booking', 'currentBookingRoom', 'remainingNights'));
    }

    /**
     * Nhãn hiển thị khi tìm đơn đổi phòng: tránh "Phòng N/A" khi chưa gán phòng vật lý hoặc thiếu room_number.
     *
     * @return array{badge: string, guest_suffix: string, room_type: string}
     */
    private function labelsForRoomChangeBookingSearchRow(BookingRoom $br): array
    {
        $br->loadMissing(['room.roomType', 'roomType']);
        $typeName = $br->room?->roomType?->name ?? $br->roomType?->name ?? '—';

        if ($br->room) {
            $primary = $br->room->room_number ?: $br->room->name;
            if (! $primary) {
                $primary = $br->room->displayLabel();
            }
            $guestSuffix = ($typeName !== '—')
                ? " ({$primary} · {$typeName})"
                : " ({$primary})";

            return [
                'badge' => $primary,
                'guest_suffix' => $guestSuffix,
                'room_type' => $typeName,
            ];
        }

        return [
            'badge' => 'Chưa gán',
            'guest_suffix' => " (Chưa gán phòng · {$typeName})",
            'room_type' => $typeName,
        ];
    }

    /**
     * API: Tìm kiếm booking đang check-in
     */
    public function searchBooking(Request $request)
    {
        try {
            $q = $request->q;
            \Log::info("RoomChange Search - Query: " . ($q ?: 'Empty'));
            
            $query = Booking::with(['user', 'bookingRooms.room.roomType', 'bookingRooms.roomType'])
                ->where('status', 'checked_in');

            if ($q) {
                $query->where(function($query) use ($q) {
                    $query->where('id', 'like', "%{$q}%")
                          ->orWhereHas('user', function($u) use ($q) {
                              $u->where('full_name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                          });
                });
            }

            $results = $query->latest()->limit(10)->get();
            \Log::info("RoomChange Search - Found: " . $results->count() . " bookings");

            $bookings = [];
            foreach ($results as $b) {
                foreach ($b->bookingRooms as $br) {
                    $labels = $this->labelsForRoomChangeBookingSearchRow($br);

                    $bookings[] = [
                        'id' => $b->id,
                        'room_id' => $br->room_id,
                        'guest_name' => ($b->user->full_name ?? 'Khách lẻ').$labels['guest_suffix'],
                        'phone' => $b->user->phone ?? '—',
                        'room_number' => $labels['badge'],
                        'room_type' => $labels['room_type'],
                        'room_assigned' => $br->room_id !== null && $br->room !== null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $bookings
            ]);
        } catch (\Exception $e) {
            \Log::error("RoomChange Search ERROR: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách phòng trống đủ sức chứa (đổi phòng — trang riêng)
     */
    public function getAvailableRooms(Request $request, RoomChangeService $roomChangeService)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'old_room_id' => 'nullable|exists:rooms,id',
        ]);

        $booking = Booking::with('bookingRooms.room.roomType')->findOrFail($request->booking_id);

        $bookingRoom = $booking->bookingRooms->firstWhere('room_id', (int) $request->get('old_room_id'))
            ?? $booking->bookingRooms->first();

        if (! $bookingRoom) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $excludeIds = $roomChangeService->getExcludedRoomIdsForChange($booking);

        $query = Room::with('roomType')
            ->where('status', 'available')
            ->excludeMaintenance();

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        // Trẻ 0–5 không tính vào sức chứa khi gợi ý phòng đổi
        $totalGuests = (int) $bookingRoom->adults + (int) $bookingRoom->children_6_11;

        $rooms = $query->get()
            ->filter(static fn (Room $room) => $room->catalogueMaxGuests() >= $totalGuests)
            ->map(function ($room) use ($bookingRoom) {
            $rt = $room->roomType;
            $base = (float) $room->catalogueBasePrice();

            $breakdown = RoomOccupancyPricing::breakdown(
                $base,
                (int) $bookingRoom->adults,
                (int) $bookingRoom->children_6_11,
                (int) $bookingRoom->children_0_5,
                $rt
            );

            return [
                'id' => $room->id,
                'room_number' => $room->room_number ?? $room->name ?? 'N/A',
                'room_type' => $rt->name ?? 'N/A',
                'price' => $breakdown['base_price'],
                'price_per_night_full' => $breakdown['price_per_night'],
                'surcharge_per_night' => $breakdown['surcharge_per_night'],
                'capacity' => $rt->capacity ?? 0,
                'standard_capacity' => $rt->standard_capacity ?? $rt->capacity ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    /**
     * Xử lý xác nhận đổi phòng (tính tiền theo RoomChangeService + RoomOccupancyPricing).
     */
    public function store(Request $request, RoomChangeService $roomChangeService)
    {
        return $this->performRoomChangeStore($request, $roomChangeService);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function performRoomChangeStore(Request $request, RoomChangeService $roomChangeService)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'old_room_id' => 'required|exists:rooms,id',
            'new_room_id' => 'required|exists:rooms,id',
            'reason' => 'required|string',
            'other_reason' => 'nullable|string',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chức năng đổi phòng chỉ áp dụng cho đơn đã Check-in.');
        }

        $oldRoom = Room::findOrFail($request->old_room_id);
        $newRoom = Room::with('roomType')->findOrFail($request->new_room_id);

        if ($newRoom->isInMaintenance() || $newRoom->status !== 'available') {
            return back()->with('error', 'Phòng đích phải đang trống và không ở trạng thái bảo trì.');
        }

        $reason = $request->reason === 'Khác'
            ? ($request->other_reason ?: 'Khác')
            : $request->reason;

        try {
            $result = $roomChangeService->changeRoom(
                $booking,
                (int) $request->old_room_id,
                (int) $request->new_room_id,
                $reason,
                auth()->id(),
                null,
                $request->boolean('is_emergency'),
                $request->boolean('keep_price')
            );
        } catch (\Throwable $e) {
            Log::error('Room change error: ' . $e->getMessage());

            return back()->with('error', $e->getMessage());
        }

        return $this->roomChangeSuccessRedirect($booking->fresh(), $oldRoom, $newRoom, $result);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    /**
     * @param  array<string, mixed>  $changeResult  Kết quả từ RoomChangeService::changeRoom (có history_id).
     */
    protected function roomChangeSuccessRedirect(Booking $booking, Room $oldRoom, Room $newRoom, array $changeResult): \Illuminate\Http\RedirectResponse
    {
        $hid = (int) ($changeResult['history_id'] ?? 0);
        $pfx = $this->roomChangesRoutePrefix();

        if ($hid <= 0) {
            return redirect()->route($pfx.'.room-changes.index')
                ->with('warning', 'Đổi phòng đã thực hiện nhưng không tìm thấy mã lịch sử để mở chi tiết.');
        }

        return redirect()->route($pfx.'.room-changes.show', $hid)
            ->with(
                'success',
                'Đổi phòng thành công từ '.($oldRoom->room_number ?: $oldRoom->name).' sang '.($newRoom->room_number ?: $newRoom->name).'.'
            );
    }

    /**
     * Hoàn tác đổi phòng
     */
    public function revert(Request $request, $id)
    {
        $history = RoomChangeHistory::findOrFail($id);
        $booking = Booking::findOrFail($history->booking_id);

        // Kiểm tra phòng cũ có còn trống không
        $fromRoom = Room::findOrFail($history->from_room_id);
        if ($fromRoom->status !== 'available') {
            return back()->with('error', 'Không thể hoàn tác vì phòng cũ ('.$fromRoom->room_number.') không còn trống.');
        }

        try {
            DB::transaction(function () use ($booking, $history, $fromRoom) {
                $toRoom = Room::findOrFail($history->to_room_id);

                $toRoom->update(['status' => 'available']);
                $fromRoom->update(['status' => 'booked']);

                $bookingRoom = BookingRoom::where('booking_id', $booking->id)
                    ->where('room_id', $toRoom->id)
                    ->first();

                $oldPpn = (float) ($history->old_price_per_night ?? $fromRoom->roomType->price ?? 0);
                $nights = max(1, (int) ($bookingRoom?->nights ?? 1));

                if ($bookingRoom) {
                    $bookingRoom->update([
                        'room_id' => $fromRoom->id,
                        'price_per_night' => $oldPpn,
                        'subtotal' => round($oldPpn * $nights, 2),
                    ]);
                }

                $lastNight = Carbon::parse($booking->check_out)->startOfDay()->subDay();
                $start = Carbon::today()->max(Carbon::parse($booking->check_in)->startOfDay());
                if ($start->lte($lastNight)) {
                    $period = CarbonPeriod::create($start, $lastNight);
                    RoomBookedDate::replaceBookingRoomNights($booking->id, $toRoom->id, $fromRoom->id, $period);
                }

                $booking->refresh();
                $roomsTotal = (float) $booking->bookingRooms()->sum('subtotal');
                $servicesTotal = (float) $booking->bookingServices()->get()->sum(static fn ($bs) => $bs->quantity * $bs->price);
                $surchargesTotal = (float) $booking->surcharges()->sum('amount');
                $discount = (float) ($booking->discount_amount ?? 0);
                $booking->update([
                    'total_price' => round(max(0.0, $roomsTotal + $servicesTotal + $surchargesTotal - $discount), 2),
                ]);

                $history->delete();
            });

            $booking->refresh();
            $booking->reconcilePaymentStatusWithPayments();

            try {
                InvoiceBookingSynchronizer::syncFullFromBooking($booking->fresh());
            } catch (\Throwable $e) {
                Log::warning('invoice_sync_after_room_change_revert_failed', [
                    'booking_id' => $booking->id,
                    'message' => $e->getMessage(),
                ]);
            }

            return redirect()->route($this->roomChangesRoutePrefix().'.room-changes.index')
                ->with('success', 'Đã hoàn tác đổi phòng thành công.');
        } catch (\Exception $e) {
            Log::error('Room change revert error: '.$e->getMessage());

            return back()->with('error', 'Có lỗi xảy ra khi hoàn tác: '.$e->getMessage());
        }
    }

}
