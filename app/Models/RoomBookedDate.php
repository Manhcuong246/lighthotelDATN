<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

class RoomBookedDate extends Model
{
    protected $table = 'room_booked_dates';

    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'booked_date',
        'booking_id',
    ];

    protected $casts = [
        'booked_date' => 'date',
    ];

    /**
     * @param  iterable<mixed>|\Carbon\CarbonPeriod  $nights
     * @return list<string>
     */
    public static function normalizeNightDateStrings(iterable $nights): array
    {
        $out = [];
        foreach ($nights as $n) {
            if ($n instanceof CarbonPeriod) {
                foreach ($n as $d) {
                    $out[] = $d->copy()->startOfDay()->toDateString();
                }

                continue;
            }
            if ($n instanceof \Carbon\CarbonInterface) {
                $out[] = $n->copy()->startOfDay()->toDateString();

                continue;
            }
            $out[] = Carbon::parse((string) $n)->toDateString();
        }
        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }

    /**
     * Chuyển các đêm đặt của một đơn từ phòng cũ sang phòng mới mà không dùng UPDATE `room_id`:
     * tránh trùng UNIQUE(room_id, booked_date) khi đã có dòng trùng đêm trên phòng đích (dữ liệu lệch / đổi phòng trước đó).
     */
    public static function replaceBookingRoomNights(int $bookingId, int $oldRoomId, int $newRoomId, iterable $nights): void
    {
        $dates = static::normalizeNightDateStrings($nights);
        if ($dates === []) {
            return;
        }

        static::query()
            ->where('booking_id', $bookingId)
            ->where('room_id', $oldRoomId)
            ->whereIn('booked_date', $dates)
            ->delete();

        static::query()
            ->where('booking_id', $bookingId)
            ->where('room_id', $newRoomId)
            ->whereIn('booked_date', $dates)
            ->delete();

        $now = now();
        $rows = [];
        foreach ($dates as $ds) {
            $rows[] = [
                'room_id' => $newRoomId,
                'booking_id' => $bookingId,
                'booked_date' => $ds,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            static::insert($chunk);
        }
    }

    /**
     * Xây lại toàn bộ room_booked_dates của đơn theo các booking_rooms hiện tại (đa phòng an toàn).
     */
    public static function syncForBooking(Booking $booking): void
    {
        $checkIn = Carbon::parse($booking->check_in)->startOfDay();
        $lastNight = Carbon::parse($booking->check_out)->startOfDay()->subDay();

        static::query()->where('booking_id', $booking->id)->delete();

        if ($checkIn->gt($lastNight)) {
            return;
        }

        $period = CarbonPeriod::create($checkIn, $lastNight);
        $dates = [];
        foreach ($period as $d) {
            $dates[] = $d->toDateString();
        }

        $now = now();
        $rows = [];
        $bookingRooms = $booking->bookingRooms()
            ->whereNotNull('room_id')
            ->get()
            ->unique('room_id');

        foreach ($bookingRooms as $br) {
            if (! $br->room_id) {
                continue;
            }
            foreach ($dates as $ds) {
                $rows[] = [
                    'room_id' => $br->room_id,
                    'booked_date' => $ds,
                    'booking_id' => $booking->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            static::insert($chunk);
        }
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}


