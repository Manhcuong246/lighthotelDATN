<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuestUserSeeder extends Seeder
{
    /**
     * Tài khoản khách dùng để test (luồng đặt phòng / hủy / hoàn tiền).
     */
    public function run(): void
    {
        $guestRole = Role::firstOrCreate(['name' => 'guest']);

        $guest = User::firstOrCreate(
            ['email' => 'guest@test.local'],
            [
                'full_name' => 'Khách Test',
                'password' => Hash::make('Guest@123'),
                'phone' => '0900000001',
                'status' => 'active',
            ]
        );

        if (! $guest->roles()->where('name', 'guest')->exists()) {
            $guest->roles()->attach($guestRole->id);
        }
    }
}
