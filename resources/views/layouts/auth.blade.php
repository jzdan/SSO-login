@extends('layouts.base')

@section('body')
<div class="auth-page">
    <aside class="auth-aside">
        <div class="d-flex align-items-center gap-2">
            <span class="sidebar-logo"><i class="bi bi-shield-lock-fill"></i></span>
            <span class="fw-bold fs-5">{{ config('app.name') }}</span>
        </div>

        <div style="max-width: 440px">
            <h2 class="fw-bold mb-3" style="font-size: 2rem; line-height: 1.25">Satu akun untuk semua aplikasi Anda.</h2>
            <p class="mb-4" style="color: rgba(255,255,255,.8)">
                Masuk sekali, lalu akses seluruh website yang terhubung tanpa perlu login berulang kali.
            </p>

            <div class="auth-feature">
                <i class="bi bi-lightning-charge"></i>
                <div><div class="fw-semibold">Login sekali</div><small style="color: rgba(255,255,255,.75)">Akses semua aplikasi dengan satu sesi.</small></div>
            </div>
            <div class="auth-feature">
                <i class="bi bi-shield-check"></i>
                <div><div class="fw-semibold">Aman</div><small style="color: rgba(255,255,255,.75)">Standar OAuth2 dengan PKCE dan token terenkripsi.</small></div>
            </div>
            <div class="auth-feature mb-0">
                <i class="bi bi-box-arrow-right"></i>
                <div><div class="fw-semibold">Logout serentak</div><small style="color: rgba(255,255,255,.75)">Keluar dari satu tempat, keluar dari semua aplikasi.</small></div>
            </div>
        </div>

        <small style="color: rgba(255,255,255,.6)">&copy; {{ date('Y') }} {{ config('app.name') }}</small>
    </aside>

    <main class="auth-main">
        <div class="auth-box">
            <div class="text-center mb-4 d-lg-none">
                <span class="sidebar-logo text-white mb-2" style="width:48px;height:48px;font-size:1.4rem"><i class="bi bi-shield-lock-fill"></i></span>
                <div class="fw-bold fs-5">{{ config('app.name') }}</div>
            </div>

            <div class="card">
                <div class="card-body p-4 p-sm-5">
                    <h1 class="h4 fw-bold mb-1">@yield('heading')</h1>
                    <p class="text-body-secondary mb-4">@yield('subtitle')</p>

                    @include('partials.alerts')

                    @yield('content')
                </div>
            </div>
        </div>
    </main>
</div>
@endsection
