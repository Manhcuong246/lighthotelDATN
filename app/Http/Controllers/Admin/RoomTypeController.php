<?php

namespace App\Http\Controllers\Admin;

use App\Models\RoomType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class RoomTypeController extends Controller
{
    // Danh sách loại phòng
    public function index()
    {
        $roomTypes = RoomType::orderBy('id', 'desc')->get();
        return view('admin.roomtypes.index', compact('roomTypes'));
    }

    // Hiển thị chi tiết loại phòng cho user booking
    public function show(RoomType $roomType)
    {
        // Đếm số phòng available của loại này
        $availableRoomsCount = $roomType->rooms()->where('status', 'available')->count();
        
        return view('roomtypes.show', compact('roomType', 'availableRoomsCount'));
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
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

         RoomType::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'beds' => $request->beds,
            'baths' => $request->baths,
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

        $validated = $request->validate([
            'name' => 'required|max:255|unique:room_types,name,' . $roomType->id,
            'capacity' => 'required|integer|min:1',
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu có
            if ($roomType->image) {
                Storage::disk('public')->delete($roomType->image);
            }
            $validated['image'] = $request->file('image')->store('room_types', 'public');
        }

        $roomType->update($validated);

        return redirect()->route('admin.roomtypes.index')
            ->with('success', 'Cập nhật thành công');
    }

    // Xóa
    public function destroy($id)
    {
        $roomType = RoomType::findOrFail($id);
        
        // Xóa ảnh nếu có
        if ($roomType->image) {
            Storage::disk('public')->delete($roomType->image);
        }
        
        $roomType->delete();

        return redirect()->route('admin.roomtypes.index')
            ->with('success', 'Đã xóa loại phòng');
    }
}