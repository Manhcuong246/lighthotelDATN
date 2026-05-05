<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtendedPublicSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_detail_page_loads(): void
    {
        $room = $this->seedAvailableRoom();

        $response = $this->get(route('rooms.show', $room));

        $response->assertOk();
    }

    public function test_search_page_loads(): void
    {
        $this->seedMinimalHotelCatalog();

        $response = $this->get(route('rooms.search'));

        $response->assertOk();
    }

    public function test_simple_booking_form_loads(): void
    {
        $response = $this->get(route('bookings.create-simple'));

        $response->assertOk();
    }

    public function test_payment_failed_page_loads(): void
    {
        $response = $this->get(route('payment.failed'));

        $response->assertOk();
    }

    public function test_admin_login_form_loads(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertOk();
    }

    public function test_register_validation_requires_fields(): void
    {
        $response = $this->post(route('register.submit'), []);

        $response->assertSessionHasErrors(['full_name', 'email', 'password']);
    }
}
