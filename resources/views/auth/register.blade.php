@extends('layouts.auth')

@section('title', 'Daftar')
@section('heading', 'Buat akun baru')
@section('subtitle', 'Satu akun untuk mengakses semua aplikasi.')

@section('content')
<form method="POST" action="{{ route('register') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label fw-medium">Nama lengkap</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror" required autofocus autocomplete="name">
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label for="nip_lama" class="form-label fw-medium">NIP lama <span class="text-body-secondary fw-normal">(opsional)</span></label>
            <input type="text" id="nip_lama" name="nip_lama" value="{{ old('nip_lama') }}" inputmode="numeric" maxlength="9"
                   class="form-control @error('nip_lama') is-invalid @enderror" placeholder="9 digit">
        </div>
        <div class="col-sm-6">
            <label for="nip_baru" class="form-label fw-medium">NIP baru <span class="text-body-secondary fw-normal">(opsional)</span></label>
            <input type="text" id="nip_baru" name="nip_baru" value="{{ old('nip_baru') }}" inputmode="numeric" maxlength="12"
                   class="form-control @error('nip_baru') is-invalid @enderror" placeholder="12 digit">
        </div>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label fw-medium">Email Google</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" placeholder="nama@gmail.com" required autocomplete="username">
        </div>
    </div>

    <div class="mb-3">
        <label for="email_bps" class="form-label fw-medium">Email BPS <span class="text-body-secondary fw-normal">(opsional)</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-building"></i></span>
            <input type="email" id="email_bps" name="email_bps" value="{{ old('email_bps') }}"
                   class="form-control @error('email_bps') is-invalid @enderror" placeholder="nama@bps.go.id">
        </div>
        <div class="form-text">Link verifikasi akan dikirim ke setiap email yang diisi.</div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <label for="password" class="form-label fw-medium">Password</label>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
        </div>
        <div class="col-sm-6">
            <label for="password_confirmation" class="form-label fw-medium">Konfirmasi</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control" required autocomplete="new-password">
        </div>
        <div class="col-12 mt-1"><div class="form-text">Minimal 8 karakter.</div></div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="bi bi-person-plus me-1"></i> Daftar
    </button>

    <p class="text-center text-body-secondary small mt-4 mb-0">
        Sudah punya akun? <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Masuk</a>
    </p>
</form>
@endsection
