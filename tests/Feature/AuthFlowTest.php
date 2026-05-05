<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_from_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_login_and_reach_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->post(route('login.submit'), [
            'email' => $admin->email,
            'password' => 'SecretPass1',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_provisional_guest_cannot_login_without_registering(): void
    {
        $this->seedRoles();
        $user = User::factory()->create([
            'email' => 'shadow@test.local',
            'password' => Hash::make('x'),
            'status' => 'active',
        ]);

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'x',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_banned_user_cannot_login(): void
    {
        $user = $this->createCustomerUser();
        $user->update(['status' => 'banned']);

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'SecretPass1',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_staff_redirected_to_staff_dashboard_after_login(): void
    {
        $staff = $this->createStaffUser();

        $response = $this->post(route('login.submit'), [
            'email' => $staff->email,
            'password' => 'SecretPass1',
        ]);

        $response->assertRedirect('/staff/dashboard');
        $this->assertAuthenticatedAs($staff);
    }

    public function test_customer_cannot_access_staff_area(): void
    {
        $customer = $this->createCustomerUser();

        $response = $this->actingAs($customer)->get(route('staff.dashboard'));

        $response->assertForbidden();
    }
}
