<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

/**
 * Đảm bảo mỗi loại phòng có đủ phòng vật lý để đặt (inventory).
 * Chạy lại an toàn: chỉ thêm phòng khi count &lt; ngưỡng.
 */
class ExpandRoomsPerRoomTypeSeeder extends Seeder
{
    /** Số phòng tối thiểu cho mỗi room_type_id */
    private const MIN_ROOMS_PER_TYPE = 18;

    public function run(): void
    {
        $types = RoomType::query()->orderBy('id')->get();

        foreach ($types as $type) {
            $current = Room::query()->where('room_type_id', $type->id)->count();
            $need = max(0, self::MIN_ROOMS_PER_TYPE - $current);
            if ($need === 0) {
                continue;
            }

            $template = Room::query()->where('room_type_id', $type->id)->first();

            $basePrice = $template->base_price ?? $type->price ?? 1_000_000;
            $maxGuests = $template->max_guests
                ?? $type->standard_capacity
                ?? $type->capacity
                ?? 2;
            $beds = $template->beds ?? $type->beds ?? 1;
            $baths = $template->baths ?? $type->baths ?? 1;
            $area = $template->area ?? 30;
            $legacyType = $template->type ?? mb_substr($type->name, 0, 100);

            $desc = $template->description ?? null;
            if ($desc === null && filled($type->description)) {
                $desc = mb_strlen($type->description) > 240
                    ? mb_substr($type->description, 0, 237).'…'
                    : $type->description;
            }

            for ($i = 1; $i <= $need; $i++) {
                $idx = $current + $i;
                $roomNumber = sprintf('T%d-%03d', $type->id, $idx);

                Room::firstOrCreate(
                    ['room_number' => $roomNumber],
                    [
                        'name' => $type->name.' · '.$roomNumber,
                        'type' => $legacyType,
                        'base_price' => $basePrice,
                        'max_guests' => $maxGuests,
                        'beds' => $beds,
                        'baths' => $baths,
                        'area' => $area,
                        'description' => $desc ?? 'Phòng thuộc loại '.$type->name.'.',
                        'status' => 'available',
                        'room_type_id' => $type->id,
                    ]
                );
            }

            $this->command?->info(sprintf(
                'Loại #%d %s: đã đảm bảo tối thiểu %d phòng (+ %d mới).',
                $type->id,
                $type->name,
                self::MIN_ROOMS_PER_TYPE,
                $need
            ));
        }
    }
}
