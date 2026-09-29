@extends('layouts.app', ['hideErrorSummary' => true])

@section('title', 'Profil & Keamanan')
@section('breadcrumb')
    <li class="breadcrumb-item">Akun</li>
    <li class="breadcrumb-item active">Profil &amp; Keamanan</li>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-body text-center py-4">
                <span class="avatar avatar-lg mb-3">{{ collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') }}</span>
                <h1 class="h5 fw-bold mb-0">{{ $user->name }}</h1>
                <p class="text-body-secondary small mb-2">{{ $user->email }}</p>
                <span class="badge {{ $user->is_admin ? 'badge-soft-primary' : 'badge-soft-secondary' }}">{{ $user->is_admin ? 'Administrator' : 'Pengguna' }}</span>
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between px-4"><span class="text-body-secondary">Terdaftar</span><span>{{ $user->created_at?->translatedFormat('d M Y') }}</span></li>
                <li class="list-group-item d-flex justify-content-between px-4"><span class="text-body-secondary">Login terakhir</span><span>{{ $user->last_login_at?->diffForHumans() ?? '-' }}</span></li>
                <li class="list-group-item d-flex justify-content-between px-4"><span class="text-body-secondary">Sesi aplikasi aktif</span><span class="fw-semibold">{{ $sessions->count() }}</span></li>
            </ul>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card mb-4">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-person me-1 text-primary"></i> Informasi profil</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="name">Nama</label>
                            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="email">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan perubahan</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-key me-1 text-primary"></i> Ubah password</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium" for="current_password">Password saat ini</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required autocomplete="current-password">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium" for="password">Password baru</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="new-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium" for="password_confirmation">Konfirmasi</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-shield-lock me-1"></i> Ubah password</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-display me-1 text-primary"></i> Sesi aplikasi aktif</h2>
                @if ($sessions->isNotEmpty())
                    <form method="POST" action="{{ route('profile.sessions.revoke') }}" data-confirm="Keluarkan akun Anda dari semua aplikasi?">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i> Cabut semua</button>
                    </form>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Aplikasi</th><th>Login sejak</th><th>Berlaku hingga</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $token)
                            <tr>
                                <td class="fw-semibold">{{ $token->client?->name ?? '-' }}</td>
                                <td class="text-body-secondary">{{ $token->created_at->diffForHumans() }}</td>
                                <td class="text-body-secondary">{{ $token->expires_at->translatedFormat('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-body-secondary py-4">Tidak ada sesi aplikasi aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
