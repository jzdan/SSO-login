<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('SSO_ADMIN_EMAIL', 'admin@sso.test')],
            [
                'name' => 'Administrator',
                'password' => env('SSO_ADMIN_PASSWORD', 'password'),
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
