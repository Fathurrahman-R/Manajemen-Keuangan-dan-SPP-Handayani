<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Logo sekolah disimpan di storage/app/public dan disajikan ke browser lewat
 * URL /storage/... yang bergantung pada symlink public/storage. Kalau symlink
 * itu putus, backend tetap menyimpan file dengan benar dan API tetap membalas
 * 200 — satu-satunya gejala adalah <img> yang 404 di halaman Pengaturan,
 * sehingga terlihat seolah "logo tidak pernah berubah".
 *
 * Kegagalan senyap itu pernah terjadi karena symlink dibuat dari dalam
 * container Linux ke bind-mount Windows: Windows menyimpannya sebagai reparse
 * point yang tidak bisa ditelusuri PHP (is_dir() false), jadi php artisan serve
 * melempar request /storage/... ke router dan berakhir 404.
 */
class PublicStorageLinkTest extends TestCase
{
    public function test_public_storage_link_resolves_to_storage_app_public(): void
    {
        $link = public_path('storage');

        $this->assertTrue(
            is_dir($link),
            'public/storage tidak terbaca PHP sebagai direktori. Semua URL /storage/... akan 404 '
                .'(logo sekolah tidak tampil). Jalankan `php artisan storage:link`; di Windows tanpa '
                ."Developer Mode pakai `cmd /c mklink /J \"{$link}\" \"".storage_path('app\\public').'"`.'
        );

        $probe = 'link-check-'.uniqid().'.txt';
        file_put_contents(storage_path('app/public/'.$probe), 'ok');

        try {
            $this->assertFileExists(
                $link.DIRECTORY_SEPARATOR.$probe,
                'public/storage ada tapi tidak menunjuk ke storage/app/public.'
            );
        } finally {
            @unlink(storage_path('app/public/'.$probe));
        }
    }
}
