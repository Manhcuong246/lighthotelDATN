<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
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

    public function damageReports()
    {
        return $this->hasMany(DamageReport::class);
    }

    public function activeDamageReport()
    {
        return $this->belongsTo(DamageReport::class, 'damage_report_id');
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
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return asset('storage/' . $path);
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
}


