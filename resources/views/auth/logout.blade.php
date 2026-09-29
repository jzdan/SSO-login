@extends('layouts.auth')

@section('title', 'Keluar')
@section('heading', 'Keluar dari SSO?')
@section('subtitle', 'Anda akan keluar dari semua aplikasi yang terhubung.')

@section('content')
<div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-body-tertiary mb-4">
    <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
    <div class="lh-sm">
        <div class="fw-semibold">{{ auth()->user()->name }}</div>
        <small class="text-body-secondary">{{ auth()->user()->email }}</small>
    </div>
</div>

<form method="POST" action="{{ route('logout') }}" class="d-grid gap-2">
    @csrf
    <button type="submit" class="btn btn-danger py-2"><i class="bi bi-box-arrow-right me-1"></i> Ya, keluar</button>
    <a href="{{ route('dashboard') }}" class="btn btn-light py-2">Batal</a>
</form>
@endsection
