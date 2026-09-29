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

    <div class="mb-3">
        <label for="email" class="form-label fw-medium">Email</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" placeholder="nama@contoh.com" required autocomplete="username">
        </div>
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
