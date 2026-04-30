<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\Guest;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddGuestsToBooking146 extends Seeder
{
    public function run(): void
    {
        $bookingId = 146;
        $booking = Booking::find($bookingId);
        
        if (!$booking) {
            echo "Booking #146 not found\n";
            return;
        }

        // Get room IDs
        $standard107 = Room::where('name', 'like', '%Standard 107%')->orWhere('room_number', '107')->first();
        $deluxe206 = Room::where('name', 'like', '%Deluxe 206%')->orWhere('room_number', '206')->first();
        
        // Get booking room IDs
        $bookingRooms = $booking->bookingRooms;
        $standardBookingRoom = $bookingRooms->firstWhere('room_id', $standard107?->id);
        $deluxeBookingRoom = $bookingRooms->firstWhere('room_id', $deluxe206?->id);
        
        echo "Standard 107 Room ID: " . ($standard107?->id ?? 'N/A') . "\n";
        echo "Deluxe 206 Room ID: " . ($deluxe206?->id ?? 'N/A') . "\n";
        echo "Standard BookingRoom ID: " . ($standardBookingRoom?->id ?? 'N/A') . "\n";
        echo "Deluxe BookingRoom ID: " . ($deluxeBookingRoom?->id ?? 'N/A') . "\n";

        // Guests to add
        $guests = [
            // Standard 107: 2 adults + 1 child
            ['name' => 'Nguyễn Văn An', 'type' => 'adult', 'room_id' => $standard107?->id, 'booking_room_id' => $standardBookingRoom?->id, 'room_type' => 'Standard'],
            ['name' => 'Nguyễn Văn An', 'type' => 'adult', 'room_id' => $standard107?->id, 'booking_room_id' => $standardBookingRoom?->id, 'room_type' => 'Standard'],
            ['name' => 'Nguyễn Văn An', 'type' => 'child_0_5', 'room_id' => $standard107?->id, 'booking_room_id' => $standardBookingRoom?->id, 'room_type' => 'Standard'],
            
            // Deluxe 206: 2 adults + 1 child (user said 207 but booking shows 206)
            ['name' => 'Nguyễn Văn An', 'type' => 'adult', 'room_id' => $deluxe206?->id, 'booking_room_id' => $deluxeBookingRoom?->id, 'room_type' => 'Deluxe'],
            ['name' => 'Nguyễn Văn An', 'type' => 'adult', 'room_id' => $deluxe206?->id, 'booking_room_id' => $deluxeBookingRoom?->id, 'room_type' => 'Deluxe'],
            ['name' => 'Nguyễn Văn An', 'type' => 'child_0_5', 'room_id' => $deluxe206?->id, 'booking_room_id' => $deluxeBookingRoom?->id, 'room_type' => 'Deluxe'],
        ];

        DB::beginTransaction();
        try {
            foreach ($guests as $guestData) {
                // Add to guests table (legacy)
                Guest::create([
                    'booking_id' => $bookingId,
                    'name' => $guestData['name'],
                    'cccd' => null,
                    'type' => $guestData['type'],
                    'room_id' => $guestData['room_id'],
                    'checkin_status' => 'checked_in', // Already checked in
                    'is_representative' => 0,
                    'room_type' => $guestData['room_type'],
                ]);

                // Add to booking_guests table (new)
                BookingGuest::create([
                    'booking_id' => $bookingId,
                    'booking_room_id' => $guestData['booking_room_id'],
                    'name' => $guestData['name'],
                    'cccd' => null,
                    'type' => $guestData['type'] === 'adult' ? 'adult' : 'child',
                    'status' => 'checked_in',
                    'checkin_status' => 'checked_in',
                    'is_representative' => 0,
                ]);
            }

            DB::commit();
            echo "Successfully added 6 guests to booking #146\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
