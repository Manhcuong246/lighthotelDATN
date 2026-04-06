<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\RefundLog;
use Illuminate\Support\Facades\URL;

class Booking extends Model
{
    use SoftDeletes;

    protected $table = 'bookings';

    public $timestamps = true;

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'pending',
        'discount_amount' => 0,
    ];

    protected $fillable = [
        'user_id',
        'room_id',
        'check_in',
        'check_out',
        'check_in_date',
        'check_out_date',
        'actual_check_in',
        'actual_check_out',
        'guests',
        'adults',
        'children',
        'total_price',
        'status',
        'payment_status',
        'payment_method',
        'placed_via',
        'coupon_code',
        'discount_amount',
        'cancellation_reason',
        'cancel_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'check_in_date' => 'datetime',
        'check_out_date' => 'datetime',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ mới: 1 booking có nhiều booking_rooms
     */
    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class);
    }

    /**
     * Shortcut: danh sách Room qua booking_rooms
     */
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'booking_rooms')
                    ->withPivot('price_per_night', 'nights', 'subtotal', 'adults', 'children_0_5', 'children_6_11')
                    ->withTimestamps();
    }

    /**
     * Giữ lại để tương thích với code admin cũ (dùng $booking->room)
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /** Giao dịch thanh toán mới nhất (một dòng / đơn). */
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /** Bản ghi thanh toán mới nhất (nếu có nhiều dòng lịch sử). */
    public function payments()
    {
        return $this->hasMany(Payment::class)->orderByDesc('id');
    }

    public function refundLogs()
    {
        return $this->hasMany(RefundLog::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function logs()
    {
        return $this->hasMany(BookingLog::class);
    }

    public function bookedDates()
    {
        return $this->hasMany(RoomBookedDate::class);
    }

    public function bookingServices()
    {
        return $this->hasMany(BookingService::class);
    }

    public function refundRequest()
    {
        return $this->hasOne(RefundRequest::class);
    }

    public function surcharges()
    {
        return $this->hasMany(BookingSurcharge::class);
    }

    /**
     * Khách tự check-in trên web: từ ngày nhận phòng, đơn đã xác nhận.
     */
    public function isCheckinAllowed(): bool
    {
        return $this->status === 'confirmed'
            && is_null($this->actual_check_in)
            && Carbon::today()->gte($this->check_in);
    }

    /**
     * Admin: nhận phòng khi đơn đã xác nhận, chưa ghi nhận check-in,
     * và vẫn trong kỳ đặt (không quá ngày trả phòng dự kiến — sau đó không còn hợp lệ để CI).
     * Nhận sớm trước ngày nhận vẫn được nếu chưa quá check_out.
     */
    public function isAdminCheckinAllowed(): bool
    {
        if ($this->status !== 'confirmed' || !is_null($this->actual_check_in) || !$this->check_out) {
            return false;
        }

        return Carbon::today()->lte($this->check_out);
    }

    /**
     * Khách tự check-out: đã nhận phòng và từ ngày trả phòng trở đi (theo hợp đồng).
     */
    public function isCheckoutAllowed(): bool
    {
        return $this->status === 'confirmed'
            && !is_null($this->actual_check_in)
            && is_null($this->actual_check_out)
            && Carbon::today()->gte($this->check_out);
    }

    /**
     * Admin: trả phòng khi đã check-in, chưa check-out (trả sớm / ghi nhận tại quầy).
     */
    public function isAdminCheckoutAllowed(): bool
    {
        return $this->status === 'confirmed'
            && !is_null($this->actual_check_in)
            && is_null($this->actual_check_out);
    }

    /**
     * Giai đoạn lưu trú cho UI admin (một dòng / đơn — cùng actual_check_in/out).
     *
     * @return string pending_payment|cancelled|not_checked_in|stay_overdue|checked_in|checked_out
     */
    public function adminStayPhase(): string
    {
        if (in_array($this->status, ['cancelled', 'cancel_requested'], true)) {
            return 'cancelled';
        }
        if ($this->status === 'pending') {
            return 'pending_payment';
        }
        if (!is_null($this->actual_check_out) || $this->status === 'completed') {
            return 'checked_out';
        }
        if (!is_null($this->actual_check_in)) {
            return 'checked_in';
        }
        if ($this->status === 'confirmed' && $this->check_out && Carbon::today()->gt($this->check_out)) {
            return 'stay_overdue';
        }

        return 'not_checked_in';
    }

    /**
     * Hóa đơn / biên lai (khách và luật tạo HĐ nội bộ): chỉ khi đã thanh toán và đã checkout.
     */
    public function isPaidAndCheckedOutForInvoice(): bool
    {
        if (in_array($this->status, ['cancelled', 'cancel_requested'], true)) {
            return false;
        }

        if (! $this->isPaymentRecordedPaid()) {
            return false;
        }

        return $this->status === 'completed' || $this->actual_check_out !== null;
    }

    /**
     * Đơn đã được ghi nhận thanh toán (theo bookings.payment_status hoặc payments.status).
     */
    public function isPaymentRecordedPaid(): bool
    {
        if (($this->payment_status ?? '') === 'paid') {
            return true;
        }

        if ($this->relationLoaded('payment')) {
            return $this->payment && $this->payment->status === 'paid';
        }

        return $this->payment()->where('status', 'paid')->exists();
    }

    /**
     * Đơn chờ thanh toán coi như hết hạn: quá ngày nhận phòng, hoặc các đêm phòng đã bị đơn khác (còn hiệu lực) giữ.
     */
    public function isPendingDisplayExpired(): bool
    {
        if ($this->status !== 'pending' || $this->isPaymentRecordedPaid()) {
            return false;
        }
        if ($this->check_in && Carbon::today()->gt($this->check_in)) {
            return true;
        }

        return $this->hasRoomDatesClaimedByAnotherActiveBooking();
    }

    private function hasRoomDatesClaimedByAnotherActiveBooking(): bool
    {
        if (!$this->check_in || !$this->check_out) {
            return false;
        }

        $roomIds = $this->resolveRoomIdsForDateBlocks();
        if ($roomIds === []) {
            return false;
        }

        $period = CarbonPeriod::create(
            Carbon::parse($this->check_in),
            Carbon::parse($this->check_out)->copy()->subDay()
        );
        $dates = collect($period)->map(static fn ($d) => Carbon::parse($d)->toDateString())->all();
        if ($dates === []) {
            return false;
        }

        return RoomBookedDate::query()
            ->whereIn('room_id', $roomIds)
            ->whereIn('booked_date', $dates)
            ->where('booking_id', '<>', $this->id)
            ->whereHas('booking', static function ($q): void {
                $q->whereNotIn('status', ['cancelled', 'cancel_requested']);
            })
            ->exists();
    }

    /**
     * @return list<int>
     */
    private function resolveRoomIdsForDateBlocks(): array
    {
        if ($this->relationLoaded('bookingRooms')) {
            $ids = $this->bookingRooms->pluck('room_id')->map(static fn ($id) => (int) $id)->unique()->values()->all();
        } else {
            $ids = $this->bookingRooms()->pluck('room_id')->map(static fn ($id) => (int) $id)->unique()->values()->all();
        }

        if ($ids !== []) {
            return $ids;
        }

        if ($this->room_id) {
            return [(int) $this->room_id];
        }

        return [];
    }

    /**
     * Đánh giá phòng: chỉ khách đã đặt đúng phòng đó, đã thanh toán, và đã check-out.
     * Hỗ trợ cả đơn một phòng (bookings.room_id) và đơn nhiều phòng (booking_rooms).
     */
    public static function userHasCheckedOutRoom(int $userId, int $roomId): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->whereNotIn('status', ['cancelled', 'cancel_requested'])
            ->whereNotNull('actual_check_out')
            ->where(function ($q) {
                $q->where('payment_status', 'paid')
                    ->orWhereHas('payment', static fn ($p) => $p->where('status', 'paid'));
            })
            ->where(function ($q) use ($roomId) {
                $q->where('room_id', $roomId)
                    ->orWhereHas('bookingRooms', static fn ($br) => $br->where('room_id', $roomId));
            })
            ->exists();
    }

    /**
     * Gửi đánh giá phòng: đủ điều kiện checkout + thanh toán và chưa có đánh giá cho phòng đó.
     */
    public static function userCanSubmitRoomReview(int $userId, int $roomId): bool
    {
        return static::userHasCheckedOutRoom($userId, $roomId)
            && ! Review::userHasReviewedRoom($userId, $roomId);
    }

    /** Đặt từ website khách (kể cả không đăng nhập — tài khoản shadow theo email). */
    public const PLACED_VIA_CUSTOMER_WEB = 'customer_web';

    /** Admin đặt hộ khách. */
    public const PLACED_VIA_ADMIN = 'admin';

    /**
     * Link xem chi tiết đơn cho khách không đăng nhập (kèm chữ ký URL).
     */
    public function signedPublicShowUrl(?int $ttlDays = null): string
    {
        $days = $ttlDays ?? max(1, (int) config('booking.signed_booking_show_ttl_days', 90));

        return URL::signedRoute(
            'bookings.show',
            ['booking' => $this->id],
            now()->addDays($days)
        );
    }

    public function signedPublicCancelUrl(?int $ttlDays = null): string
    {
        $days = $ttlDays ?? max(1, (int) config('booking.signed_booking_show_ttl_days', 90));

        return URL::signedRoute(
            'bookings.cancel',
            ['booking' => $this->id],
            now()->addDays($days)
        );
    }

    public function signedPublicPolicyUrl(?int $ttlDays = null): string
    {
        $days = $ttlDays ?? max(1, (int) config('booking.signed_booking_show_ttl_days', 90));

        return URL::signedRoute(
            'bookings.policy',
            ['booking' => $this->id],
            now()->addDays($days)
        );
    }

    protected static function booted(): void
    {
        static::deleting(function (Booking $booking): void {
            if ($booking->isForceDeleting()) {
                return;
            }
            RoomBookedDate::where('booking_id', $booking->id)->delete();
            Payment::where('booking_id', $booking->id)->delete();
            $invoice = $booking->invoice()->first();
            if ($invoice) {
                $invoice->delete();
            }
        });
    }
}


