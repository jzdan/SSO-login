@extends('layouts.app')

@section('title', 'Klien SSO')
@section('breadcrumb')
    <li class="breadcrumb-item">Administrasi</li>
    <li class="breadcrumb-item active">Klien SSO</li>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1>Klien SSO</h1>
        <p>Website yang menggunakan server ini untuk login.</p>
    </div>
    <a href="{{ route('admin.clients.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Klien</a>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar klien <span class="badge badge-soft-secondary ms-1">{{ $clients->total() }}</span></h2>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Aplikasi</th>
                    <th>Client ID</th>
                    <th>Tipe</th>
                    <th class="text-center">Sesi aktif</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="app-avatar" style="width:38px;height:38px;font-size:1rem;background:#4f46e5">{{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}</span>
                                <div class="lh-sm min-w-0">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="fw-semibold text-body text-decoration-none">{{ $client->name }}</a>
                                    <div><small class="text-body-secondary">{{ $client->appUrl() ?? '—' }}</small></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-nowrap">
                            <code class="small">{{ \Illuminate\Support\Str::limit($client->id, 13, '…') }}</code>
                            <button class="btn btn-link btn-sm p-0 ms-1 text-body-secondary" data-copy="{{ $client->id }}" data-bs-toggle="tooltip" title="Salin Client ID"><i class="bi bi-copy"></i></button>
                        </td>
                        <td>
                            @if (! $client->hasGrantType('authorization_code'))
                                <span class="badge badge-soft-secondary">{{ implode(', ', $client->grant_types) }}</span>
                            @elseif ($client->confidential())
                                <span class="badge badge-soft-info"><i class="bi bi-lock me-1"></i>Confidential</span>
                            @else
                                <span class="badge badge-soft-secondary"><i class="bi bi-globe me-1"></i>Public (PKCE)</span>
                            @endif
                        </td>
                        <td class="text-center fw-semibold">{{ $client->active_tokens_count }}</td>
                        <td>
                            @if ($client->revoked)
                                <span class="badge badge-soft-danger"><span class="status-dot bg-danger me-1"></span>Nonaktif</span>
                            @else
                                <span class="badge badge-soft-success"><span class="status-dot bg-success me-1"></span>Aktif</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-light btn-icon" data-bs-toggle="tooltip" title="Detail"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-light btn-icon" data-bs-toggle="tooltip" title="Ubah"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-grid-1x2"></i>
                                <p class="fw-semibold text-body mt-3 mb-1">Belum ada klien SSO</p>
                                <p class="small">Daftarkan website pertama yang akan memakai SSO ini.</p>
                                <a href="{{ route('admin.clients.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Tambah Klien</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $clients->links() }}</div>
@endsection
