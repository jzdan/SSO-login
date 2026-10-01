<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan sesi SSO masih berlaku untuk pengguna yang login lewat SSO.
 * Jika pengguna logout di SSO / aplikasi lain (atau dinonaktifkan admin), token dicabut
 * dan pengguna otomatis ikut logout dari aplikasi ini pada pemeriksaan berikutnya.
 *
 * Pengguna yang login dengan password biasa atau Google tidak diperiksa.
 */
class EnsureSsoSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! $request->session()->has('sso_access_token')) {
            return $next($request);
        }

        $interval = (int) config('sso.check_interval', 60);

        if (now()->timestamp - (int) $request->session()->get('sso_checked_at', 0) < $interval) {
            return $next($request);
        }

        try {
            $valid = $this->tokenIsValid($request) || $this->refreshToken($request);
        } catch (ConnectionException) {
            // Server SSO tidak bisa dihubungi: jangan logout-kan pengguna, coba lagi pada interval berikutnya.
            $valid = true;
        }

        if ($valid) {
            $request->session()->put('sso_checked_at', now()->timestamp);

            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Halaman publik tetap bisa dibuka sebagai tamu; halaman ber-login akan diarahkan ke /login oleh middleware auth.
        return $request->isMethod('GET')
            ? redirect()->to($request->fullUrl())->with('error', 'Sesi SSO Anda telah berakhir. Silakan login kembali.')
            : redirect('/login')->with('error', 'Sesi SSO Anda telah berakhir. Silakan login kembali.');
    }

    private function tokenIsValid(Request $request): bool
    {
        return Http::timeout(5)->withToken($request->session()->get('sso_access_token'))->acceptJson()
            ->get(config('sso.base_url').'/api/user')
            ->ok();
    }

    private function refreshToken(Request $request): bool
    {
        $refresh = $request->session()->get('sso_refresh_token');

        if (! $refresh) {
            return false;
        }

        $response = Http::timeout(5)->asForm()->acceptJson()->post(config('sso.base_url').'/oauth/token', array_filter([
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
