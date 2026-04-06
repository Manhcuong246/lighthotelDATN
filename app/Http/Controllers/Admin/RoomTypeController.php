<?php

namespace App\Http\Controllers\Admin;

use App\Models\RoomType;
use App\Models\Service;
use App\Support\RoomImageStorage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class RoomTypeController extends Controller
{
    // Danh sách loại phòng
    public function index(Request $request)
    {
        $query = RoomType::orderBy('id', 'desc');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where('name', 'like', "%{$q}%");
        }

        $roomTypes = $query->get();
        return view('admin.roomtypes.index', compact('roomTypes'));
    }

    // Form thêm
    public function create()
    {
        $services = Service::query()->orderBy('name')->get();

        return view('admin.roomtypes.create', compact('services'));
    }

    // Lưu loại phòng
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:room_types,name',
            'capacity' => 'required|integer|min:1',
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|boolean',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
        ]);

        if ($request->hasFile('image')) {
            RoomImageStorage::ensureDirectories();
            $validated['image'] = $request->file('image')->store(RoomImageStorage::roomTypesDir(), 'public');
        }

        $roomType = RoomType::create([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'beds' => $validated['beds'],
            'baths' => $validated['baths'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'image' => $validated['image'] ?? null,
            'status' => $validated['status'] ?? 1,
        ]);

        $serviceIds = array_values(array_unique(array_map('intval', $validated['service_ids'] ?? [])));
        $roomType->services()->sync($serviceIds);

        return redirect()->route('admin.roomtypes.index')
            ->with('success', 'Thêm loại phòng thành công');
    }

    // Form sửa
    public function edit($id)
    {
        $roomType = RoomType::with('services')->findOrFail($id);
        $services = Service::query()->orderBy('name')->get();

        return view('admin.roomtypes.edit', compact('roomType', 'services'));
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
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
        ]);

        if ($request->hasFile('image')) {
            if ($roomType->image) {
                Storage::disk('public')->delete($roomType->image);
            }
            RoomImageStorage::ensureDirectories();
            $validated['image'] = $request->file('image')->store(RoomImageStorage::roomTypesDir(), 'public');
        } else {
            unset($validated['image']);
        }

        $serviceIds = array_values(array_unique(array_map('intval', $validated['service_ids'] ?? [])));
        unset($validated['service_ids']);

        $roomType->update($validated);

        $roomType->services()->sync($serviceIds);

        return redirect()->route('admin.roomtypes.index')
            ->with('success', 'Cập nhật thành công');
    }

    // Xóa
    public function destroy($id)
    {
        $roomType = RoomType::findOrFail($id);

        $roomType->delete();

        return redirect()->route('admin.roomtypes.index')
            ->with('success', 'Đã ẩn loại phòng');
    }
}
