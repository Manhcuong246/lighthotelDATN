<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\HotelInfo;
use App\Models\Service;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $hotel = HotelInfo::first();
        $rooms = Room::with('images')->where('status', 'available')->orderBy('base_price')->paginate(9);

        return view('rooms.index', compact('hotel', 'rooms'));
    }

    public function show(Room $room)
    {
        $room->load(['images', 'amenities', 'reviews.user']);
        
        // Lấy danh sách dịch vụ đi kèm
        $services = Service::orderBy('name')->get();

        return view('rooms.show', compact('room', 'services'));
    }
}


