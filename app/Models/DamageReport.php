<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DamageReport extends Model
{
    protected $table = 'damage_reports';

    protected $fillable = [
        'room_id',
        'reported_by',
        'booking_id',
        'damage_type',
        'description',
        'severity',
        'status',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
        'repair_cost',
        'requires_room_change',
        'requires_refund',
        'refund_amount',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'requires_room_change' => 'boolean',
        'requires_refund' => 'boolean',
        'repair_cost' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function roomChangeHistories(): HasMany
    {
        return $this->hasMany(RoomChangeHistory::class);
    }

    public function isUrgent(): bool
    {
        return $this->severity === 'urgent' || $this->severity === 'high';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function markAsResolved(int $resolvedById, ?string $notes = null, ?float $repairCost = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by' => $resolvedById,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
            'repair_cost' => $repairCost,
        ]);

        // Update room status back to available
        $this->room->update([
            'status' => 'available',
            'maintenance_note' => null,
            'maintenance_since' => null,
            'damage_report_id' => null,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['reported', 'in_progress']);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('severity', ['high', 'urgent']);
    }

    public static function getDamageTypes(): array
    {
        return [
            'broken_bed' => 'Giường bị hỏng',
            'broken_ac' => 'Điều hòa hỏng',
            'water_leak' => 'Rò rỉ nước',
            'power_issue' => 'Vấn đề điện',
            'broken_tv' => 'TV hỏng',
            'wifi_issue' => 'Wifi không hoạt động',
            'plumbing_issue' => 'Vấn đề ống nước',
            'door_lock_broken' => 'Khóa cửa hỏng',
            'window_broken' => 'Cửa sổ hỏng',
            'furniture_damaged' => 'Đồ nội thất hư hỏng',
            'cleanliness_issue' => 'Vấn đề vệ sinh',
            'noise_issue' => 'Vấn đề tiếng ồn',
            'other' => 'Khác',
        ];
    }

    public static function getSeverityLabels(): array
    {
        return [
            'low' => 'Thấp',
            'medium' => 'Trung bình',
            'high' => 'Cao',
            'urgent' => 'Khẩn cấp',
        ];
    }

    public function getSeverityBadgeAttribute(): string
    {
        $badges = [
            'low' => '<span class="badge bg-secondary">Thấp</span>',
            'medium' => '<span class="badge bg-warning">Trung bình</span>',
            'high' => '<span class="badge bg-danger">Cao</span>',
            'urgent' => '<span class="badge bg-dark">Khẩn cấp</span>',
        ];
        return $badges[$this->severity] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'reported' => '<span class="badge bg-warning">Đã báo cáo</span>',
            'in_progress' => '<span class="badge bg-info">Đang xử lý</span>',
            'resolved' => '<span class="badge bg-success">Đã giải quyết</span>',
            'cancelled' => '<span class="badge bg-secondary">Đã hủy</span>',
        ];
        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }
}
