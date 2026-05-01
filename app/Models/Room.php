<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $room_number
 * @property string|null $type
 * @property float       $base_price
 * @property int         $max_guests
 * @property int         $beds
 * @property int         $baths
 * @property float|null  $area
 * @property string|null $description
 * @property string      $status
 * @property int|null    $room_type_id
 * @property string|null $image
 * @property string|null $maintenance_note
 * @property \Carbon\Carbon|null $maintenance_since
 * @property int|null    $damage_report_id
 */
class Room extends Model
{
    use SoftDeletes;

    /**
     * Không đăng ký SoftDeletes scope khi bảng chưa có deleted_at (migration chưa chạy đồng bộ).
     */
    public static function bootSoftDeletes(): void
    {
        if (! Schema::hasColumn((new static)->getTable(), 'deleted_at')) {
            return;
        }

        static::addGlobalScope(new SoftDeletingScope);
    }

    protected $table = 'rooms';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'base_price',
        'max_guests',
        'beds',
        'baths',
        'area',
        'description',
        'status',
        'room_type_id',
        'room_number',
        'image',
        'maintenance_note',
        'maintenance_since',
        'damage_report_id',
    ];

    protected $casts = [
        'maintenance_since' => 'datetime',
    ];

    public function prices()
    {
        return $this->hasMany(RoomPrice::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'room_amenities');
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_rooms')
                    ->withPivot('price_per_night', 'nights', 'subtotal')
                    ->withTimestamps();
    }

    public function bookedDates()
    {
        return $this->hasMany(RoomBookedDate::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Sức chứa hiển thị / nghiệp vụ: ưu tiên loại phòng khi phòng gắn room_type_id.
     */
    public function catalogueMaxGuests(): int
    {
        $this->loadMissing('roomType');
        if ($this->room_type_id && $this->roomType) {
            return (int) ($this->roomType->capacity ?? $this->max_guests ?? 1);
        }

        return (int) ($this->max_guests ?? 1);
    }

    public function catalogueBasePrice(): float
    {
        $this->loadMissing('roomType');
        if ($this->room_type_id && $this->roomType) {
            return (float) ($this->roomType->price ?? $this->base_price ?? 0);
        }

        return (float) ($this->base_price ?? 0);
    }

    public function catalogueBeds(): int
    {
        $this->loadMissing('roomType');
        if ($this->room_type_id && $this->roomType) {
            return (int) ($this->roomType->beds ?? $this->beds ?? 1);
        }

        return (int) ($this->beds ?? 1);
    }

    public function catalogueBaths(): int
    {
        $this->loadMissing('roomType');
        if ($this->room_type_id && $this->roomType) {
            return (int) ($this->roomType->baths ?? $this->baths ?? 0);
        }

        return (int) ($this->baths ?? 0);
    }

    public function damageReports()
    {
        return $this->hasMany(DamageReport::class);
    }

    public function activeDamageReport()
    {
        return $this->belongsTo(DamageReport::class, 'damage_report_id');
    }

    /**
     * Human-readable label for use in messages / logs.
     * E.g. "101 - Deluxe" or "Deluxe" or "Phòng #5"
     */
    public function displayLabel(): string
    {
        $parts = array_filter([
            $this->room_number ?? null,
            $this->roomType?->name ?? ($this->name ?? null),
        ]);
        return $parts ? implode(' - ', $parts) : ('Phòng #' . $this->id);
    }

    /**
     * Scope to get only available rooms (not in maintenance)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to exclude maintenance rooms
     */
    public function scopeNotInMaintenance($query)
    {
        return $query->where('status', '!=', 'maintenance');
    }

    /**
     * Check if room is in maintenance
     */
    public function isInMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    /**
     * Check if room can be booked
     */
    public function canBeBooked(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'available' => '<span class="badge bg-success">Trống</span>',
            'booked' => '<span class="badge bg-warning">Đã đặt</span>',
            'maintenance' => '<span class="badge bg-danger">Bảo trì</span>',
            'cleaning' => '<span class="badge bg-info">Đang dọn</span>',
        ];
        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

    /**
     * Resolve image URL - handles storage path, full URL, or returns null for fallback.
     */
    public static function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        $path = ltrim((string) $path, '/');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        // DB đôi khi lưu thừa "storage/..." → URL sẽ thành /storage/storage/... (404).
        while (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }
        if ($path === '') {
            return null;
        }
        return '/storage/'.$path.'?v='.config('room_images.cache_version', '1');
    }

    /**
     * Get all displayable image URLs for this room (images, room.image, roomType.image).
     */
    public function getDisplayImageUrls(): array
    {
        $urls = [];
        foreach ($this->images as $img) {
            if ($img->image_url) {
                $url = self::resolveImageUrl($img->image_url);
                if ($url) {
                    $urls[] = $url;
                }
            }
        }
        if (empty($urls) && $this->image) {
            $url = self::resolveImageUrl($this->image);
            if ($url) {
                $urls[] = $url;
            }
        }
        if (empty($urls) && $this->roomType?->image) {
            $url = self::resolveImageUrl($this->roomType->image);
            if ($url) {
                $urls[] = $url;
            }
        }
        return $urls;
    }

    protected static function booted(): void
    {
        static::deleting(function (Room $room): void {
            if ($room->isForceDeleting()) {
                return;
            }
            $room->images()->delete();
        });
    }
}


