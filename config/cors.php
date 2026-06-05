<?php

return [
    // Izinkan semua jalur API dan cookie Sanctum
    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['*'],

    // JALUR AMAN: Kita buka untuk kedua alamat agar Anda bebas pakai localhost atau 127.0.0.1
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    // Ini wajib TRUE agar token/cookie login bisa dikirim dari React ke Laravel
    'supports_credentials' => true,
];