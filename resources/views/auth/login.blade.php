@extends('layouts.auth')

@section('title', 'Masuk')
@section('heading', 'Selamat Datang')
@section('subtitle', 'Silakan masuk ke akun SSO Anda.')

@section('content')
<form method="POST" action="{{ route('login') }}" novalidate>
    @csrf

    <div class="mb-4">
        <label for="login" class="form-label">NIP / Email</label>
        <div class="auth-field">
            <i class="bi bi-person"></i>
            <input type="text" id="login" name="login" value="{{ old('login') }}"
                   class="form-control @error('login') is-invalid @enderror"
                   placeholder="NIP lama, NIP baru, atau email" required autofocus autocomplete="username">
        </div>
        <div class="form-text">NIP lama (9 digit), NIP baru (12 digit), email BPS, atau email Google.</div>
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <div class="auth-field">
            <i class="bi bi-lock"></i>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Masukkan password" required autocomplete="current-password">
            <button class="auth-field-action" type="button" id="togglePassword" aria-label="Tampilkan password">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
        <label class="form-check-label" for="remember">Ingat perangkat ini</label>
    </div>

    <button type="submit" class="btn btn-primary w-100">Masuk</button>

</form>
@endsection

@push('scripts')
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });
</script>
@endpush
