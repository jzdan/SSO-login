<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function pegawai(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'nip_lama' => '340012345',
            'nip_baru' => '199001012015',
            'email' => 'budi@gmail.com',
            'email_bps' => 'budi@bps.go.id',
            'email_bps_verified_at' => now(),
        ]);
    }

    public function test_user_can_login_with_any_of_the_four_identifiers(): void
    {
        $user = $this->pegawai();

        foreach (['340012345', '199001012015', 'budi@bps.go.id', 'BUDI@gmail.com'] as $login) {
            $this->post('/login', ['login' => $login, 'password' => 'password'])->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->pegawai();

        $this->post('/login', ['login' => '340012345', 'password' => 'salah'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_unverified_user_is_sent_to_verification_page(): void
    {
        $this->pegawai(['email_verified_at' => null, 'email_bps_verified_at' => null]);

        $this->post('/login', ['login' => '340012345', 'password' => 'password'])
            ->assertRedirect(route('verification.notice'));
        $this->assertGuest();

        $this->get(route('verification.notice'))->assertOk()->assertSee('budi@bps.go.id');
    }

    public function test_login_with_an_unverified_email_is_blocked_even_if_another_email_is_verified(): void
    {
        $this->pegawai(['email_verified_at' => null]);

        $this->post('/login', ['login' => 'budi@gmail.com', 'password' => 'password'])
            ->assertRedirect(route('verification.notice'));
        $this->assertGuest();

        // Email BPS sudah terverifikasi, jadi login dengan NIP tetap bisa.
        $this->post('/login', ['login' => '199001012015', 'password' => 'password'])->assertRedirect(route('dashboard'));
    }

    public function test_verification_link_marks_email_verified(): void
    {
        Notification::fake();
        $user = $this->pegawai(['email_verified_at' => null, 'email_bps_verified_at' => null]);

        $this->assertSame(2, $user->sendEmailVerifications());

        $url = null;
        Notification::assertSentTo(new AnonymousNotifiable, VerifyEmailAddress::class, function ($notification, $channels, $notifiable) use (&$url) {
            if ($notifiable->routes['mail'] === 'budi@bps.go.id') {
                $url = $notification->verificationUrl();
            }

            return true;
        });

        $this->get($url)->assertRedirect(route('login'));
        $this->assertNotNull($user->fresh()->email_bps_verified_at);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_tampered_or_stale_verification_link_is_rejected(): void
    {
        $user = $this->pegawai(['email_bps_verified_at' => null]);
        $url = (new VerifyEmailAddress($user, 'email_bps'))->verificationUrl();

        // Email diganti setelah link dibuat: link lama tidak berlaku.
        $user->update(['email_bps' => 'lain@bps.go.id']);
        $this->get($url)->assertForbidden();

        $this->get(str_replace('email_bps', 'email', $url))->assertForbidden();
        $this->assertNull($user->fresh()->email_bps_verified_at);
    }

    public function test_registration_does_not_login_and_requires_verification(): void
    {
        Notification::fake();
        config(['sso.allow_registration' => true]);

        $this->post('/register', [
            'name' => 'Siti',
            'nip_lama' => '340099999',
            'nip_baru' => '',
            'email' => 'Siti@Gmail.com',
            'email_bps' => '',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
        ])->assertRedirect(route('verification.notice'));

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'siti@gmail.com', 'nip_baru' => null, 'email_verified_at' => null]);
        Notification::assertSentOnDemandTimes(VerifyEmailAddress::class, 1);
    }

    public function test_identity_validation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->pegawai();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'X',
            'nip_lama' => '12345',
            'nip_baru' => '199001012015',
            'email' => 'x@bps.go.id',
            'email_bps' => 'x@gmail.com',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
        ])->assertSessionHasErrors(['nip_lama', 'nip_baru', 'email', 'email_bps']);
    }

    public function test_admin_created_user_gets_verification_email_or_can_be_marked_verified(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $data = ['name' => 'Andi', 'nip_baru' => '198501012010', 'email' => 'andi@gmail.com', 'email_bps' => 'andi@bps.go.id',
            'password' => 'Rahasia123!', 'password_confirmation' => 'Rahasia123!', 'is_active' => '1'];

        $this->actingAs($admin)->post('/admin/users', $data)->assertRedirect(route('admin.users.index'));
        Notification::assertSentOnDemandTimes(VerifyEmailAddress::class, 2);
        $andi = User::where('email', 'andi@gmail.com')->first();
        $this->assertSame('belum_verifikasi', $andi->status());
        $this->actingAs($admin)->get("/admin/users/{$andi->id}/edit")->assertOk()->assertSee('Kirim ulang link verifikasi');
        $this->actingAs($admin)->get('/admin/users?q=198501012010')->assertOk()->assertSee('Belum verifikasi');

        $this->actingAs($admin)->post('/admin/users', ['nip_baru' => '198501012011', 'email' => 'rina@gmail.com', 'email_bps' => '', 'name' => 'Rina', 'mark_verified' => '1'] + $data)
            ->assertRedirect(route('admin.users.index'));
        $this->assertSame('aktif', User::where('email', 'rina@gmail.com')->first()->status());
    }

    public function test_changing_email_in_profile_resets_its_verification(): void
    {
        Notification::fake();
        $user = $this->pegawai();

        $this->actingAs($user)->put('/profile', ['name' => 'Budi', 'email' => 'budi.baru@gmail.com', 'email_bps' => 'budi@bps.go.id'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_bps_verified_at);
        Notification::assertSentOnDemandTimes(VerifyEmailAddress::class, 1);
    }
}
