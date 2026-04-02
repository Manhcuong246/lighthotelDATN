<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    public $timestamps = true;

    public const REFUND_NONE = 'none';

    public const REFUND_AWAITING_USER = 'awaiting_user_info';

    public const REFUND_PENDING_ADMIN = 'pending_admin';

    public const REFUND_COMPLETED = 'completed';

    public const REFUND_REJECTED = 'rejected';

    protected $fillable = [
        'booking_id',
        'amount',
        'method',
        'status',
        'transaction_id',
        'paid_at',
        'refund_status',
        'refund_account_name',
        'refund_account_number',
        'refund_qr_path',
        'refund_proof_path',
        'refund_user_note',
        'refund_admin_note',
        'refund_requested_at',
        'refund_completed_at',
        'refund_penalty_amount',
        'refund_eligible_amount',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refund_requested_at' => 'datetime',
        'refund_completed_at' => 'datetime',
        'refund_penalty_amount' => 'decimal:2',
        'refund_eligible_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function needsRefundBankDetails(): bool
    {
        if ($this->status !== 'paid' || $this->refund_status !== self::REFUND_AWAITING_USER) {
            return false;
        }
        if ($this->refund_eligible_amount !== null) {
            return (float) $this->refund_eligible_amount > 0;
        }

        return (float) $this->amount > 0;
    }

    public function beginRefundFlowIfPaid(): void
    {
        if ($this->status !== 'paid') {
            return;
        }
        if ($this->refund_status && $this->refund_status !== self::REFUND_NONE) {
            return;
        }
        $eligible = $this->refund_eligible_amount;
        if ($eligible !== null && (float) $eligible <= 0) {
            return;
        }
        $this->update([
            'refund_status' => self::REFUND_AWAITING_USER,
            'refund_requested_at' => now(),
        ]);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}


