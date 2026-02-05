<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use App\Models\BookingLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $booking;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        // Create guest user
        $guest = User::create([
            'email' => 'guest@test.com',
            'password' => bcrypt('password'),
        ]);

        // Create room
        $room = Room::create([
            'name' => 'Room 101',
            'type' => 'Single',
            'base_price' => 500000,
            'max_guests' => 2,
            'beds' => 1,
            'baths' => 1,
            'area' => 25,
            'status' => 'available',
        ]);

        // Create booking
        $this->booking = Booking::create([
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => now()->addDay(),
            'check_out' => now()->addDays(3),
            'guests' => 2,
            'total_price' => 1500000,
            'status' => 'pending',
        ]);
    }

    public function test_update_status_pending_to_confirmed(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post("/admin/bookings/{$this->booking->id}/status", [
            'status' => 'confirmed',
        ]);

        $this->booking->refresh();
        $this->assertEquals('confirmed', $this->booking->status);

        $log = BookingLog::where('booking_id', $this->booking->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('pending', $log->old_status);
        $this->assertEquals('confirmed', $log->new_status);
    }

    public function test_update_status_confirmed_to_cancelled(): void
    {
        $this->actingAs($this->admin);
        $this->booking->update(['status' => 'confirmed']);

        $this->post("/admin/bookings/{$this->booking->id}/status", [
            'status' => 'cancelled',
        ]);

        $this->booking->refresh();
        $this->assertEquals('cancelled', $this->booking->status);

        $log = BookingLog::where('booking_id', $this->booking->id)->latest()->first();
        $this->assertEquals('confirmed', $log->old_status);
        $this->assertEquals('cancelled', $log->new_status);
    }

    public function test_update_status_with_invalid_status(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post("/admin/bookings/{$this->booking->id}/status", [
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_checkin_requires_confirmed_status(): void
    {
        $this->actingAs($this->admin);
        $this->booking->update(['status' => 'pending']);

        $response = $this->post("/admin/bookings/{$this->booking->id}/checkin");

        $response->assertSessionHas('error', 'Không thể thực hiện check-in cho đơn này.');
        $this->booking->refresh();
        $this->assertNull($this->booking->actual_check_in);
    }

    public function test_checkin_when_confirmed(): void
    {
        $this->actingAs($this->admin);
        $this->booking->update(['status' => 'confirmed']);

        $response = $this->post("/admin/bookings/{$this->booking->id}/checkin");

        $response->assertSessionHas('success', 'Khách đã được check-in.');
        $this->booking->refresh();
        $this->assertNotNull($this->booking->actual_check_in);

        $log = BookingLog::where('booking_id', $this->booking->id)->first();
        $this->assertEquals('checked_in', $log->new_status);
    }

    public function test_checkin_fails_if_already_checked_in(): void
    {
        $this->actingAs($this->admin);
        $this->booking->update([
            'status' => 'confirmed',
            'actual_check_in' => now(),
        ]);

        $response = $this->post("/admin/bookings/{$this->booking->id}/checkin");

        $response->assertSessionHas('error', 'Không thể thực hiện check-in cho đơn này.');
    }

    public function test_checkout_requires_checked_in(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post("/admin/bookings/{$this->booking->id}/checkout");

        $response->assertSessionHas('error', 'Không thể thực hiện check-out cho đơn này.');
        $this->booking->refresh();
        $this->assertNull($this->booking->actual_check_out);
    }

    public function test_checkout_when_checked_in(): void
    {
        $this->actingAs($this->admin);
        $this->booking->update([
            'status' => 'confirmed',
            'actual_check_in' => now()->subHours(2),
        ]);

        $response = $this->post("/admin/bookings/{$this->booking->id}/checkout");

        $response->assertSessionHas('success', 'Khách đã check-out.');
        $this->booking->refresh();
        $this->assertNotNull($this->booking->actual_check_out);
        $this->assertEquals('completed', $this->booking->status);

        $log = BookingLog::where('booking_id', $this->booking->id)->first();
        $this->assertEquals('completed', $log->new_status);
    }

    public function test_checkout_fails_if_already_checked_out(): void
    {
        $this->actingAs($this->admin);
        $this->booking->update([
            'status' => 'completed',
            'actual_check_in' => now()->subHours(2),
            'actual_check_out' => now(),
        ]);

        $response = $this->post("/admin/bookings/{$this->booking->id}/checkout");

        $response->assertSessionHas('error', 'Không thể thực hiện check-out cho đơn này.');
    }

    public function test_logs_track_multiple_status_changes(): void
    {
        $this->actingAs($this->admin);

        $this->post("/admin/bookings/{$this->booking->id}/status", ['status' => 'confirmed']);
        $this->post("/admin/bookings/{$this->booking->id}/status", ['status' => 'cancelled']);

        $logs = BookingLog::where('booking_id', $this->booking->id)->get();
        $this->assertCount(2, $logs);

        $this->assertEquals('pending', $logs[0]->old_status);
        $this->assertEquals('confirmed', $logs[0]->new_status);

        $this->assertEquals('confirmed', $logs[1]->old_status);
        $this->assertEquals('cancelled', $logs[1]->new_status);
    }

    public function test_helper_is_checkin_allowed(): void
    {
        $this->booking->update(['status' => 'pending']);
        $this->assertFalse($this->booking->isCheckinAllowed());

        $this->booking->update(['status' => 'confirmed', 'actual_check_in' => null]);
        $this->assertTrue($this->booking->isCheckinAllowed());

        $this->booking->update(['actual_check_in' => now()]);
        $this->assertFalse($this->booking->isCheckinAllowed());
    }

    public function test_helper_is_checkout_allowed(): void
    {
        $this->booking->update(['actual_check_in' => null, 'actual_check_out' => null]);
        $this->assertFalse($this->booking->isCheckoutAllowed());

        $this->booking->update(['actual_check_in' => now(), 'actual_check_out' => null]);
        $this->assertTrue($this->booking->isCheckoutAllowed());

        $this->booking->update(['actual_check_out' => now()]);
        $this->assertFalse($this->booking->isCheckoutAllowed());
    }
}
