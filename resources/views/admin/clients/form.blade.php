@extends('layouts.app', ['hideErrorSummary' => true])

@section('title', $client->exists ? 'Ubah Klien' : 'Tambah Klien')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}" class="text-decoration-none">Klien SSO</a></li>
    <li class="breadcrumb-item active">{{ $client->exists ? $client->name : 'Tambah' }}</li>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1>{{ $client->exists ? 'Ubah Aplikasi Klien' : 'Daftarkan Aplikasi Klien' }}</h1>
        <p>{{ $client->exists ? $client->name : 'Hubungkan website baru ke server SSO.' }}</p>
    </div>
</div>

<form method="POST" action="{{ $client->exists ? route('admin.clients.update', $client) : route('admin.clients.store') }}">
    @csrf
    @if ($client->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h2 class="card-title"><i class="bi bi-info-circle me-1 text-primary"></i> Informasi aplikasi</h2></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="name">Nama aplikasi <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                               value="{{ old('name', $client->name) }}" placeholder="mis. Sistem Kepegawaian" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium" for="description">Deskripsi</label>
                        <input class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                               value="{{ old('description', $client->description) }}" placeholder="Ditampilkan di dashboard pengguna">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label fw-medium" for="homepage_url">URL halaman utama</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-house"></i></span>
                            <input type="url" class="form-control @error('homepage_url') is-invalid @enderror" id="homepage_url" name="homepage_url"
                                   value="{{ old('homepage_url', $client->homepage_url) }}" placeholder="https://app1.contoh.test">
                            @error('homepage_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">Tautan yang dibuka dari dashboard. Kosongkan untuk memakai domain dari Redirect URI.</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title"><i class="bi bi-arrow-return-left me-1 text-primary"></i> Redirect URI</h2></div>
                <div class="card-body">
                    <label class="form-label fw-medium" for="redirect_uris_text">URL callback <span class="text-danger">*</span></label>
                    <textarea class="form-control font-monospace small @error('redirect_uris') is-invalid @enderror @error('redirect_uris.*') is-invalid @enderror"
                              id="redirect_uris_text" name="redirect_uris_text" rows="4" required
                              placeholder="https://app1.contoh.test/auth/sso/callback">{{ old('redirect_uris_text', implode("\n", $client->redirect_uris ?? [])) }}</textarea>
                    <div class="form-text">Satu URL per baris. Harus sama persis dengan yang dikirim aplikasi klien.</div>
                    @error('redirect_uris')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @foreach ($errors->get('redirect_uris.*') as $messages)
                        <div class="invalid-feedback d-block">{{ $messages[0] }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="card-title"><i class="bi bi-sliders me-1 text-primary"></i> Pengaturan</h2></div>
                <div class="card-body">
                    @unless ($client->exists)
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="public_client" name="public_client" value="1" @checked(old('public_client'))>
                            <label class="form-check-label fw-medium" for="public_client">Klien publik</label>
                            <div class="form-text mt-0">Tanpa secret, wajib PKCE. Untuk SPA atau aplikasi mobile.</div>
                        </div>
                    @endunless

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="skip_authorization" name="skip_authorization" value="1"
                               @checked(old('skip_authorization', $client->skip_authorization))>
                        <label class="form-check-label fw-medium" for="skip_authorization">Aplikasi tepercaya</label>
                        <div class="form-text mt-0">Lewati halaman "Izinkan akses?" sehingga pengguna langsung masuk.</div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="show_on_dashboard" name="show_on_dashboard" value="1"
                               @checked(old('show_on_dashboard', $client->show_on_dashboard))>
                        <label class="form-check-label fw-medium" for="show_on_dashboard">Tampilkan di dashboard</label>
                        <div class="form-text mt-0">Muncul di daftar "Aplikasi Anda".</div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary py-2"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="{{ $client->exists ? route('admin.clients.show', $client) : route('admin.clients.index') }}" class="btn btn-light py-2">Batal</a>
            </div>
        </div>
    </div>
</form>
@endsection
