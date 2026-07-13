<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@isp.com'],
            [
                'name' => 'Super Admin',
                'phone' => '08123456789',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            ]
        );

        $admin->assignRole('Super Admin');
    }
}
