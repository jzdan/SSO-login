<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->value();

        $users = User::query()
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('email_bps', 'like', "%{$search}%")
                ->orWhere('nip_lama', 'like', "%{$search}%")
                ->orWhere('nip_baru', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = User::create($this->validated($request));

        return redirect()->route('admin.users.index')
            ->with('status', 'Pengguna berhasil ditambahkan. '.$this->handleVerification($request, $user));
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($request->user()->is($user)) {
            // Cegah admin mengunci dirinya sendiri.
            $data['is_admin'] = true;
            $data['is_active'] = true;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data);
        $emailChanged = $user->isDirty(array_keys(User::EMAIL_COLUMNS));
        $user->resetChangedEmailVerification();
        $user->save();

        if (! $user->is_active) {
            $user->revokeAllTokens();
        }

        $note = $emailChanged || $request->boolean('mark_verified') ? ' '.$this->handleVerification($request, $user) : '';

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil diperbarui.'.$note);
    }

    public function sendVerification(User $user): RedirectResponse
    {
        $sent = $user->sendEmailVerifications();

        return back()->with('status', $sent
            ? "Link verifikasi dikirim ke {$sent} alamat email."
            : 'Semua email pengguna ini sudah terverifikasi.');
    }

    /**
     * Admin bisa langsung menandai email terverifikasi (mis. data pegawai dari kepegawaian);
     * jika tidak, link verifikasi dikirim ke email yang belum terverifikasi.
     */
    private function handleVerification(Request $request, User $user): string
    {
        if ($request->boolean('mark_verified')) {
            foreach (User::EMAIL_COLUMNS as $column => $verifiedAt) {
                if ($user->{$column} && ! $user->{$verifiedAt}) {
                    $user->{$verifiedAt} = now();
                }
            }
            $user->save();

            return 'Email ditandai sudah terverifikasi.';
        }

        return $user->sendEmailVerifications()
            ? 'Link verifikasi telah dikirim ke email pengguna.'
            : '';
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Anda tidak dapat menghapus akun sendiri.');

        $user->revokeAllTokens();
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil dihapus.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        User::normalizeIdentityInput($request);

        $data = $request->validate(User::identityRules($user) + [
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ], User::identityMessages());

        return $data + [
            'is_admin' => $request->boolean('is_admin'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
