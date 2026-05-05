<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_from_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_admin_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_staff_can_open_admin_dashboard(): void
    {
        $staff = $this->createStaffUser();

        $response = $this->actingAs($staff)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_staff_can_open_staff_dashboard(): void
    {
        $staff = $this->createStaffUser();

        $response = $this->actingAs($staff)->get(route('staff.dashboard'));

        $response->assertOk();
    }

    public function test_guest_redirected_from_staff_dashboard(): void
    {
        $response = $this->get(route('staff.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_open_admin_bookings_index(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.bookings.index'));

        $response->assertOk();
    }
}
