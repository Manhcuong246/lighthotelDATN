<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Models\RoomBookedDate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use existing users from database (id 1, 2)
        $user1 = User::find(1); // Nguyễn Văn A
        $user2 = User::find(2); // Trần Thị B

        // Skip if users don't exist
        if (!$user1 || !$user2) {
            echo "❌ Cần ít nhất 2 users trong database.\n";
            return;
        }

        // Ensure we have a room
        $room = Room::first() ?? Room::factory()->create(["name" => "Seeded Room", "status" => "available"]);

        // helper to create a booking and its booked dates
        $createBooking = function (array $data) use ($room) {
            $booking = Booking::create(array_merge([
                'room_id' => $room->id,
                'guests' => 1,
                'total_price' => $room->base_price,
                'status' => 'pending',
            ], $data));

            // populate room_booked_dates (same logic as controller)
            $start = Carbon::parse($data['check_in']);
            $end   = Carbon::parse($data['check_out']);
            $period = CarbonPeriod::create($start, $end->copy()->subDay());
            foreach ($period as $date) {
                RoomBookedDate::create([
                    'room_id' => $room->id,
                    'booked_date' => $date->toDateString(),
                    'booking_id' => $booking->id,
                ]);
            }

            return $booking;
        };

        // create a few bookings with different states
        $b1 = $createBooking([
            'user_id' => $user1->id,
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDays(2)->toDateString(),
            'status' => 'pending',
        ]);

        $b2 = $createBooking([
            'user_id' => $user2->id,
            'check_in' => Carbon::today()->subDays(3)->toDateString(),
            'check_out' => Carbon::today()->subDays(1)->toDateString(),
            'status' => 'confirmed',
        ]);
        $b2->actual_check_in = Carbon::today()->subDays(3)->setTime(14,0);
        $b2->save();

        $b3 = $createBooking([
            'user_id' => $user1->id,
            'check_in' => Carbon::today()->subDays(5)->toDateString(),
            'check_out' => Carbon::today()->subDays(3)->toDateString(),
            'status' => 'completed',
        ]);
        $b3->actual_check_in = Carbon::today()->subDays(5)->setTime(14,0);
        $b3->actual_check_out = Carbon::today()->subDays(3)->setTime(11,0);
        $b3->save();

        // create a log entry for each (optional)
        foreach ([$b1, $b2, $b3] as $booking) {
            \App\Models\BookingLog::create([
                'booking_id' => $booking->id,
                'old_status' => 'pending',
                'new_status' => $booking->status,
                'changed_at' => now(),
            ]);
        }
    }
}
