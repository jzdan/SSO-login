<?php

return [

    // Izinkan pengguna mendaftar sendiri di halaman SSO.
    'allow_registration' => env('SSO_ALLOW_REGISTRATION', true),

    // Masa berlaku access token (menit) dan refresh token (hari).
    'token_ttl' => env('SSO_TOKEN_TTL', 60),
    'refresh_token_ttl_days' => env('SSO_REFRESH_TOKEN_TTL_DAYS', 30),

];
