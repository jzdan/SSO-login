<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Salin ke aplikasi KLIEN (bukan ke server SSO).
 * Menangani login via SSO memakai OAuth2 Authorization Code + PKCE.
 */
class SsoController extends Controller
{
    /**
     * Arahkan pengguna ke halaman login SSO.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $verifier = Str::random(128);

        $request->session()->put(['sso_state' => $state, 'sso_verifier' => $verifier]);

        $challenge = strtr(rtrim(base64_encode(hash('sha256', $verifier, true)), '='), '+/', '-_');

        return redirect()->away(config('sso.base_url').'/oauth/authorize?'.http_build_query([
            'client_id' => config('sso.client_id'),
            'redirect_uri' => config('sso.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'profile email',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]));
    }

    /**
     * SSO mengembalikan pengguna ke sini dengan ?code=...&state=...
     */
    public function callback(Request $request): RedirectResponse
    {
        $state = $request->session()->pull('sso_state');
        $verifier = $request->session()->pull('sso_verifier');

        if (! $state || ! hash_equals($state, (string) $request->input('state'))) {
            abort(403, 'State SSO tidak valid. Silakan coba login lagi.');
        }

        if ($request->filled('error')) {
            return redirect('/')->withErrors(['sso' => 'Login SSO dibatalkan: '.$request->input('error')]);
        }

        $tokenResponse = Http::asForm()->acceptJson()->post(config('sso.base_url').'/oauth/token', array_filter([
            'grant_type' => 'authorization_code',
            'client_id' => config('sso.client_id'),
            'client_secret' => config('sso.client_secret'),
            'redirect_uri' => config('sso.redirect_uri'),
            'code' => $request->input('code'),
            'code_verifier' => $verifier,
        ]));

        abort_unless($tokenResponse->ok(), 401, 'Gagal menukar kode SSO.');
        $token = $tokenResponse->json();

        $ssoUser = Http::withToken($token['access_token'])->acceptJson()
            ->get(config('sso.base_url').'/api/user')
            ->throw()
            ->json();

        // Sinkronkan pengguna lokal berdasarkan ID dari SSO.
        $user = User::updateOrCreate(
            ['sso_id' => $ssoUser['id']],
            ['name' => $ssoUser['name'], 'email' => $ssoUser['email']],
        );

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put([
            'sso_access_token' => $token['access_token'],
            'sso_refresh_token' => $token['refresh_token'] ?? null,
            'sso_checked_at' => now()->timestamp,
        ]);

        return redirect()->intended('/');
    }

    /**
     * Logout lokal lalu logout dari SSO (sekaligus dari semua aplikasi lain).
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(config('sso.base_url').'/logout?'.http_build_query([
            'client_id' => config('sso.client_id'),
            'redirect_uri' => url('/'),
        ]));
    }
}
