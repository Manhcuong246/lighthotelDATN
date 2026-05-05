<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewBookingValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookings_search_requires_dates(): void
    {
        $response = $this->post(route('bookings.search'), []);

        $response->assertSessionHasErrors(['check_in', 'check_out']);
    }

    public function test_bookings_search_lists_available_room(): void
    {
        $this->seedAvailableRoom();

        $checkIn = Carbon::today()->addDays(3)->toDateString();
        $checkOut = Carbon::today()->addDays(5)->toDateString();

        $response = $this->post(route('bookings.search'), [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]);

        $response->assertOk();
        $response->assertSee('Deluxe Test', false);
    }

    public function test_booking_store_requires_valid_cccd(): void
    {
        $response = $this->post(route('bookings.internal.store'), [
            'check_in' => Carbon::today()->addDay()->toDateString(),
            'check_out' => Carbon::today()->addDays(2)->toDateString(),
            'full_name' => 'Nguyen Van A',
            'email' => 'a@test.local',
            'phone' => '0909123456',
            'rooms' => 1,
            'name' => 'Nguyen Van A',
            'cccd' => '123',
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors(['cccd']);
    }
}
