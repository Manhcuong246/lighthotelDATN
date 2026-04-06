<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'booking_id',
        'invoice_number',
        'room_amount',
        'services_amount',
        'surcharges_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
        'issued_at',
        'paid_at',
    ];

    protected $casts = [
        'room_amount' => 'decimal:2',
        'services_amount' => 'decimal:2',
        'surcharges_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'paid' || $this->paid_amount >= $this->total_amount;
    }

    public function markAsPaid($amount = null)
    {
        $this->paid_amount = $amount ?? $this->total_amount;
        $this->paid_at = Carbon::now();

        if ($this->paid_amount >= $this->total_amount) {
            $this->status = 'paid';
        } else {
            $this->status = 'partially_paid';
        }

        $this->save();
    }

    public static function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $date = Carbon::now()->format('Ymd');
        $lastInvoice = self::whereDate('created_at', Carbon::today())->orderBy('id', 'desc')->first();
        $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::deleting(function (Invoice $invoice): void {
            if ($invoice->isForceDeleting()) {
                return;
            }
            $invoice->items()->delete();
            $num = (string) $invoice->invoice_number;
            if ($num !== '' && ! str_starts_with($num, '__DEL__')) {
                $prefixed = '__DEL__' . $invoice->id . '__' . $num;
                $invoice->invoice_number = strlen($prefixed) > 255 ? substr($prefixed, 0, 255) : $prefixed;
            }
        });
    }
}
