<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $sessions = $request->user()->tokens()
            ->with('client')
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return view('profile.edit', ['user' => $request->user(), 'sessions' => $sessions]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        User::normalizeIdentityInput($request);

        // NIP hanya bisa diubah admin, karena dipakai sebagai identitas login.
        $rules = collect(User::identityRules($user))->only(['name', 'email', 'email_bps'])->all();
        $user->fill($request->validate($rules, User::identityMessages()));

        $emailChanged = $user->isDirty(array_keys(User::EMAIL_COLUMNS));
        $user->resetChangedEmailVerification();
        $user->save();

        if ($emailChanged && $user->sendEmailVerifications()) {
            return back()->with('status', 'Profil berhasil diperbarui. Link verifikasi telah dikirim ke email yang baru.');
        }

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('status', 'Password berhasil diubah.');
    }

    public function revokeSessions(Request $request): RedirectResponse
    {
        $request->user()->revokeAllTokens();

        return back()->with('status', 'Semua sesi aplikasi telah dicabut.');
    }
}
