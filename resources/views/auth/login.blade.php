@extends('layouts.auth')

@section('title', 'Masuk')
@section('heading', 'Selamat datang kembali')
@section('subtitle', 'Masuk ke akun Anda untuk melanjutkan.')

@section('content')
<form method="POST" action="{{ route('login') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label fw-medium">Email</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="nama@contoh.com" required autofocus autocomplete="username">
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-medium">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" id="password" name="password"
                   class="form-control border-end-0 @error('password') is-invalid @enderror"
                   placeholder="Masukkan password" required autocomplete="current-password">
            <button class="input-group-text" type="button" id="togglePassword" tabindex="-1" aria-label="Tampilkan password">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
        <label class="form-check-label" for="remember">Ingat saya</label>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2">
        Masuk <i class="bi bi-arrow-right ms-1"></i>
    </button>

    @if (config('sso.allow_registration'))
        <p class="text-center text-body-secondary small mt-4 mb-0">
            Belum punya akun? <a href="{{ route('register') }}" class="fw-semibold text-decoration-none">Daftar sekarang</a>
        </p>
    @endif
</form>
@endsection

@push('scripts')
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });
</script>
@endpush
