<?php

use App\Http\Controllers\Api\UserInfoController;
use Illuminate\Support\Facades\Route;

// Endpoint yang dipanggil aplikasi klien dengan access token untuk mengambil data pengguna.
Route::middleware(['auth:api', 'active'])->group(function () {
    Route::get('/user', UserInfoController::class)->name('api.user');
});
