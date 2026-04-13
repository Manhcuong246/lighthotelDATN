<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use SoftDeletes;

    protected $table = 'images';

    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'image_url',
        'image_type',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}


