<?php

// Salin ke aplikasi KLIEN: config/sso.php

return [
    'base_url' => rtrim(env('SSO_BASE_URL', 'http://sso-login.test'), '/'),
    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'redirect_uri' => env('SSO_REDIRECT_URI'),

    // Seberapa sering (detik) aplikasi memeriksa apakah sesi SSO masih berlaku.
    'check_interval' => env('SSO_CHECK_INTERVAL', 60),
];
