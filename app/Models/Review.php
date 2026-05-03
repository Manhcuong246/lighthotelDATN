<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    protected $table = 'reviews';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'room_id',
        'booking_id',
        'rating',
        'title',
        'comment',
        'reply',
        'replied_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function booking(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Đã có đánh giá cho đúng đơn và phòng vật lý (một lượt lưu trú).
     */
    public static function existsForBookingAndRoom(int $bookingId, int $roomId): bool
    {
        return static::query()
            ->where('booking_id', $bookingId)
            ->where('room_id', $roomId)
            ->exists();
    }

    /**
     * @deprecated Dùng existsForBookingAndRoom hoặc reviewableBookingsForRoom (theo đơn).
     */
    public static function userHasReviewedRoom(int $userId, int $roomId): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('room_id', $roomId)
            ->whereNotNull('booking_id')
            ->exists();
    }
}


