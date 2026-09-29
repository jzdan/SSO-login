@extends('layouts.base')

@php
    $authUser = auth()->user();
    $initials = collect(explode(' ', $authUser->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $menu = [
        'Utama' => [
            ['route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
        ],
    ];
    if ($authUser->is_admin) {
        $menu['Administrasi'] = [
            ['route' => 'admin.clients.index', 'active' => 'admin.clients.*', 'icon' => 'bi-grid-1x2', 'label' => 'Klien SSO'],
            ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'bi-people', 'label' => 'Pengguna'],
        ];
    }
    $menu['Akun'] = [
        ['route' => 'profile.edit', 'active' => 'profile.*', 'icon' => 'bi-person-gear', 'label' => 'Profil & Keamanan'],
    ];
@endphp

@section('body')
<script>try { if (localStorage.getItem('sidebar-collapsed') === '1') document.body.classList.add('sidebar-collapsed'); } catch (e) {}</script>
<aside class="sidebar" id="sidebar">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <span class="sidebar-logo"><i class="bi bi-shield-lock-fill"></i></span>
        <span class="sidebar-brand-text">{{ config('app.name') }}<small>Single Sign-On</small></span>
    </a>

    <nav class="sidebar-nav">
        @foreach ($menu as $heading => $items)
            <div class="sidebar-heading">{{ $heading }}</div>
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                   class="sidebar-link @if(request()->routeIs($item['active'])) active @endif">
                    <i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <span class="avatar avatar-sm">{{ $initials }}</span>
            <div class="sidebar-user-info">
                <div class="fw-semibold text-truncate small">{{ $authUser->name }}</div>
                <small>{{ $authUser->is_admin ? 'Administrator' : 'Pengguna' }}</small>
            </div>
        </div>
    </div>
</aside>
<div class="sidebar-backdrop" data-sidebar-close></div>

<div class="main">
    <header class="topbar">
        <button class="topbar-toggle" type="button" data-sidebar-toggle aria-label="Buka/tutup menu">
            <i class="bi bi-list"></i>
        </button>

        <div class="me-auto min-w-0">
            <p class="topbar-title text-truncate">@yield('title')</p>
            @hasSection('breadcrumb')
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Beranda</a></li>
                        @yield('breadcrumb')
                    </ol>
                </nav>
            @endif
        </div>

        <div class="dropdown">
            <a href="#" class="topbar-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">{{ $initials }}</span>
                <span class="d-none d-sm-block lh-sm">
                    <span class="d-block fw-semibold small">{{ $authUser->name }}</span>
                    <span class="d-block text-body-secondary" style="font-size:.75rem">{{ $authUser->email }}</span>
                </span>
                <i class="bi bi-chevron-down small text-body-secondary d-none d-sm-inline"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="min-width: 220px">
                <li class="px-3 py-2 d-sm-none">
                    <div class="fw-semibold small">{{ $authUser->name }}</div>
                    <div class="text-body-secondary small">{{ $authUser->email }}</div>
                </li>
                <li><a class="dropdown-item py-2" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Profil &amp; Keamanan</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item py-2 text-danger"><i class="bi bi-box-arrow-right me-2"></i>Keluar dari semua aplikasi</button>
                    </form>
                </li>
            </ul>
        </div>
    </header>

    <main class="content">
        @include('partials.alerts')
        @yield('content')
    </main>

    <footer class="footer d-flex justify-content-between flex-wrap gap-2">
        <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
        <span>Single Sign-On · OAuth2</span>
    </footer>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const body = document.body;
        const desktop = window.matchMedia('(min-width: 992px)');

        document.querySelector('[data-sidebar-toggle]').addEventListener('click', function () {
            if (desktop.matches) {
                const collapsed = body.classList.toggle('sidebar-collapsed');
                try { localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
            } else {
                body.classList.toggle('sidebar-open');
            }
        });
        document.querySelector('[data-sidebar-close]').addEventListener('click', function () {
            body.classList.remove('sidebar-open');
        });
    })();
</script>
@endpush
