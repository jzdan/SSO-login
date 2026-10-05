@extends('layouts.auth')

@section('title', 'Verifikasi Email')
@section('heading', 'Verifikasi email Anda')
@section('subtitle', 'Akun belum bisa dipakai sebelum email diverifikasi.')

@section('content')
<p class="mb-2">Kami mengirim link verifikasi ke:</p>
<ul class="mb-3">
    @foreach (\App\Models\User::EMAIL_COLUMNS as $column => $verifiedAt)
        @if ($user->{$column})
            <li>
                <span class="fw-semibold">{{ $user->{$column} }}</span>
                @if ($user->{$verifiedAt})
                    <span class="badge badge-soft-success ms-1">Terverifikasi</span>
                @else
                    <span class="badge badge-soft-warning ms-1">Belum</span>
                @endif
            </li>
        @endif
    @endforeach
</ul>
<p class="text-body-secondary small mb-4">
    Buka email tersebut lalu klik tombol <strong>Verifikasi Email</strong>. Link berlaku
    {{ \App\Notifications\VerifyEmailAddress::EXPIRE_MINUTES }} menit. Jika tidak ada di inbox, cek folder spam.
</p>

<form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="bi bi-envelope-arrow-up me-1"></i> Kirim ulang link verifikasi
    </button>
</form>

<p class="text-center text-body-secondary small mt-4 mb-0">
    Sudah verifikasi? <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Masuk</a>
</p>
@endsection
