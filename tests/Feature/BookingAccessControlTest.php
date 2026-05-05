<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_other_users_booking_without_signature(): void
    {
        $owner = $this->createCustomerUser();
        $booking = $this->createMinimalBooking($owner);

        $response = $this->get(route('bookings.show', $booking));

        $response->assertForbidden();
    }

    public function test_owner_can_view_own_booking_when_logged_in(): void
    {
        $owner = $this->createCustomerUser();
        $booking = $this->createMinimalBooking($owner);

        $response = $this->actingAs($owner)->get(route('bookings.show', $booking));

        $response->assertOk();
    }

    public function test_staff_cannot_open_admin_booking_edit_form(): void
    {
        $owner = $this->createCustomerUser();
        $booking = $this->createMinimalBooking($owner);
        $staff = $this->createStaffUser();

        $response = $this->actingAs($staff)->get(route('admin.bookings.edit', $booking));

        $response->assertForbidden();
    }

    public function test_admin_booking_edit_redirects_to_show(): void
    {
        $owner = $this->createCustomerUser();
        $booking = $this->createMinimalBooking($owner);
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.bookings.edit', $booking));

        $response->assertRedirect(route('admin.bookings.show', $booking));
    }

    public function test_admin_can_view_booking_detail(): void
    {
        $owner = $this->createCustomerUser();
        $booking = $this->createMinimalBooking($owner);
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();
    }
}
