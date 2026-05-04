<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class Service extends Model
{
    use SoftDeletes;

    protected $table = 'services';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'price',
        'description',
    ];

    /**
     * Rule validate ID dịch vụ còn hoạt động (không soft-deleted).
     */
    public static function existsActiveIdRule(): \Illuminate\Validation\Rules\Exists
    {
        $r = Rule::exists('services', 'id');
        if (Schema::hasColumn('services', 'deleted_at')) {
            $r->withoutTrashed('deleted_at');
        }

        return $r;
    }

    public function roomTypes()
    {
        return $this->belongsToMany(RoomType::class, 'room_type_service');
    }
}


