<?php

namespace Tests\Feature;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_coupon_code_returns_validation_error(): void
    {
        $response = $this->postJson(route('coupons.verify'), ['code' => '']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('code');
    }

    public function test_coupon_code_must_match_safe_pattern(): void
    {
        $response = $this->postJson(route('coupons.verify'), ['code' => "SAVE'; DROP TABLE users;--"]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('code');
    }

    public function test_invalid_coupon_returns_failure(): void
    {
        $response = $this->postJson(route('coupons.verify'), ['code' => 'NOPE']);

        $response->assertOk();
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_active_coupon_returns_discount(): void
    {
        Coupon::create([
            'code' => 'SAVE10',
            'discount_percent' => 10,
            'is_active' => true,
            'expired_at' => Carbon::today()->addMonth(),
        ]);

        $response = $this->postJson(route('coupons.verify'), ['code' => 'SAVE10']);

        $response->assertOk();
        $response->assertJsonFragment([
            'success' => true,
            'discount_percent' => 10.0,
        ]);
    }
}
