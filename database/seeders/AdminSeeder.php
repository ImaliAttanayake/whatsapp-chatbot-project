<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Seed the application's database with default admin users.
     *
     * Default password: Admin@123 (change after first login).
     */
    public function run(): void
    {
        $defaultPassword = 'Admin@123';

        $admins = [
            ['name' => 'Kosala',  'email' => 'kosala@slt.lk'],
            ['name' => 'Charith', 'email' => 'charith@slt.lk'],
            ['name' => 'Menusha', 'email' => 'menusha@slt.lk'],
            ['name' => 'Ekvith',  'email' => 'ekvith@slt.lk'],
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($defaultPassword),
                    'is_admin' => true,
                    'role' => 'admin',
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );
        }

        // Create a Super Admin
        User::updateOrCreate(
            ['email' => 'superadmin@slt.lk'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($defaultPassword),
                'is_admin' => true,
                'role' => 'superadmin',
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );
    }
}
