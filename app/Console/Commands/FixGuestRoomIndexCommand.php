<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\BookingRoom;

class FixGuestRoomIndexCommand extends Command
{
    protected $signature = 'fix:guest-room-index {booking_id?}';
    protected $description = 'Fix room_index for guests based on booking_rooms data';

    public function handle()
    {
        $bookingId = $this->argument('booking_id');

        if ($bookingId) {
            $booking = Booking::find($bookingId);
            if (!$booking) {
                $this->error("Booking #{$bookingId} not found");
                return 1;
            }
            $this->fixBooking($booking);
        } else {
            // Fix all bookings with multiple rooms
            $bookings = Booking::whereHas('bookingRooms', function ($q) {
                $q->havingRaw('COUNT(*) > 1');
            })->get();

            $this->info("Found {$bookings->count()} bookings with multiple rooms");

            foreach ($bookings as $booking) {
                $this->fixBooking($booking);
            }
        }

        $this->info('Done!');
        return 0;
    }

    private function fixBooking(Booking $booking)
    {
        $this->info("\nProcessing Booking #{$booking->id}");

        // Load booking rooms with room info
        $bookingRooms = $booking->bookingRooms()
            ->with('room.roomType')
            ->orderBy('id')
            ->get();

        if ($bookingRooms->count() <= 1) {
            $this->warn("  Only 1 room, skipping");
            return;
        }

        // Display rooms
        $this->info("  Rooms:");
        foreach ($bookingRooms as $index => $br) {
            $roomName = $br->room->roomType?->name . ' ' . $br->room->name;
            $guestCount = $br->adults + $br->children_0_5 + $br->children_6_11;
            $this->info("    [{$index}] {$roomName} - {$guestCount} guests");
        }

        // Get all guests for this booking
        $guests = Guest::where('booking_id', $booking->id)
            ->orderBy('id')
            ->get();

        $this->info("  Total guests: {$guests->count()}");

        if ($guests->isEmpty()) {
            $this->warn("  No guests found");
            return;
        }

        // Assign room_index to guests based on room order and guest count per room
        $guestIndex = 0;
        foreach ($bookingRooms as $roomIndex => $br) {
            $guestCountInRoom = $br->adults + $br->children_0_5 + $br->children_6_11;
            $roomName = $br->room->roomType?->name . ' ' . $br->room->name;

            $this->info("  Assigning room_index={$roomIndex} ({$roomName}) to {$guestCountInRoom} guests");

            for ($i = 0; $i < $guestCountInRoom && $guestIndex < $guests->count(); $i++) {
                $guest = $guests[$guestIndex];
                $oldRoomIndex = $guest->room_index;

                $guest->update(['room_index' => $roomIndex]);

                $this->info("    Guest: {$guest->name} (ID: {$guest->id}) - room_index: {$oldRoomIndex} -> {$roomIndex}");

                $guestIndex++;
            }
        }

        // Clear cache
        \Cache::forget("guest_info_{$booking->id}");
        $this->info("  Cache cleared");
    }
}
