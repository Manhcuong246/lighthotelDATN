<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\HotelInfo;
use App\Models\Service;
use App\Models\RoomBookedDate;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $hotel = HotelInfo::first();
        $rooms = Room::with(['images', 'roomType'])->where('status', 'available')->orderBy('base_price')->paginate(9);

        return view('rooms.index', compact('hotel', 'rooms'));
    }

    public function show(Room $room)
    {
        $room->load(['images', 'roomType', 'amenities']);

        $reviews = $room->reviews()->with('user')->latest()->paginate(5)->withQueryString()->fragment('reviews');

        $bookedDates = RoomBookedDate::where('room_id', $room->id)
            ->where('booked_date', '>=', now()->toDateString())
            ->pluck('booked_date')
            ->map(fn ($d) => is_string($d) ? $d : \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->values()
            ->toArray();

        $services = Service::orderBy('name')->get();
        $hotelInfo = HotelInfo::first();

        return view('rooms.show', compact('room', 'reviews', 'services', 'hotelInfo', 'bookedDates'));
    }
}


