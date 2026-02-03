<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomAdminController extends Controller
{
    public function index()
    {
        $rooms = Room::orderBy('created_at', 'desc')->paginate(15);

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('admin.rooms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
            'area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:available,booked,maintenance',
        ]);

        Room::create($data);

        return redirect()->route('admin.rooms.index')->with('success', 'Thêm phòng mới thành công.');
    }

    public function edit(Room $room)
    {
        return view('admin.rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
            'area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:available,booked,maintenance',
        ]);

        $room->update($data);

        return redirect()->route('admin.rooms.index')->with('success', 'Cập nhật phòng thành công.');
    }

    public function destroy(Room $room)
    {
        // Kiểm tra có booking đang hoạt động không
        $activeBookings = $room->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        if ($activeBookings > 0) {
            return redirect()->route('admin.rooms.index')
                ->with('error', 'Không thể xóa phòng có ' . $activeBookings . ' booking đang hoạt động!');
        }

        $room->delete();

        return redirect()->route('admin.rooms.index')->with('success', 'Xóa phòng thành công.');
    }
}


