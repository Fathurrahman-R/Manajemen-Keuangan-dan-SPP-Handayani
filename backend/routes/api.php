<?php

use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JenisTagihanController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KenaikanKelasController;
use App\Http\Controllers\PdfGeneratorController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\AyahController;
use App\Http\Controllers\IbuController;
use App\Http\Controllers\AkunSiswaController;
use App\Http\Controllers\EmailOptOutController;
use App\Http\Controllers\NotificationLogController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaliController;
use Illuminate\Support\Facades\Route;

Route::post("/login", [AuthController::class, "login"]);

// Public unsubscribe routes (no auth required)
Route::get('/unsubscribe/{token}', [EmailOptOutController::class, 'show']);
Route::post('/unsubscribe/{token}', [EmailOptOutController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/logout', [AuthController::class, "logout"]);
    Route::get("/users/current", [UserController::class, "get"]);
    Route::patch('/users/current', [UserController::class, "updateCurrent"]);
    Route::post('/users/change-password', [UserController::class, 'changePassword']);

    // Siswa-accessible route (role:siswa middleware)
    Route::get('/tagihan/siswa', [TagihanController::class, 'siswaView'])->middleware('role:siswa');

    // Admin panel routes — deny access to users with only "siswa" role
    Route::middleware('deny_siswa')->group(function () {
        // User management routes
        Route::prefix('/users')->group(function () {
            Route::get('/', [UserController::class, 'index'])
                ->middleware('permission:view-user');
            Route::post('/', [UserController::class, 'store'])
                ->middleware('permission:create-user');
            Route::get('/{id}', [UserController::class, 'show'])
                ->middleware('permission:read-user')
                ->where('id', '[0-9]+');
            Route::put('/{id}', [UserController::class, 'update'])
                ->middleware('permission:update-user')
                ->where('id', '[0-9]+');
            Route::delete('/{id}', [UserController::class, 'destroy'])
                ->middleware('permission:delete-user')
                ->where('id', '[0-9]+');
        });

        // Role management — permission-based
        Route::prefix('/roles')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->middleware('permission:view-roles');
            Route::post('/', [RoleController::class, 'store'])->middleware('permission:create-role');
            Route::post('/attach', [RoleController::class, 'attach'])->middleware('permission:attach-role');
            Route::post('/detach', [RoleController::class, 'detach'])->middleware('permission:detach-role');
            Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('permission:view-permissions');
            Route::get('/{id}', [RoleController::class, 'show'])->middleware('permission:view-roles');
            Route::put('/{id}', [RoleController::class, 'update'])->middleware('permission:update-role');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('permission:delete-role');
        });

        // Siswa routes
        Route::prefix('/siswa')->group(function () {
            Route::get('/{jenjang}', [SiswaController::class, 'index'])->middleware('permission:view-siswa');
            Route::post('/{jenjang}', [SiswaController::class, 'create'])->middleware('permission:create-siswa');
            Route::get('/{jenjang}/{id}', [SiswaController::class, 'get'])->middleware('permission:read-siswa');
            Route::put('/{jenjang}/{id}', [SiswaController::class, 'update'])->middleware('permission:update-siswa');
            Route::delete('/{jenjang}/{id}', [SiswaController::class, 'delete'])->middleware('permission:delete-siswa');
        });

        // Kelas routes
        Route::prefix('/kelas')->group(function () {
            Route::get('/{jenjang}', [KelasController::class, 'index'])->middleware('permission:view-kelas');
            Route::post('/{jenjang}', [KelasController::class, 'create'])->middleware('permission:create-kelas');
            Route::get('/{jenjang}/{id}', [KelasController::class, 'get'])->middleware('permission:read-kelas');
            Route::put('/{jenjang}/{id}', [KelasController::class, 'update'])->middleware('permission:update-kelas');
            Route::delete('/{jenjang}/{id}', [KelasController::class, 'delete'])->middleware('permission:delete-kelas');
        });

        // Kategori routes
        Route::prefix('/kategori')->group(function () {
            Route::get('/', [KategoriController::class, 'index'])->middleware('permission:view-kategori');
            Route::post('/', [KategoriController::class, 'create'])->middleware('permission:create-kategori');
            Route::get('/{id}', [KategoriController::class, 'get'])->middleware('permission:read-kategori');
            Route::put('/{id}', [KategoriController::class, 'update'])->middleware('permission:update-kategori');
            Route::delete('/{id}', [KategoriController::class, 'delete'])->middleware('permission:delete-kategori');
        });

        // Wali routes (related to siswa management)
        Route::prefix('/wali')->group(function () {
            Route::get('/', [WaliController::class, 'index'])->middleware('permission:view-siswa');
            Route::post('/', [WaliController::class, 'create'])->middleware('permission:create-siswa');
            Route::get('/{id}', [WaliController::class, 'get'])->middleware('permission:read-siswa');
            Route::put('/{id}', [WaliController::class, 'update'])->middleware('permission:update-siswa');
            Route::delete('/{id}', [WaliController::class, 'delete'])->middleware('permission:delete-siswa');
        });

        // Ayah routes (parent search for siswa creation)
        Route::get('/ayah', [AyahController::class, 'index'])->middleware('permission:create-siswa');

        // Ibu routes (parent search for siswa creation)
        Route::get('/ibu', [IbuController::class, 'index'])->middleware('permission:create-siswa');

        // Tagihan routes (admin)
        Route::get('/tagihan/grouped', [TagihanController::class, 'grouped'])->middleware('permission:view-tagihan');
        Route::get('/tagihan', [TagihanController::class, 'index'])->middleware('permission:view-tagihan');
        Route::prefix('/tagihan')->group(function () {
            Route::post('/', [TagihanController::class, 'create'])->middleware('permission:create-tagihan');
            Route::get('/{kode_tagihan}', [TagihanController::class, 'get'])->middleware('permission:read-tagihan');
            Route::patch('/{kode_tagihan}', [TagihanController::class, 'update'])->middleware('permission:update-tagihan');
            Route::delete('/{kode_tagihan}', [TagihanController::class, 'delete'])->middleware('permission:delete-tagihan');
        });

        // Pembayaran routes
        Route::prefix('/pembayaran')->group(function () {
            Route::get('/', [PembayaranController::class, 'index'])->middleware('permission:view-pembayaran');
            Route::post('/batch', [PembayaranController::class, 'batchLunas'])->middleware('permission:view-pembayaran');
            Route::post('/bayar/{kode_tagihan}', [PembayaranController::class, 'bayar'])->middleware('permission:view-pembayaran');
            Route::post('/lunas/{kode_tagihan}', [PembayaranController::class, 'lunas'])->middleware('permission:view-pembayaran');
            Route::get('/kwitansi/{kode_pembayaran}', [PdfGeneratorController::class, 'get'])->middleware('permission:print-kwitansi');
            Route::delete('/{kode_pembayaran}', [PembayaranController::class, 'delete'])->middleware('permission:delete-pembayaran');
        });

        // Pengeluaran routes
        Route::prefix('/pengeluaran')->group(function () {
            Route::get('/', [PengeluaranController::class, 'index'])->middleware('permission:view-pengeluaran');
            Route::post('/', [PengeluaranController::class, 'create'])->middleware('permission:create-pengeluaran');
            Route::get('/{id}', [PengeluaranController::class, 'get'])->middleware('permission:read-pengeluaran');
            Route::put('/{id}', [PengeluaranController::class, 'update'])->middleware('permission:update-pengeluaran');
            Route::delete('/{id}', [PengeluaranController::class, 'delete'])->middleware('permission:delete-pengeluaran');
        });

        // Jenis Tagihan routes
        Route::prefix('/jenis-tagihan')->group(function () {
            Route::get('/', [JenisTagihanController::class, 'index'])->middleware('permission:view-jenis-tagihan');
            Route::post('/', [JenisTagihanController::class, 'create'])->middleware('permission:create-jenis-tagihan');
            Route::get('/{id}', [JenisTagihanController::class, 'get'])->middleware('permission:read-jenis-tagihan');
            Route::put('/{id}', [JenisTagihanController::class, 'update'])->middleware('permission:update-jenis-tagihan');
            Route::delete('/{id}', [JenisTagihanController::class, 'delete'])->middleware('permission:delete-jenis-tagihan');
        });

        // Tahun Ajaran routes
        Route::prefix('/tahun-ajaran')->group(function () {
            Route::get('/', [TahunAjaranController::class, 'index']);
            Route::post('/', [TahunAjaranController::class, 'store'])->middleware('permission:manage-tahun-ajaran');
            Route::get('/{id}', [TahunAjaranController::class, 'show']);
            Route::put('/{id}', [TahunAjaranController::class, 'update'])->middleware('permission:manage-tahun-ajaran');
            Route::delete('/{id}', [TahunAjaranController::class, 'destroy'])->middleware('permission:manage-tahun-ajaran');
            Route::patch('/{id}/activate', [TahunAjaranController::class, 'activate'])->middleware('permission:manage-tahun-ajaran');
            Route::patch('/{id}/deactivate', [TahunAjaranController::class, 'deactivate'])->middleware('permission:manage-tahun-ajaran');
        });

        // Setting routes (accessible to authenticated admin users)
        Route::get('/setting', [AppSettingController::class, 'get']);
        Route::post('/setting/{id}', [AppSettingController::class, 'update']);

        // Notification settings routes
        Route::get('/notification-settings', [NotificationSettingController::class, 'show']);
        Route::put('/notification-settings', [NotificationSettingController::class, 'update']);

        // Notification log routes
        Route::get('/notification-logs', [NotificationLogController::class, 'index']);
        Route::post('/notification-logs/retry', [NotificationLogController::class, 'retry']);

        // Laporan routes
        Route::prefix('/laporan')->group(function () {
            Route::get('/kas', [KasController::class, 'kasHarian'])->middleware('permission:view-kas-harian');
            Route::get('/rekap', [KasController::class, 'rekapBulanan'])->middleware('permission:view-rekap-bulanan');
            Route::prefix('/export')->group(function () {
                Route::get('/kas', [PdfGeneratorController::class, 'exportKas'])->middleware('permission:export-laporan');
                Route::get('/rekap', [PdfGeneratorController::class, 'exportRekapBulanan'])->middleware('permission:export-laporan');
            });
        });

        // Kenaikan Kelas routes
        Route::prefix('/kenaikan-kelas')->middleware('permission:manage-kenaikan-kelas')->group(function () {
            Route::post('/bulk-promotion', [KenaikanKelasController::class, 'bulkPromotion']);
            Route::post('/individual-promotion', [KenaikanKelasController::class, 'individualPromotion']);
            Route::post('/graduation', [KenaikanKelasController::class, 'graduation']);
            Route::post('/retention', [KenaikanKelasController::class, 'retention']);
            Route::post('/cross-level-transfer', [KenaikanKelasController::class, 'crossLevelTransfer']);
            Route::post('/{batchId}/undo', [KenaikanKelasController::class, 'undo']);
            Route::get('/batches', [KenaikanKelasController::class, 'listBatches']);
            Route::get('/batches/{id}', [KenaikanKelasController::class, 'showBatch']);
            Route::get('/eligible-students', [KenaikanKelasController::class, 'eligibleStudents']);
            Route::get('/class-hierarchy', [KenaikanKelasController::class, 'classHierarchy']);
        });

        // Akun Siswa management routes
        Route::prefix('/akun-siswa')->middleware('permission:manage-akun-siswa')->group(function () {
            Route::get('/', [AkunSiswaController::class, 'index']);
            Route::get('/unregistered', [AkunSiswaController::class, 'unregistered']);
            Route::post('/bulk', [AkunSiswaController::class, 'bulkCreate']);
            Route::post('/{id}/reset-password', [AkunSiswaController::class, 'resetPassword']);
            Route::patch('/{id}/toggle-active', [AkunSiswaController::class, 'toggleActive']);
            Route::get('/credentials', [AkunSiswaController::class, 'credentials']);
            Route::get('/credentials-pdf', [AkunSiswaController::class, 'credentialsPdf']);
        });
    });
});
