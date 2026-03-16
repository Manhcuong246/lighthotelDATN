<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\HotelInfo;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $hotel = HotelInfo::first();
        // Lấy danh sách loại phòng thay vì phòng cụ thể
        $roomTypes = RoomType::where('status', 1)
            ->orderBy('price', 'asc')
            ->paginate(9);
        
        // Đếm số phòng available cho mỗi loại
        foreach ($roomTypes as $type) {
            $type->available_rooms_count = $type->rooms()->where('status', 'available')->count();
        }

        return view('rooms.index', compact('hotel', 'roomTypes'));
    }
}


