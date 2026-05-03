<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\HotelInfo;
use App\Models\Service;
use App\Models\RoomType;
use App\Models\BookingRoom;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RoomController extends Controller
{
    private function roomTypeIsActive($query)
    {
        // room_types.status is boolean in migration; allow NULL for backward-compat
        return $query->where(function ($q) {
            $q->where('status', true)->orWhereNull('status');
        });
    }

    public function index(Request $request)
    {
        // Chỉ validate khi có ý định tìm kiếm
        if ($request->has('search')) {
            $request->validate([
                'check_in' => 'required|date_format:Y-m-d|after_or_equal:today',
                'check_out' => 'required|date_format:Y-m-d|after:check_in',
                'rooms' => 'required|integer|min:1',
            ], [
                'check_in.required' => 'Vui lòng chọn ngày nhận phòng.',
                'check_out.required' => 'Vui lòng chọn ngày trả phòng.',
                'check_in.date_format' => 'Định dạng ngày nhận phòng không hợp lệ.',
                'check_out.date_format' => 'Định dạng ngày trả phòng không hợp lệ.',
                'check_in.after_or_equal' => 'Ngày nhận phòng phải từ hôm nay.',
                'check_out.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
                'rooms.required' => 'Vui lòng chọn số lượng phòng.',
                'rooms.min' => 'Số lượng phòng phải ít nhất là 1.',
            ]);
        }

        $hotel = HotelInfo::first();

        // Ảnh trưng bày: cần phòng có images; ưu tiên available nhưng vẫn lấy phòng khác nếu loại đó hết phòng trống.
        $query = RoomType::with(['rooms' => function ($q) {
            $q->with('images')
                ->excludeMaintenance()
                ->orderByRaw("CASE WHEN status = 'available' THEN 0 ELSE 1 END")
                ->orderBy('id');
        }, 'services']);

        $query = $this->roomTypeIsActive($query);

        // Lọc theo loại phòng
        if ($request->filled('room_type')) {
            $roomTypeIds = is_array($request->room_type) ? $request->room_type : [$request->room_type];
            $query->whereIn('id', $roomTypeIds);
        }

        // Lọc theo dịch vụ đi kèm (gắn với loại phòng — phải có đủ các dịch vụ đã chọn)
        if ($request->filled('included_services')) {
            $serviceIds = is_array($request->included_services)
                ? $request->included_services
                : [$request->included_services];
            $serviceIds = array_values(array_filter(array_map('intval', $serviceIds)));
            foreach ($serviceIds as $sid) {
                $query->whereHas('services', function ($q) use ($sid) {
                    $q->where('services.id', $sid);
                });
            }
        }

        // Lọc theo số lượng người
        if ($request->filled('adults') || $request->filled('children')) {
            $adults = (int) $request->input('adults', 1);
            $children = (int) $request->input('children', 0);
            $query->where('adult_capacity', '>=', $adults)
                  ->where('child_capacity', '>=', $children);
        }


        // Sắp xếp
        $sortBy = $request->input('sort_by', 'price_asc');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->orderBy('price', 'asc');
        }

        $roomTypesList = $query->paginate(10)->withQueryString();
        $catalogServices = Service::orderBy('name')->get();
        // Data cho dropdown + gallery ảnh phòng (eager load để tránh N+1)
        $allRoomTypes = $this->roomTypeIsActive(RoomType::query())
            ->with(['rooms' => function ($q) {
                $q->with('images')
                    ->excludeMaintenance()
                    ->orderByRaw("CASE WHEN status = 'available' THEN 0 ELSE 1 END")
                    ->orderBy('id');
            }, 'services'])
            ->orderBy('name')
            ->get();

        return view('rooms.index', compact('hotel', 'roomTypesList', 'allRoomTypes', 'catalogServices'));
    }

    public function show(Room $room)
    {
        if ($room->isInMaintenance() && ! $this->viewerMayAccessMaintenanceRoom($room)) {
            abort(404);
        }

        $room->load(['images', 'roomType', 'amenities']);

        $reviews = $room->reviews()
            ->with(['user', 'booking'])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->fragment('reviews');

        $reviewableBookings = collect();
        if (auth()->check()) {
            $reviewableBookings = Booking::reviewableBookingsForRoom((int) auth()->id(), (int) $room->id);
        }

        return view('rooms.show', compact('room', 'reviews', 'reviewableBookings'));
    }

    /**
     * Search for available rooms.
     */
    public function search(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ], [
            'check_in.required' => 'Vui lòng chọn ngày nhận phòng.',
            'check_out.required' => 'Vui lòng chọn ngày trả phòng.',
            'check_in.after_or_equal' => 'Ngày nhận phòng phải từ hôm nay.',
            'check_out.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
        ]);

        $checkIn = $request->check_in;
        $checkOut = $request->check_out;
        $roomsNeeded = max(1, (int) $request->input('rooms', 1));

        // 1) Tìm tất cả các phòng vật lý đang rảnh theo ngày
        $availableRoomsQuery = Room::query()
            ->where('status', 'available')
            ->excludeMaintenance()
            ->whereDoesntHave('bookedDates', function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('booked_date', [
                    $checkIn,
                    Carbon::parse($checkOut)->subDay()->toDateString()
                ]);
            })
            ->with(['amenities', 'images']);

        // 2) Filter theo loại phòng (room_type)
        if ($request->filled('room_type')) {
            $availableRoomsQuery->where('room_type_id', (int) $request->input('room_type'));
        }

        // 3) Filter theo dịch vụ đi kèm của loại phòng (included_services[] — AND)
        if ($request->filled('included_services')) {
            $serviceIds = (array) $request->input('included_services', []);
            $serviceIds = array_values(array_filter(array_map('intval', $serviceIds)));
            foreach ($serviceIds as $sid) {
                $availableRoomsQuery->whereHas('roomType.services', function ($q) use ($sid) {
                    $q->where('services.id', $sid);
                });
            }
        }

        $availableRooms = $availableRoomsQuery->get();

        $typeCounts = $availableRooms->groupBy('room_type_id')->map->count();
        $availableRoomTypeIds = collect();
        foreach ($typeCounts as $typeId => $physical) {
            if ($typeId === null || $typeId === '') {
                continue;
            }
            $tid = (int) $typeId;
            $unassigned = BookingRoom::unassignedCountForRoomTypeBetween($tid, $checkIn, $checkOut);
            $bookable = max(0, (int) $physical - $unassigned);
            if ($bookable >= $roomsNeeded) {
                $availableRoomTypeIds->push($tid);
            }
        }
        $availableRoomTypeIds = $availableRoomTypeIds->filter()->values();

        // 5) Query RoomType theo danh sách rảnh + filter khác (giá/sắp xếp)
        $roomTypesQuery = RoomType::query()->whereIn('id', $availableRoomTypeIds);
        $roomTypesQuery = $this->roomTypeIsActive($roomTypesQuery);

        // Filter giá theo RoomType.price (nếu có)
        if ($request->filled('min_price')) {
            $roomTypesQuery->where('price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $roomTypesQuery->where('price', '<=', (float) $request->input('max_price'));
        }

        // Sắp xếp
        $sortBy = $request->input('sort_by', 'price_asc');
        switch ($sortBy) {
            case 'price_desc':
                $roomTypesQuery->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $roomTypesQuery->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $roomTypesQuery->orderBy('name', 'desc');
                break;
            case 'price_asc':
            default:
                $roomTypesQuery->orderBy('price', 'asc');
                break;
        }

        // 6) Phân trang theo Loại phòng
        $roomTypes = $roomTypesQuery->with('services')->paginate(10)->withQueryString();

        // 7) Với mỗi loại phòng, gắn danh sách phòng vật lý đang rảnh của nó vào
        $roomTypes->each(function ($type) use ($availableRooms, $checkIn, $checkOut) {
            $rows = $availableRooms->where('room_type_id', $type->id)->values();
            $type->setRelation('available_rooms', $rows);
            $physical = $rows->count();
            $unassigned = BookingRoom::unassignedCountForRoomTypeBetween((int) $type->id, $checkIn, $checkOut);
            $type->setAttribute('bookable_slot_count', max(0, $physical - $unassigned));
        });

        $hotel = HotelInfo::first();

        return view('rooms.search', [
            'roomTypes' => $roomTypes,
            'hotel' => $hotel,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)),
            // Optional: provide filter datasets if we later extend search UI
            'allRoomTypes' => $this->roomTypeIsActive(RoomType::query())->orderBy('name')->get(),
            'catalogServices' => Service::orderBy('name')->get(),
        ]);
    }

    /**
     * Phòng bảo trì: admin/staff, hoặc khách có đơn (đang chờ hoặc đã xác nhận lưu trú) gắn phòng này.
     */
    private function viewerMayAccessMaintenanceRoom(Room $room): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->canAccessAdmin()) {
            return true;
        }

        return Booking::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'cancel_requested'])
            ->where(function ($q) use ($room) {
                $q->where('room_id', $room->id)
                    ->orWhereHas('bookingRooms', static fn ($br) => $br->where('room_id', $room->id));
            })
            ->exists();
    }
}


