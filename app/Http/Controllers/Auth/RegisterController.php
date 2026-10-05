<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        abort_unless(config('sso.allow_registration'), 404);

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('sso.allow_registration'), 404);

        User::normalizeIdentityInput($request);

        $data = $request->validate(User::identityRules() + [
            'password' => ['required', 'confirmed', Password::defaults()],
        ], User::identityMessages());

        // Belum langsung login: email harus diverifikasi dulu, agar orang tidak bisa
        // mendaftar memakai email milik orang lain.
        $user = User::create($data);
        $user->sendEmailVerifications();
        $request->session()->put('verification_user_id', $user->id);

        return redirect()->route('verification.notice');
    }
}
