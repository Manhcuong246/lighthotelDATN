<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'booking_id',
        'invoice_number',
        'room_amount',
        'services_amount',
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
}
