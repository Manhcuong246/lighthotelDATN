<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class BookingGuest extends Model
{
    protected $table = 'booking_guests';

    private static ?bool $checkinStatusColumnExists = null;

    public static function checkinStatusColumnExists(): bool
    {
        if (self::$checkinStatusColumnExists === null) {
            self::$checkinStatusColumnExists = Schema::hasColumn((new static)->getTable(), 'checkin_status');
        }

        return self::$checkinStatusColumnExists;
    }

    /**
     * Bỏ các field không có cột tương ứng để tránh lỗi SQL giữa các môi trường.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function filterAttributesForStorage(array $attributes): array
    {
        if (! static::checkinStatusColumnExists()) {
            unset($attributes['checkin_status']);
        }

        return $attributes;
    }

    /** adult | child_0_5 | child_6_11 (tương thích: child → child_0_5). */
    public static function normalizeTypeForStorage(string $type): string
    {
        $t = strtolower(trim($type));

        if ($t === 'adult') {
            return 'adult';
        }

        if (in_array($t, ['child_6_11', 'child_611', 'child6-11', 'child-6-11'], true)) {
            return 'child_6_11';
        }

        if (in_array($t, ['child_0_5', 'child_05', 'child0-5', 'child-0-5'], true)) {
            return 'child_0_5';
        }

        if ($t === 'child') {
            return 'child_0_5';
        }

        if (str_contains($t, '6') && (str_contains($t, '11') || str_contains($t, '6-11'))) {
            return 'child_6_11';
        }

        if (str_starts_with($t, 'child')) {
            return 'child_0_5';
        }

        return 'adult';
    }

    public static function isAdultGuestType(string $type): bool
    {
        return self::normalizeTypeForStorage($type) === 'adult';
    }

    public static function typeLabel(?string $type): string
    {
        return match (self::normalizeTypeForStorage($type ?? 'adult')) {
            'adult' => 'Người lớn',
            'child_6_11' => 'Trẻ em 6–11 tuổi',
            'child_0_5' => 'Trẻ em 0–5 tuổi',
            default => 'Khách',
        };
    }

    protected $fillable = [
        'booking_id',
        'booking_room_id',
        'name',
        'cccd',
        'type',
        'status',
        'is_representative',
        'checkin_status',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingRoom()
    {
        return $this->belongsTo(BookingRoom::class);
    }

    /**
     * Lấy phòng của khách (thông qua booking_room)
     */
    public function room()
    {
        return $this->bookingRoom?->room();
    }

    /**
     * Kiểm tra khách đã được gán phòng chưa
     */
    public function hasAssignedRoom(): bool
    {
        return !is_null($this->booking_room_id);
    }
}
