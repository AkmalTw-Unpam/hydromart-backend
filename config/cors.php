<?php

return [
    // Izinkan semua jalur API dan cookie Sanctum
    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['*'],

    // JALUR AMAN: Masukkan localhost DAN link domain Vercel Anda di sini
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'https://hydromart-frontend.vercel.app', // <-- Pindahkan ke sini
    ],

    'allowed_origins_patterns' => [], // <-- Kosongkan saja jika bukan regex

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    // Ini wajib TRUE agar token/cookie login bisa dikirim dari React ke Laravel
    'supports_credentials' => true,
];