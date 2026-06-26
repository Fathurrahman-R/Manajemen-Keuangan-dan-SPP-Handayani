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
    | fee_flat     : Biaya admin flat fallback (Rupiah) saat channel tidak
    |                dikenal.
    | fee_channels : Biaya admin per kanal pembayaran. Mendukung dua tipe:
    |                  type=flat   → ['type'=>'flat',  'amount'=>4000]
    |                  type=percent→ ['type'=>'percent','percent'=>0.7,'flat'=>0]
    |                Persentase dihitung dari `amount_paid`, optional
    |                ditambah komponen flat (mis. credit card 2.9% + 2000).
    |                Nilai default mengikuti tarif standar Midtrans:
    |                  https://midtrans.com/id/pricing
    |                Override per kanal lewat .env, mis.
    |                HANDAYANI_MIDTRANS_FEE_QRIS_PERCENT=0.7
    | min_amount   : Minimum nominal pembayaran (Rp 10.000, asumsi A2).
    | expiry_hours : Kadaluarsa transaksi sejak inisiasi (24 jam, asumsi A3).
    |
    */

    'fee_flat' => (int) env('HANDAYANI_MIDTRANS_FEE_FLAT', 4000),

    'fee_channels' => [
        'qris' => [
            'label'   => 'QRIS',
            'type'    => 'percent',
            'percent' => (float) env('HANDAYANI_MIDTRANS_FEE_QRIS_PERCENT', 0.7),
            'flat'    => (int) env('HANDAYANI_MIDTRANS_FEE_QRIS_FLAT', 0),
        ],
        'bank_transfer' => [
            'label'  => 'Bank Transfer / Virtual Account',
            'type'   => 'flat',
            'amount' => (int) env('HANDAYANI_MIDTRANS_FEE_BANK_TRANSFER', 4000),
        ],
        'gopay' => [
            'label'   => 'GoPay',
            'type'    => 'percent',
            'percent' => (float) env('HANDAYANI_MIDTRANS_FEE_GOPAY_PERCENT', 2.0),
            'flat'    => (int) env('HANDAYANI_MIDTRANS_FEE_GOPAY_FLAT', 0),
        ],
        'shopeepay' => [
            'label'   => 'ShopeePay',
            'type'    => 'percent',
            'percent' => (float) env('HANDAYANI_MIDTRANS_FEE_SHOPEEPAY_PERCENT', 2.0),
            'flat'    => (int) env('HANDAYANI_MIDTRANS_FEE_SHOPEEPAY_FLAT', 0),
        ],
        'credit_card' => [
            'label'   => 'Kartu Kredit',
            'type'    => 'percent',
            'percent' => (float) env('HANDAYANI_MIDTRANS_FEE_CREDIT_CARD_PERCENT', 2.9),
            'flat'    => (int) env('HANDAYANI_MIDTRANS_FEE_CREDIT_CARD_FLAT', 2000),
        ],
        'other' => [
            'label'  => 'Lainnya',
            'type'   => 'flat',
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
