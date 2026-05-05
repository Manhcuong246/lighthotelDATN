<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_returns_ok(): void
    {
        $this->seedMinimalHotelCatalog();

        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
    }

    public function test_static_pages_return_ok(): void
    {
        $this->seedMinimalHotelCatalog();

        foreach (['pages.contact', 'pages.help', 'pages.policy'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_new_booking_index_returns_ok(): void
    {
        $response = $this->get(route('bookings.index'));

        $response->assertOk();
    }
}
