<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class CreateExampleAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'full_name' => 'Admin Example',
            'email' => 'admin@example.com',
            'password' => bcrypt('Admin@123'),
            'phone' => '0123456789'
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        if($adminRole) {
            $user->roles()->attach($adminRole);
        }

        echo "Created admin@example.com\n";
    }
}
