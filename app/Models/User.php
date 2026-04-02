<?php

namespace App\Models;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\Access\Authorizable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Authorizable;

    protected $table = 'users';

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole(string $name): bool
    {
        return $this->roles()->where('name', $name)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('guest');
    }

    public function canAccessAdmin(): bool
    {
        return $this->isAdmin() || $this->isStaff();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}


