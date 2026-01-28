<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\HotelInfo;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    // Danh sách phòng cho khách
    public function index()
    {
        $hotel = HotelInfo::first();
        $rooms = Room::with('images')->where('status', 'available')->orderBy('base_price')->paginate(9);

        return view('rooms.index', compact('hotel', 'rooms'));
    }

    // Chi tiết phòng
    public function show(Room $room)
    {
        $room->load(['images', 'amenities', 'reviews.user']);

        return view('rooms.show', compact('room'));
    }
}


