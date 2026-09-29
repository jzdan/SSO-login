@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $palette = ['#4f46e5', '#7c3aed', '#059669', '#db2777', '#ea580c', '#0891b2', '#2563eb', '#9333ea'];
    $colorFor = fn ($id) => $palette[crc32((string) $id) % count($palette)];
    $hour = now()->hour;
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

@section('content')
{{-- Banner sambutan --}}
<div class="card welcome-card mb-4">
    <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="h4 fw-bold mb-1">{{ $greeting }}, {{ auth()->user()->name }}!</h1>
            <p class="text-muted-light mb-0">
                Anda terhubung ke <strong class="text-white">{{ count($activeClientIds) }}</strong> dari {{ $apps->count() }} aplikasi.
                Buka aplikasi mana pun tanpa login ulang.
            </p>
        </div>
        <div class="text-end d-none d-md-block">
            <div class="fw-semibold">{{ now()->translatedFormat('l') }}</div>
            <div class="text-muted-light small">{{ now()->translatedFormat('d F Y') }}</div>
        </div>
    </div>
</div>

@if ($admin)
    @php($s = $admin['stats'])
    {{-- Statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-icon soft-primary"><i class="bi bi-people"></i></span>
                    <div>
                        <div class="stat-label">Total Pengguna</div>
                        <div class="stat-value">{{ number_format($s['users']) }}</div>
                        <div class="stat-meta">
                            <span class="text-success fw-semibold">+{{ $s['users_new'] }}</span> dalam 7 hari
                            @if ($s['users_inactive']) · {{ $s['users_inactive'] }} nonaktif @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-icon soft-success"><i class="bi bi-grid-1x2"></i></span>
                    <div>
                        <div class="stat-label">Klien SSO Aktif</div>
                        <div class="stat-value">{{ number_format($s['clients']) }}</div>
                        <div class="stat-meta">{{ $s['clients_revoked'] }} dinonaktifkan</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-icon soft-info"><i class="bi bi-broadcast"></i></span>
                    <div>
                        <div class="stat-label">Sesi Aplikasi Aktif</div>
                        <div class="stat-value">{{ number_format($s['sessions']) }}</div>
                        <div class="stat-meta">{{ $s['online_users'] }} pengguna terhubung</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <span class="stat-icon soft-warning"><i class="bi bi-box-arrow-in-right"></i></span>
                    <div>
                        <div class="stat-label">Login Hari Ini</div>
                        <div class="stat-value">{{ number_format($s['logins_today']) }}</div>
                        <div class="stat-meta">Kemarin: {{ number_format($s['logins_yesterday']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Grafik login --}}
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Login SSO per hari</h2>
                        <small class="text-body-secondary">14 hari terakhir</small>
                    </div>
                    <span class="badge badge-soft-primary">{{ number_format($admin['chart']->sum('value')) }} login</span>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px">
                        <canvas id="loginChart" role="img" aria-label="Grafik batang jumlah login SSO per hari selama 14 hari terakhir"></canvas>
                    </div>
                    <table class="visually-hidden">
                        <caption>Login SSO per hari</caption>
                        <tr><th>Tanggal</th><th>Login</th></tr>
                        @foreach ($admin['chart'] as $point)
                            <tr><td>{{ $point['label'] }}</td><td>{{ $point['value'] }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>

        {{-- Aplikasi terpopuler --}}
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Aplikasi terpopuler</h2>
                        <small class="text-body-secondary">Berdasarkan sesi aktif</small>
                    </div>
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-light">Semua</a>
                </div>
                @php($maxSessions = max(1, $admin['popularApps']->max('sessions') ?? 1))
                <ul class="list-activity">
                    @forelse ($admin['popularApps'] as $app)
                        <li>
                            <span class="app-avatar" style="width:36px;height:36px;font-size:.95rem;background: {{ $colorFor($app->id) }}">
                                {{ mb_strtoupper(mb_substr($app->name, 0, 1)) }}
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between small mb-1">
                                    <a href="{{ route('admin.clients.show', $app) }}" class="fw-semibold text-body text-decoration-none text-truncate">{{ $app->name }}</a>
                                    <span class="text-body-secondary ms-2">{{ $app->sessions }}</span>
                                </div>
                                <div class="progress progress-thin">
                                    <div class="progress-bar" style="width: {{ round($app->sessions / $maxSessions * 100) }}%"></div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="empty-state d-block border-0">
                            <i class="bi bi-grid-1x2"></i>
                            <p class="small mt-2 mb-2">Belum ada klien SSO.</p>
                            <a href="{{ route('admin.clients.create') }}" class="btn btn-sm btn-primary">Tambah Klien</a>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Aktivitas login terbaru --}}
    <div class="card mb-4">
        <div class="card-header">
            <div>
                <h2 class="card-title">Aktivitas login terbaru</h2>
                <small class="text-body-secondary">Login ke server SSO</small>
            </div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light text-nowrap">Kelola pengguna</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>Pengguna</th><th class="d-none d-md-table-cell">Perangkat</th><th class="d-none d-md-table-cell">Alamat IP</th><th class="text-end">Waktu</th></tr>
                </thead>
                <tbody>
                    @forelse ($admin['recentActivities'] as $activity)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-sm">{{ mb_strtoupper(mb_substr($activity->user?->name ?? '?', 0, 1)) }}</span>
                                    <div class="lh-sm">
                                        <div class="fw-semibold">{{ $activity->user?->name ?? 'Pengguna dihapus' }}</div>
                                        <small class="text-body-secondary">{{ $activity->user?->email }}</small>
                                        <small class="d-block d-md-none text-body-secondary">{{ $activity->device() }} · {{ $activity->ip_address }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell"><i class="bi bi-laptop me-1 text-body-secondary"></i>{{ $activity->device() }}</td>
                            <td class="d-none d-md-table-cell"><code>{{ $activity->ip_address }}</code></td>
                            <td class="text-end text-body-secondary small" title="{{ $activity->created_at }}">{{ $activity->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-body-secondary py-4">Belum ada aktivitas login.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Aplikasi --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 fw-bold mb-0">Aplikasi Anda</h2>
        <small class="text-body-secondary">Klik untuk membuka, Anda akan otomatis masuk.</small>
    </div>
</div>

@if ($apps->isEmpty())
    <div class="card mb-4">
        <div class="empty-state">
            <i class="bi bi-grid-3x3-gap"></i>
            <p class="fw-semibold text-body mt-3 mb-1">Belum ada aplikasi yang terhubung</p>
            @if (auth()->user()->is_admin)
                <p class="small">Daftarkan aplikasi klien pertama Anda.</p>
                <a href="{{ route('admin.clients.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Tambah Klien</a>
            @else
                <p class="small mb-0">Hubungi administrator.</p>
            @endif
        </div>
    </div>
@else
    <div class="row g-3 mb-4">
        @foreach ($apps as $app)
            <div class="col-sm-6 col-lg-4 col-xxl-3">
                <a href="{{ $app->appUrl() }}" target="_blank" rel="noopener" class="text-decoration-none">
                    <div class="card app-tile">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="app-avatar" style="background: {{ $colorFor($app->id) }}">
                                    {{ mb_strtoupper(mb_substr($app->name, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-body text-truncate">{{ $app->name }}</div>
                                    <small class="text-body-secondary text-truncate d-block">{{ parse_url($app->appUrl(), PHP_URL_HOST) }}</small>
                                </div>
                            </div>
                            <p class="small text-body-secondary mb-3" style="min-height: 2.5em">{{ $app->description ?: 'Tidak ada deskripsi.' }}</p>
                            <div class="d-flex justify-content-between align-items-center small">
                                @if (in_array($app->id, $activeClientIds))
                                    <span class="text-success"><span class="status-dot bg-success me-1"></span>Sedang login</span>
                                @else
                                    <span class="text-body-secondary"><span class="status-dot bg-secondary-subtle me-1"></span>Belum login</span>
                                @endif
                                <span class="text-primary fw-semibold">Buka <i class="bi bi-arrow-up-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif

@unless ($admin)
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Riwayat login Anda</h2>
            <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-light">Keamanan akun</a>
        </div>
        <ul class="list-activity">
            @forelse ($myActivities as $activity)
                <li>
                    <span class="stat-icon soft-secondary" style="width:36px;height:36px;font-size:1rem"><i class="bi bi-laptop"></i></span>
                    <div class="flex-grow-1 lh-sm">
                        <div class="fw-semibold small">{{ $activity->device() }}</div>
                        <small class="text-body-secondary">{{ $activity->ip_address }}</small>
                    </div>
                    <small class="text-body-secondary">{{ $activity->created_at->diffForHumans() }}</small>
                </li>
            @empty
                <li class="text-body-secondary small">Belum ada riwayat login.</li>
            @endforelse
        </ul>
    </div>
@endunless
@endsection

@if ($admin)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const data = @json($admin['chart']);
            new Chart(document.getElementById('loginChart'), {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Login',
                        data: data.map(d => d.value),
                        backgroundColor: '#4f46e5',
                        hoverBackgroundColor: '#3730a3',
                        borderRadius: { topLeft: 4, topRight: 4 },
                        borderSkipped: 'bottom',
                        maxBarThickness: 28,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#111827', padding: 10, cornerRadius: 8, displayColors: false,
                            callbacks: { label: ctx => ctx.parsed.y + ' login' },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { color: '#6b7280', font: { size: 11 } } },
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: '#f0f1f5' },
                            ticks: { color: '#6b7280', precision: 0, font: { size: 11 } },
                        },
                    },
                },
            });
        })();
    </script>
    @endpush
@endif
