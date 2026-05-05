<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'guest']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        $demoPassword = Hash::make('Admin@123');

        $admin = User::firstOrCreate(
            ['email' => 'admin@hotel.local'],
            [
                'full_name' => 'Admin User',
                'password' => $demoPassword,
                'phone' => '0123456789',
                'status' => 'active',
            ]
        );
        if (! $admin->roles()->where('name', 'admin')->exists()) {
            $admin->roles()->attach($adminRole->id);
        }

        $staff = User::firstOrCreate(
            ['email' => 'staff@hotel.local'],
            [
                'full_name' => 'Staff User',
                'password' => $demoPassword,
                'phone' => '0123456790',
                'status' => 'active',
            ]
        );
        if (! $staff->roles()->where('name', 'staff')->exists()) {
            $staff->roles()->attach($staffRole->id);
        }

        $customer = User::firstOrCreate(
            ['email' => 'customer@hotel.local'],
            [
                'full_name' => 'Khách demo',
                'password' => $demoPassword,
                'phone' => '0123456791',
                'status' => 'active',
            ]
        );
        if ($customerRole && ! $customer->roles()->where('name', 'customer')->exists()) {
            $customer->roles()->attach($customerRole->id);
        }

        echo "\n✅ Demo users (mật khẩu chung: Admin@123)";
        echo "\n  • admin    — admin@hotel.local";
        echo "\n  • staff    — staff@hotel.local";
        echo "\n  • customer — customer@hotel.local (đăng nhập site khách)\n";
    }
}
