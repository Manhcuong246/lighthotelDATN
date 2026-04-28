<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Admin\RoomChangeController as AdminRoomChangeController;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomChange;
use App\Models\BookingSurcharge;
use App\Models\BookingRoom;
use App\Models\RoomBookedDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomChangeController extends AdminRoomChangeController
{
    public function __construct()
    {
        // Do not apply admin middleware — staff routes already use 'auth' + 'staff' middleware
    }
    /**
     * Danh sách lịch sử đổi phòng (staff view)
     */
    public function index(Request $request)
    {
        $query = RoomChange::with(['booking.user', 'fromRoom.roomType', 'toRoom.roomType', 'changedBy'])->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('id', 'like', "%{$q}%")
                    ->orWhere('booking_id', 'like', "%{$q}%")
                    ->orWhereHas('fromRoom', fn($r) => $r->where('room_number', 'like', "%{$q}%"))
                    ->orWhereHas('toRoom', fn($r) => $r->where('room_number', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('price_direction')) {
            $dir = $request->price_direction;
            if ($dir === 'increase') $query->where('price_diff', '>', 0);
            elseif ($dir === 'decrease') $query->where('price_diff', '<', 0);
            elseif ($dir === 'same') $query->where('price_diff', 0);
        }

        if ($request->filled('change_type')) {
            $type = $request->change_type;
            if ($type === 'upgrade') $query->where('price_diff', '>', 0);
            elseif ($type === 'downgrade') $query->where('price_diff', '<', 0);
            elseif ($type === 'same_grade') $query->where('price_diff', 0);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $histories = $query->paginate(15);

        $stats = [
            'total' => RoomChange::count(),
            'today' => RoomChange::whereDate('created_at', Carbon::today())->count(),
            'upgrades' => RoomChange::where('price_diff', '>', 0)->count(),
            'downgrades' => RoomChange::where('price_diff', '<', 0)->count(),
            'total_price_increase' => RoomChange::where('price_diff', '>', 0)->sum('price_diff'),
            'total_price_decrease' => RoomChange::where('price_diff', '<', 0)->sum('price_diff'),
        ];

        return view('staff.room-changes.index', compact('histories', 'stats'));
    }

    /**
     * Chi tiết một lần đổi phòng (staff view)
     */
    public function show($id)
    {
        $history = RoomChange::with(['booking.user', 'fromRoom.roomType', 'toRoom.roomType', 'changedBy'])->findOrFail($id);

        $canRevert = $history->fromRoom && $history->fromRoom->status === 'available';
        $bookingHistories = RoomChange::where('booking_id', $history->booking_id)
            ->where('id', '!=', $history->id)
            ->latest()
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

            if (!$currentBookingRoom) {
                $currentBookingRoom = $booking->bookingRooms->first();
            }

            if (!$currentBookingRoom) {
                return back()->with('error', 'Không tìm thấy thông tin phòng cho đơn này.');
            }

            $remainingNights = $this->calculateRemainingNights($booking);
        }

        return view('staff.room-changes.create', compact('booking', 'currentBookingRoom', 'remainingNights'));
    }

    /**
     * Xử lý xác nhận đổi phòng (staff redirect)
     */
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'old_room_id' => 'required|exists:rooms,id',
            'new_room_id' => 'required|exists:rooms,id',
            'reason' => 'required|string',
            'other_reason' => 'nullable|string',
        ]);

        $booking = Booking::findOrFail($request->booking_id);
        $oldRoom = Room::findOrFail($request->old_room_id);
        $newRoom = Room::with('roomType')->findOrFail($request->new_room_id);

        $nightsRemaining = $this->calculateRemainingNights($booking);
        if ($nightsRemaining <= 0) $nightsRemaining = 1;

        $oldPrice = $request->old_price;
        $newPrice = $newRoom->roomType->price;
        $priceDiff = ($newPrice - $oldPrice) * $nightsRemaining;

        $adults = (int) $request->adults;
        $children = (int) $request->children;
        $standardCapacity = $newRoom->roomType->standard_capacity ?? $newRoom->roomType->capacity;

        $totalGuests = $adults + $children;
        $extraSurcharge = 0;

        if ($totalGuests > $standardCapacity) {
            $extraAdults = max(0, $adults - $standardCapacity);
            $remainingCapacityAfterAdults = max(0, $standardCapacity - $adults);
            $extraChildren = max(0, $children - $remainingCapacityAfterAdults);
            $extraSurcharge = ($extraAdults * 200000 + $extraChildren * 100000) * $nightsRemaining;
        }

        try {
            DB::transaction(function () use ($booking, $oldRoom, $newRoom, $request, $priceDiff, $extraSurcharge, $nightsRemaining) {
                $oldRoom->update(['status' => 'available']);
                $newRoom->update(['status' => 'occupied']);

                $bookingRoom = BookingRoom::where('booking_id', $booking->id)
                    ->where('room_id', $oldRoom->id)
                    ->first();

                if ($bookingRoom) {
                    $bookingRoom->update([
                        'room_id' => $newRoom->id,
                        'price_per_night' => $newRoom->roomType->price,
                    ]);
                }

                RoomBookedDate::where('booking_id', $booking->id)
                    ->where('room_id', $oldRoom->id)
                    ->where('booked_date', '>=', Carbon::now()->toDateString())
                    ->update(['room_id' => $newRoom->id]);

                RoomChange::create([
                    'booking_id' => $booking->id,
                    'from_room_id' => $oldRoom->id,
                    'to_room_id' => $newRoom->id,
                    'price_diff' => $priceDiff,
                    'surcharge_amount' => $extraSurcharge,
                    'reason' => $request->reason === 'Khác' ? $request->other_reason : $request->reason,
                    'changed_by' => auth()->id(),
                ]);

                if ($priceDiff != 0) {
                    BookingSurcharge::create([
                        'booking_id' => $booking->id,
                        'reason' => "Chênh lệch đổi phòng (" . $oldRoom->room_number . " -> " . $newRoom->room_number . ")",
                        'quantity' => 1,
                        'amount' => $priceDiff,
                    ]);
                }

                if ($extraSurcharge > 0) {
                    BookingSurcharge::create([
                        'booking_id' => $booking->id,
                        'reason' => "Phụ thu vượt sức chứa phòng mới (" . $newRoom->room_number . ")",
                        'quantity' => 1,
                        'amount' => $extraSurcharge,
                    ]);
                }

                $booking->total_price += ($priceDiff + $extraSurcharge);
                $booking->save();
            });

            return redirect()->route('staff.room-changes.index')
                ->with('success', 'Đổi phòng thành công từ ' . $oldRoom->room_number . ' sang ' . $newRoom->room_number);

        } catch (\Exception $e) {
            Log::error("Room change error: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Hoàn tác đổi phòng (staff redirect)
     */
    public function revert(Request $request, $id)
    {
        $history = RoomChange::findOrFail($id);
        $booking = Booking::findOrFail($history->booking_id);

        $fromRoom = Room::findOrFail($history->from_room_id);
        if ($fromRoom->status !== 'available') {
            return back()->with('error', 'Không thể hoàn tác vì phòng cũ (' . $fromRoom->room_number . ') không còn trống.');
        }

        try {
            DB::transaction(function () use ($booking, $history, $fromRoom) {
                $toRoom = Room::findOrFail($history->to_room_id);

                $toRoom->update(['status' => 'available']);
                $fromRoom->update(['status' => 'occupied']);

                $bookingRoom = BookingRoom::where('booking_id', $booking->id)
                    ->where('room_id', $toRoom->id)
                    ->first();

                if ($bookingRoom) {
                    $bookingRoom->update([
                        'room_id' => $fromRoom->id,
                        'price_per_night' => $fromRoom->roomType->price,
                    ]);
                }

                RoomBookedDate::where('booking_id', $booking->id)
                    ->where('room_id', $toRoom->id)
                    ->where('booked_date', '>=', Carbon::now()->toDateString())
                    ->update(['room_id' => $fromRoom->id]);

                $booking->total_price -= ($history->price_diff + $history->surcharge_amount);
                $booking->save();

                BookingSurcharge::create([
                    'booking_id' => $booking->id,
                    'reason' => "Hoàn tác đổi phòng (" . $toRoom->room_number . " -> " . $fromRoom->room_number . ")",
                    'quantity' => 1,
                    'amount' => -($history->price_diff + $history->surcharge_amount),
                ]);

                $history->delete();
            });

            return redirect()->route('staff.room-changes.index')
                ->with('success', 'Đã hoàn tác đổi phòng thành công.');

        } catch (\Exception $e) {
            Log::error("Room change revert error: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi hoàn tác: ' . $e->getMessage());
        }
    }
}
