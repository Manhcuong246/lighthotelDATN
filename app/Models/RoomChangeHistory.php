<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomChangeHistory extends Model
{
    protected $table = 'room_change_history';

    protected $fillable = [
        'booking_id',
        'from_room_id',
        'to_room_id',
        'damage_report_id',
        'reason',
        'changed_by',
        'changed_at',
        'old_price_per_night',
        'new_price_per_night',
        'price_difference',
        'change_type',
        'remaining_nights',
        'old_room_status',
    ];

    /**
     * Loại đổi phòng - cùng hạng
     */
    public const TYPE_SAME_GRADE = 'same_grade';

    /**
     * Loại đổi phòng - nâng hạng
     */
    public const TYPE_UPGRADE = 'upgrade';

    /**
     * Loại đổi phòng - hạ hạng
     */
    public const TYPE_DOWNGRADE = 'downgrade';

    /**
     * Loại đổi phòng - khẩn cấp
     */
    public const TYPE_EMERGENCY = 'emergency';

    protected $casts = [
        'changed_at' => 'datetime',
        'old_price_per_night' => 'decimal:2',
        'new_price_per_night' => 'decimal:2',
        'price_difference' => 'decimal:2',
        'remaining_nights' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function fromRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'from_room_id');
    }

    public function toRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'to_room_id');
    }

    public function damageReport(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Label hiển thị cho loại đổi phòng
     */
    public function getChangeTypeLabelAttribute(): string
    {
        return match($this->change_type) {
            self::TYPE_SAME_GRADE => 'Cùng hạng',
            self::TYPE_UPGRADE    => 'Nâng hạng',
            self::TYPE_DOWNGRADE  => 'Hạ hạng',
            self::TYPE_EMERGENCY  => 'Khẩn cấp',
            default               => 'Khác',
        };
    }

    /**
     * Màu badge cho loại đổi phòng
     */
    public function getChangeTypeBadgeAttribute(): string
    {
        return match($this->change_type) {
            self::TYPE_SAME_GRADE => 'bg-secondary',
            self::TYPE_UPGRADE    => 'bg-primary',
            self::TYPE_DOWNGRADE  => 'bg-success',
            self::TYPE_EMERGENCY  => 'bg-danger',
            default               => 'bg-dark',
        };
    }

    /**
     * Label trạng thái phòng cũ
     */
    public function getOldRoomStatusLabelAttribute(): string
    {
        return match($this->old_room_status) {
            'available'  => 'Sẵn sàng',
            'occupied'   => 'Đang sử dụng',
            'booked'     => 'Đã đặt',
            'cleaning'   => 'Đang dọn dẹp',
            'maintenance'=> 'Bảo trì',
            default      => $this->old_room_status ?? 'N/A',
        };
    }
}
