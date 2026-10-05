<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun contoh untuk menguji keempat cara login dan status verifikasi. Semua berpassword "password".
 * Hanya dipanggil di APP_ENV=local, atau jalankan manual: php artisan db:seed --class=DemoUserSeeder
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Lengkap & terverifikasi: bisa login dengan keempat cara.
            ['name' => 'Budi Santoso', 'nip_lama' => '340012345', 'nip_baru' => '199001012015',
                'email' => 'budi.santoso@gmail.com', 'email_bps' => 'budi.santoso@bps.go.id',
                'email_verified' => true, 'email_bps_verified' => true],

            // Hanya email BPS terverifikasi: login via Gmail ditolak, via NIP / email BPS bisa.
            ['name' => 'Siti Rahma', 'nip_lama' => null, 'nip_baru' => '199205152018',
                'email' => 'siti.rahma@gmail.com', 'email_bps' => 'siti.rahma@bps.go.id',
                'email_verified' => false, 'email_bps_verified' => true],

            // Belum verifikasi sama sekali: diarahkan ke halaman verifikasi email.
            ['name' => 'Andi Wijaya', 'nip_lama' => '340054321', 'nip_baru' => '198803202012',
                'email' => 'andi.wijaya@gmail.com', 'email_bps' => 'andi.wijaya@bps.go.id',
                'email_verified' => false, 'email_bps_verified' => false],

            // Dinonaktifkan admin: tidak bisa login dengan cara apa pun.
            ['name' => 'Dewi Lestari', 'nip_lama' => '340067890', 'nip_baru' => '198512102010',
                'email' => 'dewi.lestari@gmail.com', 'email_bps' => 'dewi.lestari@bps.go.id',
                'email_verified' => true, 'email_bps_verified' => true, 'is_active' => false],

            // Mitra tanpa NIP & email BPS: hanya bisa login dengan Gmail.
            ['name' => 'Joko Prasetyo (Mitra)', 'nip_lama' => null, 'nip_baru' => null,
                'email' => 'joko.mitra@gmail.com', 'email_bps' => null,
                'email_verified' => true, 'email_bps_verified' => false],
        ];

        foreach ($users as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);

            $user->fill([
                'name' => $data['name'],
                'nip_lama' => $data['nip_lama'],
                'nip_baru' => $data['nip_baru'],
                'email_bps' => $data['email_bps'],
                'password' => 'password',
                'is_admin' => false,
                'is_active' => $data['is_active'] ?? true,
            ])->forceFill([
                'email_verified_at' => $data['email_verified'] ? now() : null,
                'email_bps_verified_at' => $data['email_bps_verified'] ? now() : null,
            ])->save();
        }
    }
}
