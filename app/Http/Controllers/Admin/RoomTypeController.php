<?php

namespace App\Http\Controllers\Admin;

use App\Models\RoomType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RoomTypeController extends Controller
{
    // Danh sách loại phòng
    public function index()
    {
        $roomTypes = RoomType::orderBy('id', 'desc')->get();
        return view('admin.roomtypes.index', compact('roomTypes'));
    }

    // Form thêm
    public function create()
    {
        return view('admin.roomtypes.create');
    }

    // Lưu loại phòng
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:room_types,name',
            'capacity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        RoomType::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'price' => $request->price,
            'description' => $request->description,
            'status' => $request->status ?? 1,
        ]);

        return redirect()->route('admin.roomtypes.index')
            ->with('success', 'Thêm loại phòng thành công');
    }

    // Form sửa
    public function edit($id)
    {
        $roomType = RoomType::findOrFail($id);
        return view('admin.roomtypes.edit', compact('roomType'));
    }

    // Cập nhật
    public function update(Request $request, $id)
    {
        $roomType = RoomType::findOrFail($id);

        $request->validate([
            'name' => 'required|max:255|unique:room_types,name,' . $roomType->id,
            'capacity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $roomType->update([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'price' => $request->price,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.roomtypes.index')
            ->with('success', 'Cập nhật thành công');
    }

    // Xóa
    public function destroy($id)
    {
        $roomType = RoomType::findOrFail($id);
        $roomType->delete();

        return redirect()->route('admin.roomtypes.index')
            ->with('success', 'Đã xóa loại phòng');
    }
}