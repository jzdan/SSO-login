<?php

namespace Tests\Feature;

use App\Models\Passport\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class SsoFlowTest extends TestCase
{
    use RefreshDatabase;

    private const REDIRECT = 'http://app1.test/auth/sso/callback';

    private function makeClient(array $attributes = []): Client
    {
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('App 1', [self::REDIRECT]);
        $client->forceFill($attributes + ['skip_authorization' => true, 'show_on_dashboard' => true])->save();

        return $client;
    }

    private function authorizeQuery(Client $client, string $verifier): array
    {
        return [
            'client_id' => $client->id,
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'scope' => 'profile email',
            'state' => 'xyz',
            'code_challenge' => strtr(rtrim(base64_encode(hash('sha256', $verifier, true)), '='), '+/', '-_'),
            'code_challenge_method' => 'S256',
        ];
    }

    public function test_guest_is_redirected_to_login_when_authorizing(): void
    {
        $client = $this->makeClient();

        $this->get('/oauth/authorize?'.http_build_query($this->authorizeQuery($client, Str::random(64))))
            ->assertRedirect(route('login'));
    }

    public function test_full_authorization_code_flow(): void
    {
        $user = User::factory()->create(['email' => 'budi@test.id']);
        $client = $this->makeClient();
        $plainSecret = $client->plainSecret;
        $verifier = Str::random(64);

        $response = $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query($this->authorizeQuery($client, $verifier)));

        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $params);
        $this->assertSame('xyz', $params['state']);
        $this->assertNotEmpty($params['code']);

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'client_secret' => $plainSecret,
            'redirect_uri' => self::REDIRECT,
            'code' => $params['code'],
            'code_verifier' => $verifier,
        ])->assertOk()->json();

        $this->assertArrayHasKey('refresh_token', $token);

        // Reset auth state so the API guard authenticates purely via the bearer token.
        $this->app['auth']->forgetGuards();

        $this->withToken($token['access_token'])->getJson('/api/user')
            ->assertOk()
            ->assertJson(['id' => $user->id, 'email' => 'budi@test.id']);

        // Logout from SSO revokes tokens for every client app.
        $this->app['auth']->forgetGuards();
        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->app['auth']->forgetGuards();
        $this->withToken($token['access_token'])->getJson('/api/user')->assertUnauthorized();
    }

    public function test_untrusted_client_shows_consent_screen(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClient(['skip_authorization' => false]);

        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query($this->authorizeQuery($client, Str::random(64))))
            ->assertOk()
            ->assertSee('Izinkan');
    }

    public function test_client_logout_redirects_only_to_registered_origin(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClient();

        $this->actingAs($user)
            ->get('/logout?'.http_build_query(['client_id' => $client->id, 'redirect_uri' => 'http://app1.test/']))
            ->assertRedirect('http://app1.test/');
        $this->assertGuest();

        $this->actingAs($user)
            ->get('/logout?'.http_build_query(['client_id' => $client->id, 'redirect_uri' => 'http://evil.test/']))
            ->assertRedirect(route('login'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create(['email' => 'off@test.id', 'is_active' => false]);

        $this->post('/login', ['login' => 'off@test.id', 'password' => 'password'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_page_and_dashboard_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');

        $user = User::factory()->create();
        $this->makeClient(['homepage_url' => 'http://app1.test']);

        $this->actingAs($user)->get('/')->assertOk()->assertSee('App 1');
    }

    public function test_login_is_recorded_and_shown_on_admin_dashboard(): void
    {
        $admin = User::factory()->create(['email' => 'admin@test.id', 'is_admin' => true]);

        $this->post('/login', ['login' => 'admin@test.id', 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('login_activities', ['user_id' => $admin->id]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Total Pengguna')
            ->assertSee('Login SSO per hari')
            ->assertSee('Aktivitas login terbaru');
    }

    public function test_regular_user_dashboard_hides_admin_widgets(): void
    {
        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertDontSee('Total Pengguna')
            ->assertSee('Riwayat login Anda');
    }

    public function test_admin_can_manage_clients_and_non_admin_cannot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/clients')->assertForbidden();

        $this->actingAs($admin)->post('/admin/clients', [
            'name' => 'Portal Pegawai',
            'redirect_uris_text' => "http://pegawai.test/auth/sso/callback\nhttp://localhost:8001/auth/sso/callback",
            'skip_authorization' => '1',
            'show_on_dashboard' => '1',
        ])->assertRedirect()->assertSessionHas('plainSecret');

        $client = Client::where('name', 'Portal Pegawai')->firstOrFail();
        $this->assertCount(2, $client->redirect_uris);
        $this->assertTrue($client->confidential());

        foreach (['/admin/clients', "/admin/clients/{$client->id}", "/admin/clients/{$client->id}/edit", '/admin/users', '/admin/users/create', '/profile'] as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }
}
