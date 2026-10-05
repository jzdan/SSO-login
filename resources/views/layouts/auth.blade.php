@extends('layouts.base')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}" rel="stylesheet">
@endpush

@section('body')
<div class="auth-shell">
    <aside class="auth-hero">
        <span class="auth-deco auth-deco-circle-1"></span>
        <span class="auth-deco auth-deco-circle-2"></span>
        <span class="auth-deco auth-deco-diamond"></span>

        <div class="auth-hero-content">
            <img src="{{ asset('image/logo.png') }}" alt="Single Sign On BPS Kabupaten Bangkalan" class="auth-logo">
            <h2 class="auth-hero-title">Satu Akun, Semua Layanan</h2>
            <p class="auth-hero-text">
                Masuk sekali dengan NIP atau email untuk mengakses seluruh aplikasi BPS Kabupaten Bangkalan, tanpa login berulang.
            </p>
            <ul class="auth-hero-points">
                <li><i class="bi bi-lightning-charge"></i> Login sekali</li>
                <li><i class="bi bi-shield-lock"></i> Aman &amp; terenkripsi</li>
                <li><i class="bi bi-box-arrow-right"></i> Logout serentak</li>
            </ul>
        </div>
    </aside>

    <main class="auth-panel">
        <span class="auth-panel-deco"></span>

        <div class="auth-form">
            <h1 class="auth-title">@yield('heading')</h1>
            <p class="auth-subtitle">@yield('subtitle')</p>

            @include('partials.alerts')

            @yield('content')

            <div class="auth-help">
                Butuh bantuan? Hubungi <span class="fw-semibold">administrator SSO</span>.
            </div>
        </div>
    </main>
</div>
@endsection
