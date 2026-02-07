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
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $guestRole = Role::firstOrCreate(['name' => 'guest']);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@hotel.local'],
            [
                'full_name' => 'Admin User',
                'password' => Hash::make('Admin@123'),
                'phone' => '0123456789',
                'status' => 'active',
            ]
        );

        // Attach admin role
        if (!$admin->roles()->where('name', 'admin')->exists()) {
            $admin->roles()->attach($adminRole->id);
        }

        echo "\n✅ Admin user created successfully!";
        echo "\nEmail: admin@hotel.local";
        echo "\nPassword: Admin@123\n";
    }
}
