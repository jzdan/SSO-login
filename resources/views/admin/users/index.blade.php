@extends('layouts.app')

@section('title', 'Pengguna')
@section('breadcrumb')
    <li class="breadcrumb-item">Administrasi</li>
    <li class="breadcrumb-item active">Pengguna</li>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1>Pengguna</h1>
        <p>Akun yang dapat login ke semua aplikasi klien.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Tambah Pengguna</a>
</div>

<div class="card">
    <div class="card-header flex-wrap">
        <h2 class="card-title">Daftar pengguna <span class="badge badge-soft-secondary ms-1">{{ $users->total() }}</span></h2>
        <form method="GET" class="d-flex gap-2" role="search">
            <div class="input-group input-group-sm" style="min-width: 240px">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama, NIP, atau email">
            </div>
            @if ($search)
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light">Reset</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Peran</th>
                    <th>Status</th>
                    <th>Login terakhir</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                <div class="lh-sm">
                                    <div class="fw-semibold">
                                        {{ $user->name }}
                                        @if (auth()->user()->is($user))<span class="badge badge-soft-secondary ms-1">Anda</span>@endif
                                    </div>
                                    <small class="text-body-secondary d-block">{{ $user->email }}@if ($user->email_bps) · {{ $user->email_bps }}@endif</small>
                                    @if ($user->nip_lama || $user->nip_baru)
                                        <small class="text-body-secondary">NIP {{ collect([$user->nip_lama, $user->nip_baru])->filter()->implode(' / ') }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($user->is_admin)
                                <span class="badge badge-soft-primary"><i class="bi bi-shield-check me-1"></i>Admin</span>
                            @else
                                <span class="badge badge-soft-secondary">Pengguna</span>
                            @endif
                        </td>
                        <td>
                            @switch ($user->status())
                                @case('aktif')
                                    <span class="badge badge-soft-success"><span class="status-dot bg-success me-1"></span>Aktif</span>
                                    @break
                                @case('belum_verifikasi')
                                    <span class="badge badge-soft-warning"><span class="status-dot bg-warning me-1"></span>Belum verifikasi</span>
                                    @break
                                @default
                                    <span class="badge badge-soft-danger"><span class="status-dot bg-danger me-1"></span>Nonaktif</span>
                            @endswitch
                        </td>
                        <td class="small text-body-secondary">{{ $user->last_login_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-light btn-icon" data-bs-toggle="tooltip" title="Ubah"><i class="bi bi-pencil"></i></a>
                            @unless (auth()->user()->is($user))
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                                      data-confirm="Hapus pengguna {{ $user->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-light btn-icon text-danger" data-bs-toggle="tooltip" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <p class="mt-3 mb-0">Tidak ada pengguna ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $users->links() }}</div>
@endsection
