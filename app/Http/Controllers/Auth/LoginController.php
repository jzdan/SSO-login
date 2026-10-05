<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\Passport\Client;
use App\Models\User;
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
        $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = Str::lower(trim($request->input('login')));
        $column = User::loginColumn($login);
        $throttleKey = Str::transliterate(Str::lower($login).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        if (! Auth::attempt([$column => $login, 'password' => $request->input('password')], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['login' => 'NIP/email atau password salah.']);
        }

        RateLimiter::clear($throttleKey);
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages(['login' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
        }

        // Login dengan alamat email tertentu mengharuskan email itu sudah diverifikasi;
        // login dengan NIP mengharuskan minimal satu email akun sudah diverifikasi.
        $verified = in_array($column, array_keys(User::EMAIL_COLUMNS), true)
            ? $user->emailIsVerified($column)
            : $user->hasAnyVerifiedEmail();

        if (! $verified) {
            Auth::logout();
            $request->session()->put('verification_user_id', $user->id);

            return redirect()->route('verification.notice');
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
