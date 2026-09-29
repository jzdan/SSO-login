@extends('layouts.auth')

@section('title', 'Izin Akses')
@section('heading', 'Izinkan akses?')
@section('subtitle')<strong>{{ $client->name }}</strong> ingin mengakses akun Anda.@endsection

@section('content')
@if ($client->description)
    <p class="small text-body-secondary fst-italic">{{ $client->description }}</p>
@endif

<div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-body-tertiary mb-4">
    <span class="avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
    <div class="lh-sm">
        <div class="fw-semibold">{{ $user->name }}</div>
        <small class="text-body-secondary">{{ $user->email }}</small>
    </div>
</div>

@if (count($scopes) > 0)
    <p class="small fw-semibold mb-2">Aplikasi ini akan dapat:</p>
    <ul class="list-unstyled small mb-4">
        @foreach ($scopes as $scope)
            <li class="d-flex gap-2 mb-2"><i class="bi bi-check-circle-fill text-success"></i>{{ $scope->description }}</li>
        @endforeach
    </ul>
@endif

<div class="row g-2">
    <div class="col">
        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button class="btn btn-light w-100 py-2">Tolak</button>
        </form>
    </div>
    <div class="col">
        <form method="POST" action="{{ route('passport.authorizations.approve') }}">
            @csrf
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button class="btn btn-primary w-100 py-2">Izinkan</button>
        </form>
    </div>
</div>
@endsection
