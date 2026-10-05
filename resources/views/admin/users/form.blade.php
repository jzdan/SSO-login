@extends('layouts.app', ['hideErrorSummary' => true])

@section('title', $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}" class="text-decoration-none">Pengguna</a></li>
    <li class="breadcrumb-item active">{{ $user->exists ? $user->name : 'Tambah' }}</li>
@endsection

@php($isSelf = auth()->user()->is($user))

@section('content')
<div class="page-header">
    <div>
        <h1>{{ $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna' }}</h1>
        <p>{{ $user->exists ? $user->email : 'Buat akun SSO baru.' }}</p>
    </div>
</div>

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
    @csrf
    @if ($user->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h2 class="card-title"><i class="bi bi-person me-1 text-primary"></i> Data akun</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-medium" for="name">Nama <span class="text-danger">*</span></label>
                            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="nip_lama">NIP lama</label>
                            <input class="form-control @error('nip_lama') is-invalid @enderror" id="nip_lama" name="nip_lama" value="{{ old('nip_lama', $user->nip_lama) }}" inputmode="numeric" maxlength="9" placeholder="9 digit">
                            @error('nip_lama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="nip_baru">NIP baru</label>
                            <input class="form-control @error('nip_baru') is-invalid @enderror" id="nip_baru" name="nip_baru" value="{{ old('nip_baru', $user->nip_baru) }}" inputmode="numeric" maxlength="12" placeholder="12 digit">
                            @error('nip_baru')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @foreach (['email' => ['Email Google', true, 'nama@gmail.com'], 'email_bps' => ['Email BPS', false, 'nama@bps.go.id']] as $field => [$label, $required, $placeholder])
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="{{ $field }}">
                                    {{ $label }} @if ($required)<span class="text-danger">*</span>@endif
                                    @if ($user->exists && $user->{$field})
                                        @if ($user->emailIsVerified($field))
                                            <span class="badge badge-soft-success ms-1">Terverifikasi</span>
                                        @else
                                            <span class="badge badge-soft-warning ms-1">Belum verifikasi</span>
                                        @endif
                                    @endif
                                </label>
                                <input type="email" class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $user->{$field}) }}" placeholder="{{ $placeholder }}" @required($required)>
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                        <div class="col-12">
                            <div class="form-text mt-0">Pengguna bisa login memakai NIP lama, NIP baru, email BPS, atau email Google. Mengganti email akan mereset status verifikasinya.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title"><i class="bi bi-key me-1 text-primary"></i> Password</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="password">Password @unless($user->exists)<span class="text-danger">*</span>@endunless</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" @required(! $user->exists)>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="password_confirmation">Konfirmasi password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-text">{{ $user->exists ? 'Kosongkan jika tidak ingin mengubah password.' : 'Minimal 8 karakter.' }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h2 class="card-title"><i class="bi bi-sliders me-1 text-primary"></i> Akses</h2></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $user->is_active)) @disabled($isSelf)>
                        <label class="form-check-label fw-medium" for="is_active">Akun aktif</label>
                        <div class="form-text mt-0">Akun nonaktif tidak bisa login dan langsung dikeluarkan dari semua aplikasi.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_admin" name="is_admin" value="1"
                               @checked(old('is_admin', $user->is_admin)) @disabled($isSelf)>
                        <label class="form-check-label fw-medium" for="is_admin">Administrator SSO</label>
                        <div class="form-text mt-0">Dapat mengelola klien dan pengguna.</div>
                    </div>
                    @if ($isSelf)
                        <div class="alert alert-info small border-0 mt-3 mb-0">Anda tidak dapat mengubah status atau peran akun sendiri.</div>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h2 class="card-title"><i class="bi bi-envelope-check me-1 text-primary"></i> Verifikasi email</h2></div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="mark_verified" name="mark_verified" value="1" @checked(old('mark_verified'))>
                        <label class="form-check-label fw-medium" for="mark_verified">Tandai email sudah terverifikasi</label>
                        <div class="form-text mt-0">Hanya untuk email yang Anda yakini milik pengguna (mis. dari data kepegawaian). Jika tidak dicentang, link verifikasi dikirim ke email pengguna.</div>
                    </div>
                    @if ($user->exists && $user->unverifiedEmailColumns())
                        <button type="submit" form="send-verification" class="btn btn-sm btn-outline-primary w-100 mt-3">
                            <i class="bi bi-send me-1"></i> Kirim ulang link verifikasi
                        </button>
                    @endif
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary py-2"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light py-2">Batal</a>
            </div>
        </div>
    </div>
</form>

@if ($user->exists)
    <form method="POST" action="{{ route('admin.users.verification', $user) }}" id="send-verification" class="d-none">@csrf</form>
@endif
@endsection
