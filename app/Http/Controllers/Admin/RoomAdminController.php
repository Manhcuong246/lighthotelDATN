<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RoomAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with(['roomType', 'images'])->orderBy('created_at', 'desc');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('room_number', 'like', "%{$q}%")
                    ->orWhere('type', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rooms = $query->paginate(10)->withQueryString();

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        // `admin` middleware đã cho phép cả admin và staff vào khu /admin
        // nên ở đây chỉ chặn nếu không thuộc nhóm có quyền.
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Bạn không có quyền thêm phòng.');
        }
        $roomTypes = RoomType::where('status', 1)->get();
        return view('admin.rooms.create', compact('roomTypes'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Bạn không có quyền thêm phòng.');
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
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        // Nếu chọn room_type_id thì lấy thông tin từ room type
        if ($request->filled('room_type_id')) {
            $roomType = RoomType::find($request->room_type_id);
            if ($roomType) {
                $data['type'] = $roomType->name;
                $data['base_price'] = $roomType->price; // Luôn lấy theo loại phòng
                $data['max_guests'] = $data['max_guests'] ?: $roomType->capacity;
            }
        }

        unset($data['images']);
        $room = Room::create($data);

        $this->saveRoomImages($room, $request->file('images'));

        return redirect()->route('admin.rooms.index')->with('success', 'Thêm phòng mới thành công.');
    }

    public function edit(Room $room)
    {
        $room->load('images');
        $roomTypes = RoomType::where('status', 1)->get();
        return view('admin.rooms.edit', compact('room', 'roomTypes'));
    }

    public function update(Request $request, Room $room)
    {
        // Admin và staff đều có thể cập nhật phòng.
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'room_number' => 'nullable|string|max:50',
            'room_type_id' => 'nullable|exists:room_types,id',
            'type' => 'nullable|string|max:100',
            'base_price' => 'nullable|numeric|min:0', // Để trống để lấy theo loại
            'max_guests' => 'required|integer|min:1',
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
            'area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:available,booked,maintenance',
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer|exists:images,id',
        ]);

        $removeIds = $data['remove_images'] ?? [];
        $newFilesLabels = $request->file('images') ?: [];

        // Logic check số lượng ảnh (tối đa 4)
        $existingCount = $room->images()->count();
        $actualRemoveCount = Image::where('room_id', $room->id)->whereIn('id', $removeIds)->count();
        $finalCount = $existingCount - $actualRemoveCount + count($newFilesLabels);

        if ($finalCount > 4) {
            throw ValidationException::withMessages([
                'images' => 'Mỗi phòng chỉ được phép tối đa 4 ảnh. Hiện tại bạn đang định giữ lại ' . ($existingCount - $actualRemoveCount) . ' ảnh cũ và thêm ' . count($newFilesLabels) . ' ảnh mới.',
            ]);
        }

        // Lấy thông tin từ Room Type nếu có
        if ($request->filled('room_type_id')) {
            $roomType = RoomType::find($request->room_type_id);
            if ($roomType) {
                $data['type'] = $roomType->name;
                $data['base_price'] = $roomType->price; // Ép giá theo loại phòng
            }
        }

        // Xóa ảnh cũ trước khi thêm mới
        if (!empty($removeIds)) {
            $toRemove = Image::where('room_id', $room->id)->whereIn('id', $removeIds)->get();
            foreach ($toRemove as $img) {
                if ($img->image_url && !str_starts_with($img->image_url, 'http')) {
                    Storage::disk('public')->delete($img->image_url);
                }
                $img->delete();
            }
        }

        // Thêm ảnh mới
        $this->saveRoomImages($room, $request->file('images'));
        
        // Cập nhật thông tin cơ bản của phòng
        unset($data['images'], $data['remove_images']);
        $room->update($data);

        // Đảm bảo có ảnh chính
        $this->ensureRoomPrimaryImage($room);

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

    private function saveRoomImages(Room $room, ?array $files): void
    {
        if (empty($files)) {
            return;
        }

        Storage::disk('public')->makeDirectory('rooms');

        foreach ($files as $file) {
            if (! $file->isValid()) {
                continue;
            }
            $path = $file->store('rooms', 'public');
            Image::create([
                'room_id' => $room->id,
                'image_url' => $path,
                'image_type' => 'room',
            ]);
        }

        $this->ensureRoomPrimaryImage($room);
    }

    private function ensureRoomPrimaryImage(Room $room): void
    {
        $firstImage = Image::where('room_id', $room->id)->first();
        $room->update(['image' => $firstImage?->image_url]);
    }
}


