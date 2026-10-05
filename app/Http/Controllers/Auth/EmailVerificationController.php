<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Halaman "cek email Anda" setelah login/daftar dengan akun yang belum diverifikasi.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.verify-email', compact('user'));
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $sent = $user->sendEmailVerifications();

        return back()->with('status', $sent
            ? 'Link verifikasi baru telah dikirim. Silakan cek inbox (dan folder spam) Anda.'
            : 'Semua email akun ini sudah terverifikasi. Silakan login.');
    }

    public function verify(Request $request, User $user, string $type, string $hash): RedirectResponse
    {
        abort_unless(in_array($type, array_keys(User::EMAIL_COLUMNS), true), 404);
        abort_unless($user->{$type} && hash_equals(sha1($user->{$type}), $hash), 403, 'Link verifikasi tidak valid.');

        $verifiedAt = User::EMAIL_COLUMNS[$type];

        if (! $user->{$verifiedAt}) {
            $user->forceFill([$verifiedAt => now()])->save();
        }

        $request->session()->forget('verification_user_id');
        $message = 'Email '.$user->{$type}.' berhasil diverifikasi.';

        return $request->user()
            ? redirect()->route('profile.edit')->with('status', $message)
            : redirect()->route('login')->with('status', $message.' Silakan login.');
    }

    /**
     * Pengguna yang sedang menunggu verifikasi: dari sesi (belum login) atau pengguna yang login.
     */
    private function pendingUser(Request $request): ?User
    {
        return $request->user() ?? User::find($request->session()->get('verification_user_id'));
    }
}
