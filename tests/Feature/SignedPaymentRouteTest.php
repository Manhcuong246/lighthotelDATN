<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignedPaymentRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_vnpay_pay_redirects_with_error(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $booking = Booking::create([
            'user_id' => $user->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'total_price' => 100000,
            'guests' => 1,
            'adults' => 1,
            'children' => 0,
        ]);

        $response = $this->get(route('payment.vnpay.pay', ['booking' => $booking->id]));

        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors();
    }
}
