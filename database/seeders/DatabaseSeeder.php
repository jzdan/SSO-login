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
        $admin = User::firstOrCreate(
            ['email' => env('SSO_ADMIN_EMAIL', 'admin@sso.test')],
            [
                'name' => 'Administrator',
                'password' => env('SSO_ADMIN_PASSWORD', 'password'),
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        // Lengkapi identitas admin dari .env tanpa menimpa data (atau password) yang sudah ada.
        $identity = array_filter([
            'nip_lama' => env('SSO_ADMIN_NIP_LAMA'),
            'nip_baru' => env('SSO_ADMIN_NIP_BARU'),
            'email_bps' => env('SSO_ADMIN_EMAIL_BPS'),
        ]);

        foreach ($identity as $column => $value) {
            if (! $admin->{$column}) {
                $admin->{$column} = $value;
            }
        }

        if ($admin->email_bps && ! $admin->email_bps_verified_at) {
            $admin->email_bps_verified_at = now();
        }

        $admin->save();

        // Akun contoh berpassword "password" hanya untuk uji coba, jangan pernah dibuat di server.
        if (app()->environment('local')) {
            $this->call(DemoUserSeeder::class);
        }
    }
}
