<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestPortalSignedUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_guest_bookings_portal_redirects_with_error(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('guest.bookings.index', ['user' => $user->id]));

        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors();
    }
}
