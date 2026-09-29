<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\Passport\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        RateLimiter::clear($throttleKey);

        if (! Auth::user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
        }

        $request->session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();
        LoginActivity::record(Auth::user(), $request);

        // "intended" membawa pengguna kembali ke /oauth/authorize bila login dipicu oleh aplikasi klien.
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Logout dari SSO sekaligus mencabut semua token, sehingga seluruh aplikasi klien ikut logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()?->revokeAllTokens();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($this->safeRedirect($request) ?? route('login'))
            ->with('status', 'Anda telah keluar dari semua aplikasi.');
    }

    /**
     * Logout yang dipanggil dari aplikasi klien: GET /logout?client_id=...&redirect_uri=...
     * Redirect hanya diizinkan ke URL yang terdaftar milik klien tersebut.
     */
    public function clientLogout(Request $request): RedirectResponse|View
    {
        if (Auth::guest()) {
            return redirect()->to($this->safeRedirect($request) ?? route('login'));
        }

        // Tanpa client_id yang valid, minta konfirmasi agar pengguna tidak bisa di-logout lewat link sembarangan.
        if (! $this->requestingClient($request)) {
            return view('auth.logout');
        }

        return $this->destroy($request);
    }

    private function requestingClient(Request $request): ?Client
    {
        $id = $request->input('client_id');

        return $id ? Client::where('revoked', false)->find($id) : null;
    }

    private function safeRedirect(Request $request): ?string
    {
        $uri = $request->input('redirect_uri');
        $client = $this->requestingClient($request);

        return $uri && $client && $client->ownsUrl($uri) ? $uri : null;
    }
}
