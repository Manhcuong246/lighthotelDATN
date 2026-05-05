<?php

namespace Tests;

use App\Models\Booking;
use App\Models\HotelInfo;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    protected function seedRoles(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'guest']);
        Role::firstOrCreate(['name' => 'customer']);
    }

    protected function seedMinimalHotelCatalog(): HotelInfo
    {
        return HotelInfo::create([
            'name' => 'Light Hotel Test',
            'description' => 'Test',
            'address' => 'Test street',
            'phone' => '0900000000',
            'email' => 'hotel@test.local',
            'rating_avg' => 4.8,
        ]);
    }

    protected function seedAvailableRoom(): Room
    {
        $type = RoomType::create([
            'name' => 'Deluxe Test',
            'capacity' => 2,
            'standard_capacity' => 2,
            'beds' => 1,
            'baths' => 1,
            'price' => 500000,
            'status' => true,
        ]);

        return Room::create([
            'name' => 'Deluxe Test 101',
            'room_number' => 'T101',
            'base_price' => 500000,
            'max_guests' => 2,
            'beds' => 1,
            'baths' => 1,
            'status' => 'available',
            'room_type_id' => $type->id,
        ]);
    }

    protected function createAdminUser(): User
    {
        $this->seedRoles();
        $role = Role::where('name', 'admin')->firstOrFail();
        $user = User::factory()->create([
            'email' => 'admin-test@hotel.local',
            'password' => Hash::make('SecretPass1'),
            'status' => 'active',
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh();
    }

    protected function createStaffUser(): User
    {
        $this->seedRoles();
        $role = Role::where('name', 'staff')->firstOrFail();
        $user = User::factory()->create([
            'email' => 'staff-test@hotel.local',
            'password' => Hash::make('SecretPass1'),
            'status' => 'active',
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh();
    }

    protected function createCustomerUser(): User
    {
        $this->seedRoles();
        $role = Role::where('name', 'customer')->firstOrFail();
        $user = User::factory()->create([
            'email' => 'customer-test@hotel.local',
            'password' => Hash::make('SecretPass1'),
            'status' => 'active',
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh();
    }

    protected function createMinimalBooking(User $user, ?Room $room = null): Booking
    {
        $room ??= $this->seedAvailableRoom();

        return Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(7)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
            'total_price' => 900000,
            'guests' => 1,
            'adults' => 1,
            'children' => 0,
        ]);
    }
}
