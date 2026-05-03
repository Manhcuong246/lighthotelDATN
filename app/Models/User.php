<?php

namespace App\Models;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\Access\Authorizable;

/**
 * @method bool hasRole(string $name)
 * @method bool isAdmin()
 * @method bool isStaff()
 * @method bool canAccessAdmin()
 * @property int $id
 * @property string $email
 * @property string $full_name
 * @property string $phone
 * @property string $password
 * @property string $avatar_url
 * @property string $status
 * @property \Illuminate\Database\Eloquent\Collection $roles
 * @property-read string $email
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, Authorizable, SoftDeletes;

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

    /**
     * Get the user's email address.
     */
    public function getEmailAttribute(): string
    {
        return $this->attributes['email'] ?? '';
    }

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
        return $this->hasRole('customer');
    }

    public function canAccessAdmin(): bool
    {
        return $this->isAdmin() || $this->isStaff();
    }

    /**
     * Tài khoản chỉ được tạo ngầm khi khách đặt phòng (web) hoặc admin đặt hộ:
     * có mật khẩu ngẫu nhiên, chưa gắn role — đăng ký với cùng email sẽ "kích hoạt" tài khoản.
     */
    public function isProvisionalGuestAccount(): bool
    {
        if ($this->isAdmin() || $this->isStaff()) {
            return false;
        }

        if ($this->hasRole('guest')) {
            return false;
        }

        return $this->roles()->count() === 0;
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Đơn chưa kết thúc hẳn — không cho khách tự đóng tài khoản (tránh tranh chấp / hoàn tiền).
     */
    public function hasBookingsBlockingAccountClosure(): bool
    {
        return $this->bookings()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->exists();
    }

    /**
     * Chỉ khách (không admin/staff) được đóng tài khoản qua trang hồ sơ.
     */
    public function canSelfCloseAccountFromWebsite(): bool
    {
        return ! $this->canAccessAdmin();
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if ($user->isForceDeleting()) {
                return;
            }
            $email = (string) $user->email;
            if ($email !== '' && ! str_starts_with($email, '__deleted__')) {
                $prefixed = '__deleted__' . $user->id . '__' . $email;
                $user->email = strlen($prefixed) > 255 ? substr($prefixed, 0, 255) : $prefixed;
            }
        });
    }
}


