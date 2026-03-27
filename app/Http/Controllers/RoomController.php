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

        $query = Room::with(['images', 'roomType', 'amenities'])->where('status', 'available');

        // Lọc theo loại phòng
        if ($request->filled('room_type')) {
            $roomTypeIds = is_array($request->room_type) ? $request->room_type : [$request->room_type];
            $query->whereIn('room_type_id', $roomTypeIds);
        }

        // Lọc theo khoảng giá
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        // Lọc theo tiện nghi
        if ($request->filled('amenities')) {
            $amenityIds = is_array($request->amenities) ? $request->amenities : [$request->amenities];
            $query->whereHas('amenities', function ($q) use ($amenityIds) {
                $q->whereIn('amenities.id', $amenityIds);
            });
        }

        // Lọc theo số lượng người
        if ($request->filled('adults') || $request->filled('children')) {
            $adults = (int) $request->input('adults', 1);
            $children = (int) $request->input('children', 0);

            $query->whereHas('roomType', function ($q) use ($adults, $children) {
                $q->where('adult_capacity', '>=', $adults)
                  ->where('child_capacity', '>=', $children);
            });
        }


        if ($request->filled('check_in') && $request->filled('check_out')) {
            $checkIn = $request->check_in;
            $checkOut = $request->check_out;
            $query->whereDoesntHave('bookedDates', function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('booked_date', [$checkIn, Carbon::parse($checkOut)->subDay()->toDateString()]);
            });
        }

        // Sắp xếp
        $sortBy = $request->input('sort_by', 'price_asc');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('base_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->orderBy('base_price', 'asc');
        }

        $rooms = $query->paginate(9)->withQueryString();

        // Lấy danh sách loại phòng và tiện nghi cho bộ lọc
        $roomTypes = RoomType::where('status', 'active')->orWhereNull('status')->get();
        $amenities = Amenity::orderBy('name')->get();

        return view('rooms.index', compact('hotel', 'rooms', 'roomTypes', 'amenities'));
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
}


