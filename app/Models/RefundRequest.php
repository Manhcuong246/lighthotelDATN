<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RefundRequest extends Model
{
    protected $table = 'refund_requests';

    protected $fillable = [
        'booking_id',
        'user_id',
        'account_name',
        'account_number',
        'bank_name',
        'qr_image',
        'refund_percentage',
        'refund_amount',
        'note',
        'admin_note',
        'refund_proof_image',
        'status',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Đường dẫn tương đối trong disk public (vd. refund_proofs/abc.png).
     * Không dùng public_path('storage/...') — file nằm ở storage/app/public.
     */
    public function normalizedRefundProofPath(): ?string
    {
        $p = $this->refund_proof_image;
        if ($p === null || $p === '') {
            return null;
        }
        $p = str_replace('\\', '/', (string) $p);
        $p = ltrim($p, '/');
        if (str_starts_with($p, 'storage/')) {
            $p = substr($p, strlen('storage/'));
        }

        return $p !== '' ? $p : null;
    }

    public function refundProofFileExists(): bool
    {
        $rel = $this->normalizedRefundProofPath();

        return $rel !== null && Storage::disk('public')->exists($rel);
    }

    public function refundProofPublicUrl(): ?string
    {
        $rel = $this->normalizedRefundProofPath();

        return $rel !== null ? asset('storage/'.$rel) : null;
    }
}
