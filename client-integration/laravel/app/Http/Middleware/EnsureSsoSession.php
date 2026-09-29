<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Salin ke aplikasi KLIEN.
 * Memastikan sesi SSO masih berlaku. Jika pengguna logout di SSO (atau dinonaktifkan admin),
 * token dicabut dan pengguna otomatis ikut logout dari aplikasi ini.
 */
class EnsureSsoSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->guest(route('sso.redirect'));
        }

        $interval = (int) config('sso.check_interval', 60);

        if (now()->timestamp - (int) $request->session()->get('sso_checked_at', 0) < $interval) {
            return $next($request);
        }

        if ($this->tokenIsValid($request) || $this->refreshToken($request)) {
            $request->session()->put('sso_checked_at', now()->timestamp);

            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->guest(route('sso.redirect'));
    }

    private function tokenIsValid(Request $request): bool
    {
        $token = $request->session()->get('sso_access_token');

        return $token && Http::withToken($token)->acceptJson()
            ->get(config('sso.base_url').'/api/user')
            ->ok();
    }

    private function refreshToken(Request $request): bool
    {
        $refresh = $request->session()->get('sso_refresh_token');

        if (! $refresh) {
            return false;
        }

        $response = Http::asForm()->acceptJson()->post(config('sso.base_url').'/oauth/token', array_filter([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh,
            'client_id' => config('sso.client_id'),
            'client_secret' => config('sso.client_secret'),
            'scope' => 'profile email',
        ]));

        if (! $response->ok()) {
            return false;
        }

        $request->session()->put([
            'sso_access_token' => $response->json('access_token'),
            'sso_refresh_token' => $response->json('refresh_token'),
        ]);

        return $this->tokenIsValid($request);
    }
}
