<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Passport\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Passport\ClientRepository;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::query()
            ->withCount(['tokens as active_tokens_count' => fn ($q) => $q->where('revoked', false)->where('expires_at', '>', now())])
            ->latest()
            ->paginate(15);

        return view('admin.clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('admin.clients.form', ['client' => new Client([
            'skip_authorization' => true,
            'show_on_dashboard' => true,
        ])]);
    }

    public function store(Request $request, ClientRepository $clients): RedirectResponse
    {
        $data = $this->validated($request);

        $client = $clients->createAuthorizationCodeGrantClient(
            $data['name'],
            $data['redirect_uris'],
            confidential: ! $request->boolean('public_client'),
        );

        $client->forceFill($this->extraAttributes($request, $data))->save();

        return redirect()->route('admin.clients.show', $client)
            ->with('plainSecret', $client->plainSecret)
            ->with('status', 'Aplikasi klien berhasil dibuat.');
    }

    public function show(Client $client): View
    {
        return view('admin.clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $this->validated($request);

        $client->forceFill([
            'name' => $data['name'],
            'redirect_uris' => $data['redirect_uris'],
            ...$this->extraAttributes($request, $data),
        ])->save();

        return redirect()->route('admin.clients.show', $client)->with('status', 'Aplikasi klien berhasil diperbarui.');
    }

    public function regenerateSecret(Client $client): RedirectResponse
    {
        abort_unless($client->confidential(), 422, 'Klien publik tidak memiliki secret.');

        $secret = Str::random(40);
        $client->forceFill(['secret' => $secret])->save();

        return redirect()->route('admin.clients.show', $client)
            ->with('plainSecret', $secret)
            ->with('status', 'Client Secret baru telah dibuat. Perbarui konfigurasi aplikasi klien.');
    }

    public function toggle(Client $client): RedirectResponse
    {
        if (! $client->revoked) {
            $client->tokens()->with('refreshToken')->each(function ($token) {
                $token->refreshToken?->revoke();
                $token->revoke();
            });
        }

        $client->forceFill(['revoked' => ! $client->revoked])->save();

        return back()->with('status', $client->revoked
            ? 'Aplikasi dinonaktifkan dan semua tokennya dicabut.'
            : 'Aplikasi diaktifkan kembali.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->tokens()->each(fn ($token) => $token->refreshToken()->delete());
        $client->tokens()->delete();
        $client->authCodes()->delete();
        $client->delete();

        return redirect()->route('admin.clients.index')->with('status', 'Aplikasi klien berhasil dihapus.');
    }

    /**
     * @return array{name: string, description: ?string, homepage_url: ?string, redirect_uris: string[]}
     */
    private function validated(Request $request): array
    {
        $request->merge([
            'redirect_uris' => collect(preg_split('/[\s,]+/', (string) $request->input('redirect_uris_text')))
                ->map(fn ($uri) => trim($uri))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'homepage_url' => ['nullable', 'url', 'max:255'],
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['url', 'max:2000'],
        ], [
            'redirect_uris.required' => 'Minimal satu Redirect URI wajib diisi.',
            'redirect_uris.*.url' => 'Redirect URI ":input" bukan URL yang valid.',
        ]);
    }

    private function extraAttributes(Request $request, array $data): array
    {
        return [
            'description' => $data['description'] ?? null,
            'homepage_url' => $data['homepage_url'] ?? null,
            'skip_authorization' => $request->boolean('skip_authorization'),
            'show_on_dashboard' => $request->boolean('show_on_dashboard'),
        ];
    }
}
