@extends('layouts.app')

@section('title', $client->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}" class="text-decoration-none">Klien SSO</a></li>
    <li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@php($sso = rtrim(config('app.url'), '/'))

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <span class="app-avatar" style="width:52px;height:52px;background:#4f46e5">{{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}</span>
        <div>
            <h1 class="d-flex align-items-center gap-2">
                {{ $client->name }}
                @if ($client->revoked)
                    <span class="badge badge-soft-danger fs-6">Nonaktif</span>
                @else
                    <span class="badge badge-soft-success fs-6">Aktif</span>
                @endif
            </h1>
            <p>{{ $client->description ?: ($client->appUrl() ?? 'Tanpa deskripsi') }}</p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> Ubah</a>
        <div class="dropdown">
            <button class="btn btn-light" data-bs-toggle="dropdown" aria-label="Aksi lain"><i class="bi bi-three-dots-vertical"></i></button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li>
                    <form method="POST" action="{{ route('admin.clients.toggle', $client) }}"
                          data-confirm="{{ $client->revoked ? 'Aktifkan kembali aplikasi ini?' : 'Nonaktifkan aplikasi ini? Semua pengguna akan logout dari aplikasi tersebut.' }}">
                        @csrf
                        @method('PATCH')
                        <button class="dropdown-item py-2">
                            <i class="bi {{ $client->revoked ? 'bi-play-circle text-success' : 'bi-pause-circle text-warning' }} me-2"></i>
                            {{ $client->revoked ? 'Aktifkan' : 'Nonaktifkan' }}
                        </button>
                    </form>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" data-confirm="Hapus aplikasi ini secara permanen?">
                        @csrf
                        @method('DELETE')
                        <button class="dropdown-item py-2 text-danger"><i class="bi bi-trash me-2"></i>Hapus permanen</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</div>

@if (session('plainSecret'))
    <div class="alert alert-warning border-0 shadow-sm">
        <div class="d-flex gap-2 mb-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><strong>Simpan Client Secret sekarang.</strong> Secret disimpan dalam bentuk hash dan tidak akan ditampilkan lagi.</div>
        </div>
        <div class="input-group">
            <input class="form-control font-monospace bg-white" value="{{ session('plainSecret') }}" readonly>
            <button class="btn btn-dark" type="button" data-copy="{{ session('plainSecret') }}"><i class="bi bi-copy me-1"></i> Salin</button>
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-key me-1 text-primary"></i> Kredensial</h2></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="stat-label mb-1">Client ID</div>
                    <div class="input-group input-group-sm">
                        <input class="form-control font-monospace" value="{{ $client->id }}" readonly>
                        <button class="btn btn-light border" data-copy="{{ $client->id }}"><i class="bi bi-copy"></i></button>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="stat-label mb-1">Client Secret</div>
                    @if ($client->confidential())
                        <div class="d-flex align-items-center gap-2">
                            <span class="font-monospace text-body-secondary">••••••••••••••••</span>
                            <form method="POST" action="{{ route('admin.clients.secret', $client) }}"
                                  data-confirm="Buat secret baru? Secret lama langsung tidak berlaku.">
                                @csrf
                                <button class="btn btn-sm btn-light border"><i class="bi bi-arrow-repeat me-1"></i> Buat ulang</button>
                            </form>
                        </div>
                    @else
                        <span class="badge badge-soft-secondary">Public client, gunakan PKCE</span>
                    @endif
                </div>

                <div class="mb-3">
                    <div class="stat-label mb-1">Redirect URI</div>
                    @foreach ($client->redirect_uris as $uri)
                        <div><code class="small">{{ $uri }}</code></div>
                    @endforeach
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="stat-label mb-1">Halaman persetujuan</div>
                        <div class="small">{{ $client->skip_authorization ? 'Dilewati' : 'Ditampilkan' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="stat-label mb-1">Dibuat</div>
                        <div class="small">{{ $client->created_at?->translatedFormat('d M Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-plug me-1 text-primary"></i> Integrasi</h2></div>
            <div class="card-body">
                @foreach ([
                    'Authorize URL' => $sso.'/oauth/authorize',
                    'Token URL' => $sso.'/oauth/token',
                    'User Info URL' => $sso.'/api/user',
                    'Logout URL' => $sso.'/logout?client_id='.$client->id.'&redirect_uri='.urlencode($client->appUrl() ?? ''),
                ] as $label => $url)
                    <div class="mb-3">
                        <div class="stat-label mb-1">{{ $label }}</div>
                        <div class="input-group input-group-sm">
                            <input class="form-control font-monospace" value="{{ $url }}" readonly>
                            <button class="btn btn-light border" data-copy="{{ $url }}"><i class="bi bi-copy"></i></button>
                        </div>
                    </div>
                @endforeach

                <div class="stat-label mb-1">Contoh <code>.env</code> aplikasi klien</div>
<pre class="bg-dark text-light rounded-3 p-3 mb-0 small"><code>SSO_BASE_URL={{ $sso }}
SSO_CLIENT_ID={{ $client->id }}
SSO_CLIENT_SECRET={{ session('plainSecret', $client->confidential() ? 'isi-dengan-secret' : '') }}
SSO_REDIRECT_URI={{ $client->redirect_uris[0] ?? '' }}</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection
