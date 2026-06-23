<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi Midtrans Snap. Toggle utama dan webhook
    | dapat dimatikan secara independen tanpa redeploy.
    |
    */

    'enabled' => env('HANDAYANI_MIDTRANS_ENABLED', false),

    'webhook_enabled' => env('HANDAYANI_MIDTRANS_WEBHOOK_ENABLED', true),

    'environment' => env('MIDTRANS_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Server key, client key, dan merchant ID dibaca dari .env.
    | JANGAN pernah expose server_key ke respons HTTP.
    |
    */

    'server_key' => env('MIDTRANS_SERVER_KEY'),

    'client_key' => env('MIDTRANS_CLIENT_KEY'),

    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    |
    | fee_flat     : Biaya admin flat fallback (Rupiah).
    | fee_channels : Biaya admin per kanal pembayaran. Dipakai saat siswa
    |                memilih kanal di modal "Bayar Online". Override per kanal
    |                lewat .env, mis. HANDAYANI_MIDTRANS_FEE_QRIS=1000.
    | min_amount   : Minimum nominal pembayaran (Rp 10.000, asumsi A2).
    | expiry_hours : Kadaluarsa transaksi sejak inisiasi (24 jam, asumsi A3).
    |
    */

    'fee_flat' => (int) env('HANDAYANI_MIDTRANS_FEE_FLAT', 4000),

    'fee_channels' => [
        'qris' => [
            'label' => 'QRIS',
            'amount' => (int) env('HANDAYANI_MIDTRANS_FEE_QRIS', 1000),
        ],
        'bank_transfer' => [
            'label' => 'Bank Transfer / Virtual Account',
            'amount' => (int) env('HANDAYANI_MIDTRANS_FEE_BANK_TRANSFER', 4000),
        ],
        'gopay' => [
            'label' => 'GoPay',
            'amount' => (int) env('HANDAYANI_MIDTRANS_FEE_GOPAY', 1500),
        ],
        'shopeepay' => [
            'label' => 'ShopeePay',
            'amount' => (int) env('HANDAYANI_MIDTRANS_FEE_SHOPEEPAY', 1500),
        ],
        'credit_card' => [
            'label' => 'Kartu Kredit',
            'amount' => (int) env('HANDAYANI_MIDTRANS_FEE_CREDIT_CARD', 5000),
        ],
        'other' => [
            'label' => 'Lainnya',
            'amount' => (int) env('HANDAYANI_MIDTRANS_FEE_FLAT', 4000),
        ],
    ],

    'default_channel' => env('HANDAYANI_MIDTRANS_DEFAULT_CHANNEL', 'qris'),

    'min_amount' => 10_000,

    'expiry_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Order ID & Logging
    |--------------------------------------------------------------------------
    |
    | order_prefix      : Prefix untuk order_id yang dikirim ke Midtrans.
    | log_retention_days: Jumlah hari penyimpanan log transaksi sebelum pruning.
    |
    */

    'order_prefix' => env('MIDTRANS_ORDER_PREFIX', 'HDY'),

    /*
    |--------------------------------------------------------------------------
    | Snap Callback URL
    |--------------------------------------------------------------------------
    |
    | URL yang dibuka di browser siswa setelah selesai / batal / error di
    | halaman Snap Midtrans. Default: beranda Portal di frontend-v2.
    |
    */

    'finish_url' => env('MIDTRANS_FINISH_URL', 'http://127.0.0.1:8000/portal/beranda'),

    'log_retention_days' => (int) env('MIDTRANS_LOG_RETENTION_DAYS', 180),

];
