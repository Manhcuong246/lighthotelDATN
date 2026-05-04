<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\RoomImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('only_admin')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $query = Room::with('roomType')->orderBy('created_at', 'desc');

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

        $rooms = $query->paginate(15)->withQueryString();

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        // `admin` middleware đã cho phép cả admin và staff vào khu /admin
        // nên ở đây chỉ chặn nếu không thuộc nhóm có quyền.
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Bạn không có quyền thêm phòng.');
        }
        $roomTypes = RoomType::query()->where('status', 1)->whereNull('deleted_at')->orderBy('name')->get();
        return view('admin.rooms.create', compact('roomTypes'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Bạn không có quyền thêm phòng.');
        }
        $hasType = $request->filled('room_type_id');
        $data = $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'room_number' => 'nullable|string|max:50',
            'room_type_id' => [
                'nullable',
                Rule::exists('room_types', 'id')->whereNull('deleted_at'),
            ],
            'type' => 'nullable|string|max:100',
            'area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:available,booked,maintenance',
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp',
        ], $hasType ? [
            'base_price' => 'nullable|numeric|min:0',
            'max_guests' => 'nullable|integer|min:1',
            'beds' => 'nullable|integer|min:1',
            'baths' => 'nullable|integer|min:0',
        ] : [
            'base_price' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
        ]));

        $data = $this->mergeCatalogueFieldsFromRoomType($data, $data['room_type_id'] ?? null);
        if ($request->filled('room_type_id') && empty($data['room_type_id'])) {
            return back()->withErrors(['room_type_id' => 'Loại phòng không hợp lệ hoặc đã bị ẩn.'])->withInput();
        }

        unset($data['images']);
        $room = Room::create($data);

        $this->saveRoomImages($room, $request->file('images'));

        return redirect()->route('admin.rooms.index')->with('success', 'Thêm phòng mới thành công.');
    }

    public function edit(Room $room)
    {
        $room->load('images');
        $roomTypes = RoomType::query()->where('status', 1)->whereNull('deleted_at')->orderBy('name')->get();
        return view('admin.rooms.edit', compact('room', 'roomTypes'));
    }

    public function update(Request $request, Room $room)
    {
        // Admin và staff đều có thể cập nhật phòng.
        $hasType = $request->filled('room_type_id');
        $data = $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'room_number' => 'nullable|string|max:50',
            'room_type_id' => [
                'nullable',
                Rule::exists('room_types', 'id')->whereNull('deleted_at'),
            ],
            'type' => 'nullable|string|max:100',
            'area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:available,booked,maintenance',
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer|exists:images,id',
        ], $hasType ? [
            'base_price' => 'nullable|numeric|min:0',
            'max_guests' => 'nullable|integer|min:1',
            'beds' => 'nullable|integer|min:1',
            'baths' => 'nullable|integer|min:0',
        ] : [
            'base_price' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'beds' => 'required|integer|min:1',
            'baths' => 'required|integer|min:0',
        ]));

        $removeIds = $data['remove_images'] ?? [];
        $newFilesLabels = $request->file('images') ?: [];

        // Logic check số lượng ảnh (tối đa 4)
        $existingCount = $room->images()->count();
        $actualRemoveCount = $room->images()->whereIn('id', $removeIds)->count();
        $finalCount = $existingCount - $actualRemoveCount + count($newFilesLabels);

        if ($finalCount > 4) {
            throw ValidationException::withMessages([
                'images' => 'Mỗi phòng chỉ được phép tối đa 4 ảnh. Hiện tại bạn đang định giữ lại ' . ($existingCount - $actualRemoveCount) . ' ảnh cũ và thêm ' . count($newFilesLabels) . ' ảnh mới.',
            ]);
        }

        $data = $this->mergeCatalogueFieldsFromRoomType($data, $data['room_type_id'] ?? null);
        if ($request->filled('room_type_id') && empty($data['room_type_id'])) {
            return back()->withErrors(['room_type_id' => 'Loại phòng không hợp lệ hoặc đã bị ẩn.'])->withInput();
        }

        // Xóa ảnh cũ trước khi thêm mới
        if (!empty($removeIds)) {
            $toRemove = $room->images()->whereIn('id', $removeIds)->get();
            foreach ($toRemove as $img) {
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

        RoomImageStorage::ensureDirectories();

        foreach ($files as $file) {
            if (! $file->isValid()) {
                continue;
            }
            $path = $file->store(RoomImageStorage::roomsDir(), 'public');
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
        $firstImage = $room->images()->orderBy('id')->first();
        $room->update(['image' => $firstImage?->image_url]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeCatalogueFieldsFromRoomType(array $data, mixed $roomTypeId): array
    {
        $id = $roomTypeId !== null && $roomTypeId !== '' ? (int) $roomTypeId : null;
        if (! $id) {
            return $data;
        }

        $roomType = RoomType::query()->whereNull('deleted_at')->find($id);
        if (! $roomType) {
            $data['room_type_id'] = null;

            return $data;
        }

        $data['room_type_id'] = $roomType->id;
        $data['type'] = $roomType->name;
        $data['base_price'] = $roomType->price;
        $data['max_guests'] = (int) ($roomType->capacity ?? 1);
        $data['beds'] = (int) ($roomType->beds ?? 1);
        $data['baths'] = (int) ($roomType->baths ?? 0);

        return $data;
    }
}


