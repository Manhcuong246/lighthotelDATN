<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\HotelInfo;
use App\Models\Service;
use App\Models\RoomBookedDate;
use App\Models\RoomType;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RoomController extends Controller
{
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

        $query = RoomType::with(['rooms' => function($q) {
            $q->where('status', 'available')->with('images');
        }]);

        // Lọc theo loại phòng
        if ($request->filled('room_type')) {
            $roomTypeIds = is_array($request->room_type) ? $request->room_type : [$request->room_type];
            $query->whereIn('id', $roomTypeIds);
        }

        // Lọc theo tiện nghi
        if ($request->filled('amenities')) {
            $amenityIds = is_array($request->amenities) ? $request->amenities : [$request->amenities];
            $query->whereHas('rooms.amenities', function ($q) use ($amenityIds) {
                $q->whereIn('amenities.id', $amenityIds);
            });
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
        $amenities = Amenity::orderBy('name')->get();
        // Cần truyền thêm $allRoomTypes cho bộ lọc (tránh trùng tên biến)
        $allRoomTypes = RoomType::where('status', 'active')->orWhereNull('status')->get();

        return view('rooms.index', compact('hotel', 'roomTypesList', 'allRoomTypes', 'amenities'));
    }

    public function show(Room $room)
    {
        $room->load(['images', 'roomType', 'amenities']);

        $reviews = $room->reviews()->with('user')->latest()->paginate(5)->withQueryString()->fragment('reviews');

        $bookedDates = RoomBookedDate::where('room_id', $room->id)
            ->where('booked_date', '>=', now()->toDateString())
            ->pluck('booked_date')
            ->map(fn ($d) => is_string($d) ? $d : Carbon::parse($d)->format('Y-m-d'))
            ->values()
            ->toArray();

        $services = Service::orderBy('name')->get();
        $hotelInfo = HotelInfo::first();

        return view('rooms.show', compact('room', 'reviews', 'services', 'hotelInfo', 'bookedDates'));
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

        // 1. Tìm tất cả các phòng vật lý đang rảnh
        $availableRooms = Room::where('status', 'available')
            ->whereDoesntHave('bookedDates', function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('booked_date', [
                    $checkIn,
                    Carbon::parse($checkOut)->subDay()->toDateString()
                ]);
            })
            ->get();

        // 2. Lấy danh sách ID các loại phòng có phòng rảnh
        $availableRoomTypeIds = $availableRooms->pluck('room_type_id')->unique()->filter();

        // 3. Phân trang theo Loại phòng
        $roomTypes = RoomType::whereIn('id', $availableRoomTypeIds)
            ->paginate(10);

        // 4. Với mỗi loại phòng, gắn danh sách phòng vật lý đang rảnh của nó vào
        $roomTypes->each(function($type) use ($availableRooms) {
            $type->setRelation('available_rooms', $availableRooms->where('room_type_id', $type->id));
        });

        // Debug log
        \Illuminate\Support\Facades\Log::info('Search Debug', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'availableRoomsCount' => $availableRooms->count(),
            'roomTypesCount' => $roomTypes->count(),
        ]);

        $hotel = HotelInfo::first();
        return view('rooms.search', [
            'roomTypes' => $roomTypes,
            'hotel' => $hotel,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut))
        ]);
    }
}


