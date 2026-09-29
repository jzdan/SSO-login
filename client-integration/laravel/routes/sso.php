<?php

// Tambahkan isi file ini ke routes/web.php aplikasi KLIEN.

use App\Http\Controllers\Auth\SsoController;
use App\Http\Middleware\EnsureSsoSession;
use Illuminate\Support\Facades\Route;

Route::get('/login', [SsoController::class, 'redirect'])->name('login');
Route::get('/auth/sso/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::get('/auth/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');
Route::post('/logout', [SsoController::class, 'logout'])->name('logout');

// Semua halaman yang membutuhkan login:
Route::middleware(EnsureSsoSession::class)->group(function () {
    Route::get('/', fn () => view('welcome'))->name('home');
    // Route::get('/dashboard', ...);
});
