<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}