<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomChange;
use App\Models\BookingSurcharge;
use App\Models\BookingRoom;
use App\Models\RoomBookedDate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomChangeController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Danh sách lịch sử đổi phòng
     */
    public function index(Request $request)
    {
        $query = RoomChange::with(['booking.user', 'fromRoom.roomType', 'toRoom.roomType', 'changedBy'])->latest();

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
            if ($dir === 'increase') $query->where('price_diff', '>', 0);
            elseif ($dir === 'decrease') $query->where('price_diff', '<', 0);
            elseif ($dir === 'same') $query->where('price_diff', 0);
        }

        // Lọc theo loại đổi phòng (Mapping từ price_diff)
        if ($request->filled('change_type')) {
            $type = $request->change_type;
            if ($type === 'upgrade') $query->where('price_diff', '>', 0);
            elseif ($type === 'downgrade') $query->where('price_diff', '<', 0);
            elseif ($type === 'same_grade') $query->where('price_diff', 0);
            // emergency chưa hỗ trợ lưu field riêng, tạm coi như cùng hạng hoặc bỏ qua
        }

        // Lọc theo ngày
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $histories = $query->paginate(15);

        // Thống kê cơ bản
        $stats = [
            'total' => RoomChange::count(),
            'today' => RoomChange::whereDate('created_at', Carbon::today())->count(),
            'upgrades' => RoomChange::where('price_diff', '>', 0)->count(),
            'downgrades' => RoomChange::where('price_diff', '<', 0)->count(),
            'total_price_increase' => RoomChange::where('price_diff', '>', 0)->sum('price_diff'),
            'total_price_decrease' => RoomChange::where('price_diff', '<', 0)->sum('price_diff'),
        ];

        return view('admin.room-changes.index', compact('histories', 'stats'));
    }

    /**
     * Chi tiết một lần đổi phòng
     */
    public function show($id)
    {
        $history = RoomChange::with(['booking.user', 'fromRoom.roomType', 'toRoom.roomType', 'changedBy'])->findOrFail($id);
        
        $canRevert = $history->fromRoom && $history->fromRoom->status === 'available';
        $bookingHistories = RoomChange::where('booking_id', $history->booking_id)
            ->where('id', '!=', $history->id)
            ->latest()
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

            $remainingNights = $this->calculateRemainingNights($booking);
        }

        return view('admin.room-changes.create', compact('booking', 'currentBookingRoom', 'remainingNights'));
    }

    /**
     * API: Tìm kiếm booking đang check-in
     */
    public function searchBooking(Request $request)
    {
        try {
            $q = $request->q;
            \Log::info("RoomChange Search - Query: " . ($q ?: 'Empty'));
            
            $query = Booking::with(['user', 'bookingRooms.room'])->where('status', 'checked_in');

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
                    $roomNumber = optional($br->room)->room_number ?? 'N/A';
                    $roomType = optional($br->room->roomType)->name ?? 'N/A';
                    
                    $bookings[] = [
                        'id' => $b->id,
                        'room_id' => $br->room_id,
                        'guest_name' => ($b->user->full_name ?? 'Khách lẻ') . " (Phòng $roomNumber)",
                        'phone' => $b->user->phone ?? 'N/A',
                        'room_number' => $roomNumber,
                        'room_type' => $roomType,
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
     * API: Lấy danh sách phòng trống đủ sức chứa
     */
    public function getAvailableRooms(Request $request)
    {
        $booking = Booking::findOrFail($request->booking_id);
        $totalAdults = (int) $request->total_adults;
        $totalChildren = (int) $request->total_children;

        // Lấy danh sách phòng đang bận trong khoảng thời gian còn lại
        $bookedRoomIds = RoomBookedDate::whereBetween('booked_date', [
                Carbon::now()->toDateString(),
                Carbon::parse($booking->check_out)->subDay()->toDateString()
            ])
            ->where('booking_id', '!=', $booking->id)
            ->distinct()->pluck('room_id');

        // Lọc phòng: Trống và không nằm trong danh sách bận
        $rooms = Room::with('roomType')
            ->where('status', 'available')
            ->whereNotIn('id', $bookedRoomIds)
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number ?? $room->name ?? 'N/A',
                    'room_type' => $room->roomType->name ?? 'N/A',
                    'price' => $room->roomType->price ?? 0,
                    'capacity' => $room->roomType->capacity ?? 0,
                    'standard_capacity' => $room->roomType->standard_capacity ?? $room->roomType->capacity ?? 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    /**
     * Xử lý xác nhận đổi phòng
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
        if ($nightsRemaining <= 0) $nightsRemaining = 1; // Tối thiểu tính 1 đêm nếu đổi trong ngày

        // Tính chênh lệch giá phòng
        $oldPrice = $request->old_price;
        $newPrice = $newRoom->roomType->price;
        $priceDiff = ($newPrice - $oldPrice) * $nightsRemaining;

        // Tính phụ thu người thêm
        // Logic: Giả sử phòng mới có standard_capacity. Nếu số người vượt quá -> tính phụ thu.
        $adults = (int) $request->adults;
        $children = (int) $request->children;
        $standardCapacity = $newRoom->roomType->standard_capacity ?? $newRoom->roomType->capacity;
        
        $totalGuests = $adults + $children;
        $extraSurcharge = 0;
        
        if ($totalGuests > $standardCapacity) {
            // Tính số người vượt mức (Ưu tiên người lớn tính vào standard capacity, sau đó mới tính phụ thu)
            // Ví dụ: Standard 2, Khách 3 (2 người lớn, 1 trẻ em) -> 1 trẻ em dư.
            // Nếu Khách 3 (3 người lớn) -> 1 người lớn dư.
            $extraAdults = max(0, $adults - $standardCapacity);
            $remainingCapacityAfterAdults = max(0, $standardCapacity - $adults);
            $extraChildren = max(0, $children - $remainingCapacityAfterAdults);
            
            $extraSurcharge = ($extraAdults * 200000 + $extraChildren * 100000) * $nightsRemaining;
        }

        try {
            DB::transaction(function () use ($booking, $oldRoom, $newRoom, $request, $priceDiff, $extraSurcharge, $nightsRemaining) {
                // 1. Cập nhật trạng thái phòng
                $oldRoom->update(['status' => 'available']); // Phòng cũ trống (hoặc cleaning nếu bạn muốn)
                $newRoom->update(['status' => 'occupied']);

                // 2. Cập nhật BookingRoom
                $bookingRoom = BookingRoom::where('booking_id', $booking->id)
                    ->where('room_id', $oldRoom->id)
                    ->first();
                
                if ($bookingRoom) {
                    $bookingRoom->update([
                        'room_id' => $newRoom->id,
                        'price_per_night' => $newRoom->roomType->price,
                        // Lưu ý: subtotal gốc có thể cần cập nhật nếu bạn muốn tính lại tổng booking
                    ]);
                }

                // 3. Cập nhật RoomBookedDate (để lịch hiển thị đúng)
                RoomBookedDate::where('booking_id', $booking->id)
                    ->where('room_id', $oldRoom->id)
                    ->where('booked_date', '>=', Carbon::now()->toDateString())
                    ->update(['room_id' => $newRoom->id]);

                // 4. Lưu lịch sử đổi phòng
                RoomChange::create([
                    'booking_id' => $booking->id,
                    'from_room_id' => $oldRoom->id,
                    'to_room_id' => $newRoom->id,
                    'price_diff' => $priceDiff,
                    'surcharge_amount' => $extraSurcharge,
                    'reason' => $request->reason === 'Khác' ? $request->other_reason : $request->reason,
                    'changed_by' => auth()->id(),
                ]);

                // 5. Tích hợp phụ thu vào Hóa đơn (thông qua BookingSurcharge)
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

                // 6. Cập nhật tổng tiền của Booking
                $booking->total_price += ($priceDiff + $extraSurcharge);
                $booking->save();
            });

            return redirect()->route('admin.bookings.show', $booking->id)
                ->with('success', 'Đổi phòng thành công từ ' . $oldRoom->room_number . ' sang ' . $newRoom->room_number);

        } catch (\Exception $e) {
            Log::error("Room change error: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Hoàn tác đổi phòng
     */
    public function revert(Request $request, $id)
    {
        $history = RoomChange::findOrFail($id);
        $booking = Booking::findOrFail($history->booking_id);
        
        // Kiểm tra phòng cũ có còn trống không
        $fromRoom = Room::findOrFail($history->from_room_id);
        if ($fromRoom->status !== 'available') {
            return back()->with('error', 'Không thể hoàn tác vì phòng cũ (' . $fromRoom->room_number . ') không còn trống.');
        }

        try {
            DB::transaction(function () use ($booking, $history, $fromRoom) {
                $toRoom = Room::findOrFail($history->to_room_id);

                // 1. Cập nhật trạng thái phòng
                $toRoom->update(['status' => 'available']);
                $fromRoom->update(['status' => 'occupied']);

                // 2. Cập nhật BookingRoom
                $bookingRoom = BookingRoom::where('booking_id', $booking->id)
                    ->where('room_id', $toRoom->id)
                    ->first();
                
                if ($bookingRoom) {
                    $bookingRoom->update([
                        'room_id' => $fromRoom->id,
                        'price_per_night' => $fromRoom->roomType->price,
                    ]);
                }

                // 3. Cập nhật RoomBookedDate
                RoomBookedDate::where('booking_id', $booking->id)
                    ->where('room_id', $toRoom->id)
                    ->where('booked_date', '>=', Carbon::now()->toDateString())
                    ->update(['room_id' => $fromRoom->id]);

                // 4. Hoàn trả tiền (ngược lại với lúc đổi)
                $booking->total_price -= ($history->price_diff + $history->surcharge_amount);
                $booking->save();

                // 5. Thêm phụ phí âm để cân bằng tài chính
                BookingSurcharge::create([
                    'booking_id' => $booking->id,
                    'reason' => "Hoàn tác đổi phòng (" . $toRoom->room_number . " -> " . $fromRoom->room_number . ")",
                    'quantity' => 1,
                    'amount' => -($history->price_diff + $history->surcharge_amount),
                ]);

                // 6. Xóa bản ghi lịch sử đổi phòng này
                $history->delete();
            });

            return redirect()->route('admin.bookings.show', $booking->id)
                ->with('success', 'Đã hoàn tác đổi phòng thành công.');

        } catch (\Exception $e) {
            Log::error("Room change revert error: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi hoàn tác: ' . $e->getMessage());
        }
    }

    private function calculateRemainingNights($booking)
    {
        $now = Carbon::now()->startOfDay();
        $checkOut = Carbon::parse($booking->check_out)->startOfDay();
        
        if ($now >= $checkOut) return 0;
        
        return $now->diffInDays($checkOut);
    }
}
