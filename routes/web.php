<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

// Verifikasi email: dibuka dari link di email, bisa dalam keadaan belum login.
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
    ->middleware('throttle:3,1')->name('verification.send');
Route::get('/email/verify/{user}/{type}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

// Single logout: dipanggil dari aplikasi klien (GET) atau dari tombol logout SSO (POST).
Route::get('/logout', [LoginController::class, 'clientLogout'])->name('logout.client');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile/sessions', [ProfileController::class, 'revokeSessions'])->name('profile.sessions.revoke');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('clients', Admin\ClientController::class);
        Route::post('clients/{client}/secret', [Admin\ClientController::class, 'regenerateSecret'])->name('clients.secret');
        Route::patch('clients/{client}/toggle', [Admin\ClientController::class, 'toggle'])->name('clients.toggle');

        Route::resource('users', Admin\UserController::class)->except('show');
        Route::post('users/{user}/verification', [Admin\UserController::class, 'sendVerification'])->name('users.verification');
    });
});
