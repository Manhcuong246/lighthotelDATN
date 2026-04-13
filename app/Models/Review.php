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

    public static function userHasReviewedRoom(int $userId, int $roomId): bool
    {
        return static::withTrashed()
            ->where('user_id', $userId)
            ->where('room_id', $roomId)
            ->exists();
    }
}


