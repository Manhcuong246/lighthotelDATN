<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $table = 'site_contents';

    protected $fillable = [
        'type',
        'title',
        'content',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active contents.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get content by type.
     */
    public static function getByType(string $type)
    {
        return static::where('type', $type)->where('is_active', true)->first();
    }
}


