<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAndReviewAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_from_account_bookings(): void
    {
        $response = $this->get(route('account.bookings'));

        $response->assertRedirect(route('login'));
    }

    public function test_customer_can_open_account_bookings(): void
    {
        $customer = $this->createCustomerUser();

        $response = $this->actingAs($customer)->get(route('account.bookings'));

        $response->assertOk();
    }

    public function test_guest_redirected_from_review_create(): void
    {
        $room = $this->seedAvailableRoom();

        $response = $this->get(route('reviews.create', $room));

        $response->assertRedirect(route('login'));
    }
}
