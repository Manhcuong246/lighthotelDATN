<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomAdminController extends Controller
{
    public function index()
    {
        $rooms = Room::with('roomType')->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới được thêm phòng mới.');
        }
        $roomTypes = RoomType::where('status', 1)->get();
        return view('admin.rooms.create', compact('roomTypes'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới được thêm phòng mới.');
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'room_number' => 'nullable|string|max:50',
            'room_type_id' => 'nullable|exists:room_types,id',
            'type' => 'nullable|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
            'area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:available,booked,maintenance',
        ]);

        // Nếu chọn room_type_id thì lấy thông tin từ room type
        if ($request->filled('room_type_id')) {
            $roomType = RoomType::find($request->room_type_id);
            if ($roomType) {
                $data['type'] = $roomType->name;
                $data['base_price'] = $data['base_price'] ?: $roomType->price;
                $data['max_guests'] = $data['max_guests'] ?: $roomType->capacity;
            }
        }

        Room::create($data);

        return redirect()->route('admin.rooms.index')->with('success', 'Thêm phòng mới thành công.');
    }

    public function edit(Room $room)
    {
        $roomTypes = RoomType::where('status', 1)->get();
        return view('admin.rooms.edit', compact('room', 'roomTypes'));
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'room_number' => 'nullable|string|max:50',
            'room_type_id' => 'nullable|exists:room_types,id',
            'type' => 'nullable|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
            'area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:available,booked,maintenance',
        ]);

        // Nếu chọn room_type_id thì lấy thông tin từ room type
        if ($request->filled('room_type_id')) {
            $roomType = RoomType::find($request->room_type_id);
            if ($roomType) {
                $data['type'] = $roomType->name;
                $data['base_price'] = $data['base_price'] ?: $roomType->price;
                $data['max_guests'] = $data['max_guests'] ?: $roomType->capacity;
            }
        }

        $room->update($data);

        return redirect()->route('admin.rooms.index')->with('success', 'Cập nhật phòng thành công.');
    }

    public function destroy(Room $room)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Chỉ quản trị viên mới được xóa phòng.');
        }
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


