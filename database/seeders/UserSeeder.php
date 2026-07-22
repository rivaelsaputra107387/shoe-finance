<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed default users: Owner and Staff for testing.
     */
    public function run(): void
    {
        // Owner / Admin user
        $owner = User::create([
            'name' => 'Admin Owner',
            'email' => 'owner@shoeworkshop.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('owner');

        // Staff user
        $staff = User::create([
            'name' => 'Staff Kasir',
            'email' => 'staff@shoeworkshop.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $staff->assignRole('staff');

        // Finance user (optional)
        $finance = User::create([
            'name' => 'Finance Manager',
            'email' => 'finance@shoeworkshop.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $finance->assignRole('finance');
    }
}
