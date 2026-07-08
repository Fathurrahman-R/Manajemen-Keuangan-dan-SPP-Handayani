<?php

use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchApprovalSettingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\JenisTagihanController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KenaikanKelasController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PdfGeneratorController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PengeluaranRequestController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\ParentSearchController;
use App\Http\Controllers\AkunSiswaController;
use App\Http\Controllers\EmailOptOutController;
use App\Http\Controllers\MidtransAdminController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\MidtransTransactionController;
use App\Http\Controllers\NotificationLogController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaliController;
use Illuminate\Support\Facades\Route;

Route::post("/login", [AuthController::class, "login"]);

// Password reset routes (public, no auth required)
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::get('/reset-password/{token}', [PasswordResetController::class, 'validateToken']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Public unsubscribe routes (no auth required)
Route::get('/unsubscribe/{token}', [EmailOptOutController::class, 'show']);
Route::post('/unsubscribe/{token}', [EmailOptOutController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/logout', [AuthController::class, "logout"]);
    Route::get("/users/current", [UserController::class, "get"]);
    Route::patch('/users/current', [UserController::class, "updateCurrent"]);
    Route::patch('/users/current/email', [UserController::class, 'updateEmail']);
    Route::get('/users/current/notification-preferences', [UserController::class, 'getNotificationPreferences']);
    Route::put('/users/current/notification-preferences', [UserController::class, 'updateNotificationPreferences']);
    Route::post('/users/send-verification-otp', [UserController::class, 'sendVerificationOtp']);
    Route::post('/users/verify-email-otp', [UserController::class, 'verifyEmailOtp']);
    Route::post('/users/send-wali-otp', [UserController::class, 'sendWaliOtp']);
    Route::post('/users/verify-wali-otp', [UserController::class, 'verifyWaliOtp']);
    Route::post('/users/change-password', [UserController::class, 'changePassword']);

    // Siswa-accessible route (permission:view-tagihan-siswa middleware)
    Route::get('/tagihan/siswa', [TagihanController::class, 'siswaView'])->middleware('permission:view-tagihan-siswa');
    Route::get('/pembayaran/siswa', [PembayaranController::class, 'siswaView'])->middleware('permission:view-tagihan-siswa');
    
    // Shared Pembayaran routes (accessible by students or admin with permission)
    Route::get('/pembayaran/kwitansi/{kode_pembayaran}', [PdfGeneratorController::class, 'get'])->middleware('permission:print-kwitansi');

    // Dashboard routes
    Route::prefix('/dashboard')->group(function () {
        // Admin dashboard endpoints — require view-dashboard permission
        Route::middleware('permission:view-dashboard')->group(function () {
            Route::get('/summary', [DashboardController::class, 'summary']);
            Route::get('/all-time-summary', [DashboardController::class, 'allTimeSummary']);
            Route::get('/kas-summary', [DashboardController::class, 'kasSummary']);
            Route::get('/charts/pembayaran-bulanan', [DashboardController::class, 'chartPembayaranBulanan']);
            Route::get('/charts/tunggakan-jenjang', [DashboardController::class, 'chartTunggakanJenjang']);
            Route::get('/charts/kas-bulanan', [DashboardController::class, 'chartKasBulanan']);
            Route::get('/charts/status-tagihan', [DashboardController::class, 'chartStatusTagihan']);
            Route::get('/top-tunggakan', [DashboardController::class, 'topTunggakan']);
            Route::get('/tagihan-jatuh-tempo', [DashboardController::class, 'tagihanJatuhTempo']);
            Route::get('/pembayaran-terbaru', [DashboardController::class, 'pembayaranTerbaru']);
        });

        // Siswa/Wali dashboard endpoint — require view-own-billing permission
        Route::get('/siswa', [DashboardController::class, 'siswaDashboard'])
            ->middleware('permission:view-own-billing');
    });

    // Tahun Ajaran routes (needed by all roles for dropdown filters)
    Route::prefix('/tahun-ajaran')->group(function () {
        Route::get('/', [TahunAjaranController::class, 'index']);
        Route::post('/', [TahunAjaranController::class, 'store'])->middleware('permission:create-tahun-ajaran');
        Route::get('/{id}', [TahunAjaranController::class, 'show']);
        Route::put('/{id}', [TahunAjaranController::class, 'update'])->middleware('permission:update-tahun-ajaran');
        Route::delete('/{id}', [TahunAjaranController::class, 'destroy'])->middleware('permission:delete-tahun-ajaran');
        Route::patch('/{id}/activate', [TahunAjaranController::class, 'activate'])->middleware('permission:update-tahun-ajaran');
        Route::patch('/{id}/deactivate', [TahunAjaranController::class, 'deactivate'])->middleware('permission:update-tahun-ajaran');
    });

    // Admin panel routes — completely driven by specific permissions
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
        Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive'])
            ->middleware('permission:update-user')
            ->where('id', '[0-9]+');
    });

        // Role management — permission-based
        Route::prefix('/roles')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->middleware('permission:view-roles');
            Route::post('/', [RoleController::class, 'store'])->middleware('permission:create-role');
            Route::post('/attach', [RoleController::class, 'attach'])->middleware('permission:attach-role');
            Route::post('/detach', [RoleController::class, 'detach'])->middleware('permission:detach-role');
            Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('permission:view-roles');
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
            Route::get('/', [KelasController::class, 'all'])->middleware('permission:view-kelas');
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
        Route::get('/ayah', [ParentSearchController::class, 'ayah'])->middleware('permission:create-siswa');
        Route::get('/ayah/{id}', [ParentSearchController::class, 'showAyah'])->middleware('permission:create-siswa');

        // Ibu routes (parent search for siswa creation)
        Route::get('/ibu', [ParentSearchController::class, 'ibu'])->middleware('permission:create-siswa');
        Route::get('/ibu/{id}', [ParentSearchController::class, 'showIbu'])->middleware('permission:create-siswa');

        // Tagihan routes (admin)
        Route::get('/tagihan/grouped', [TagihanController::class, 'grouped'])->middleware('permission:view-tagihan');
        Route::get('/tagihan/export-pdf', [TagihanController::class, 'exportPdf'])->middleware('permission:view-tagihan');
        Route::get('/tagihan', [TagihanController::class, 'index'])->middleware('permission:view-tagihan');
        Route::prefix('/tagihan')->group(function () {
            Route::post('/', [TagihanController::class, 'create'])->middleware('permission:create-tagihan');
            Route::get('/{kode_tagihan}', [TagihanController::class, 'get'])->middleware('permission:read-tagihan');
            Route::patch('/{kode_tagihan}', [TagihanController::class, 'update'])->middleware('permission:update-tagihan');
            Route::delete('/{kode_tagihan}', [TagihanController::class, 'delete'])->middleware('permission:delete-tagihan');
        });

        // Pembayaran routes
        Route::prefix('/pembayaran')->group(function () {
            Route::get('/grouped', [PembayaranController::class, 'grouped'])->middleware('permission:view-pembayaran');
            Route::get('/', [PembayaranController::class, 'index'])->middleware('permission:view-pembayaran');
            Route::post('/batch', [PembayaranController::class, 'batchLunas'])->middleware('permission:create-pembayaran');
            Route::post('/bayar/{kode_tagihan}', [PembayaranController::class, 'bayar'])->middleware('permission:create-pembayaran');
            Route::post('/lunas/{kode_tagihan}', [PembayaranController::class, 'lunas'])->middleware('permission:create-pembayaran');
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


        // Setting routes (accessible to authenticated admin users)
        Route::get('/setting', [AppSettingController::class, 'get'])->middleware('permission:view-app-setting');
        Route::post('/setting/{id}', [AppSettingController::class, 'update'])->middleware('permission:update-app-setting');

        // Notification settings routes
        Route::get('/notification-settings', [NotificationSettingController::class, 'show'])->middleware('permission:view-notification-setting');
        Route::put('/notification-settings', [NotificationSettingController::class, 'update'])->middleware('permission:update-notification-setting');

        // Notification log routes
        Route::get('/notification-logs', [NotificationLogController::class, 'index'])->middleware('permission:view-notification-logs');
        Route::post('/notification-logs/retry', [NotificationLogController::class, 'retry'])->middleware('permission:view-notification-logs');

        // Laporan routes
        Route::prefix('/laporan')->group(function () {
            Route::get('/kas', [KasController::class, 'kasHarian'])->middleware('permission:view-kas-harian');
            Route::get('/kas/detail', [KasController::class, 'kasDetail'])->middleware('permission:view-kas-harian');
            Route::get('/rekap', [KasController::class, 'rekapBulanan'])->middleware('permission:view-rekap-bulanan');
            Route::get('/rekap/detail', [KasController::class, 'rekapDetail'])->middleware('permission:view-rekap-bulanan');
            Route::prefix('/export')->group(function () {
                Route::get('/kas', [PdfGeneratorController::class, 'exportKas'])->middleware('permission:export-laporan');
                Route::get('/rekap', [PdfGeneratorController::class, 'exportRekapBulanan'])->middleware('permission:export-laporan');
            });
        });

        // Kenaikan Kelas routes
        Route::prefix('/kenaikan-kelas')->group(function () {
            Route::post('/bulk-promotion', [KenaikanKelasController::class, 'bulkPromotion'])->middleware('permission:process-kenaikan-kelas');
            Route::post('/individual-promotion', [KenaikanKelasController::class, 'individualPromotion'])->middleware('permission:process-kenaikan-kelas');
            Route::post('/graduation', [KenaikanKelasController::class, 'graduation'])->middleware('permission:process-kenaikan-kelas');
            Route::post('/retention', [KenaikanKelasController::class, 'retention'])->middleware('permission:process-kenaikan-kelas');
            Route::post('/cross-level-transfer', [KenaikanKelasController::class, 'crossLevelTransfer'])->middleware('permission:process-kenaikan-kelas');
            Route::post('/{batchId}/undo', [KenaikanKelasController::class, 'undo'])->middleware('permission:undo-kenaikan-kelas');
            Route::get('/batches', [KenaikanKelasController::class, 'listBatches'])->middleware('permission:view-kenaikan-kelas');
            Route::get('/batches/{id}', [KenaikanKelasController::class, 'showBatch'])->middleware('permission:view-kenaikan-kelas');
            Route::get('/eligible-students', [KenaikanKelasController::class, 'eligibleStudents'])->middleware('permission:view-kenaikan-kelas');
            Route::get('/class-hierarchy', [KenaikanKelasController::class, 'classHierarchy'])->middleware('permission:view-kenaikan-kelas');
        });

        // Akun Siswa management routes
        Route::prefix('/akun-siswa')->group(function () {
            Route::get('/', [AkunSiswaController::class, 'index'])->middleware('permission:view-akun-siswa');
            Route::get('/unregistered', [AkunSiswaController::class, 'unregistered'])->middleware('permission:view-akun-siswa');
            Route::post('/bulk', [AkunSiswaController::class, 'bulkCreate'])->middleware('permission:generate-akun-siswa');
            Route::post('/{id}/reset-password', [AkunSiswaController::class, 'resetPassword'])->middleware('permission:generate-akun-siswa');
            Route::patch('/{id}/toggle-active', [AkunSiswaController::class, 'toggleActive'])->middleware('permission:generate-akun-siswa');
            Route::get('/credentials', [AkunSiswaController::class, 'credentials'])->middleware('permission:view-akun-siswa');
            Route::get('/credentials-pdf', [AkunSiswaController::class, 'credentialsPdf'])->middleware('permission:view-akun-siswa');
        });

        // Notification routes
        Route::prefix('/notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        });

        // Pengeluaran Request (Approval Workflow) routes
        Route::prefix('/pengeluaran-request')->group(function () {
            Route::get('/', [PengeluaranRequestController::class, 'index']);
            Route::get('/{id}', [PengeluaranRequestController::class, 'show'])->where('id', '[0-9]+');
            Route::post('/', [PengeluaranRequestController::class, 'store'])->middleware('permission:create-pengeluaran-request');
            Route::put('/{id}', [PengeluaranRequestController::class, 'update'])->middleware('permission:create-pengeluaran-request')->where('id', '[0-9]+');
            Route::delete('/{id}', [PengeluaranRequestController::class, 'destroy'])->middleware('permission:create-pengeluaran-request')->where('id', '[0-9]+');
            Route::post('/{id}/submit', [PengeluaranRequestController::class, 'submit'])->middleware('permission:create-pengeluaran-request')->where('id', '[0-9]+');
            Route::post('/{id}/approve', [PengeluaranRequestController::class, 'approve'])->middleware('permission:approve-pengeluaran')->where('id', '[0-9]+');
            Route::post('/{id}/reject', [PengeluaranRequestController::class, 'reject'])->middleware('permission:approve-pengeluaran')->where('id', '[0-9]+');
            Route::post('/{id}/disburse', [PengeluaranRequestController::class, 'disburse'])->middleware('permission:disburse-pengeluaran')->where('id', '[0-9]+');
        });

        // Branch Approval Settings
        Route::prefix('/branch-approval-settings')->group(function () {
            Route::get('/', [BranchApprovalSettingController::class, 'show'])->middleware('permission:view-app-setting');
            Route::put('/', [BranchApprovalSettingController::class, 'update'])->middleware('permission:update-app-setting');
        });

        // Branch routes
        Route::prefix('/branches')->group(function () {
            Route::get('/', [BranchController::class, 'index'])->middleware('permission:view-branch');
            Route::post('/', [BranchController::class, 'store'])->middleware('permission:create-branch');
            Route::get('/{id}', [BranchController::class, 'show'])->middleware('permission:read-branch');
            Route::put('/{id}', [BranchController::class, 'update'])->middleware('permission:update-branch');
            Route::delete('/{id}', [BranchController::class, 'destroy'])->middleware('permission:delete-branch');
        });

        // Import & Export routes
        Route::prefix('/import-export')->group(function () {
            // Export routes - require 'export-data' permission
            Route::middleware('permission:export-data')->group(function () {
                Route::post('/export/siswa', [ImportExportController::class, 'exportSiswa']);
                Route::post('/export/tagihan', [ImportExportController::class, 'exportTagihan']);
                Route::post('/export/pembayaran', [ImportExportController::class, 'exportPembayaran']);
                Route::post('/export/kas-harian', [ImportExportController::class, 'exportKasHarian']);
                Route::post('/export/rekap-bulanan', [ImportExportController::class, 'exportRekapBulanan']);
            });

            // Import routes - require 'import-data' permission
            Route::middleware('permission:import-data')->group(function () {
                Route::post('/import/siswa/upload', [ImportExportController::class, 'uploadSiswa']);
                Route::post('/import/siswa/confirm', [ImportExportController::class, 'confirmSiswa']);
                Route::post('/import/tagihan/upload', [ImportExportController::class, 'uploadTagihan']);
                Route::post('/import/tagihan/confirm', [ImportExportController::class, 'confirmTagihan']);
                Route::get('/import/template/siswa', [ImportExportController::class, 'templateSiswa']);
                Route::get('/import/template/tagihan', [ImportExportController::class, 'templateTagihan']);
                Route::get('/import/history', [ImportExportController::class, 'importHistory']);
                Route::post('/import/{batchId}/rollback', [ImportExportController::class, 'rollbackImport']);
            });

            // Job status - accessible with either permission
            Route::get('/job/{jobId}/status', [ImportExportController::class, 'jobStatus'])->middleware('permission:import-data|export-data');
        });
});

// ──────────────────────────────────────────────────────────────
// Midtrans Webhook - public, no Sanctum (protected by signature)
// ──────────────────────────────────────────────────────────────
Route::post('/midtrans/notification', [MidtransNotificationController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    // Portal Siswa - Midtrans
    Route::middleware('permission:pay-tagihan-online')->group(function () {
        Route::get('/midtrans/fee-channels', [MidtransTransactionController::class, 'feeChannels']);
        Route::post('/midtrans/transactions', [MidtransTransactionController::class, 'initiate']);
        Route::post('/midtrans/transactions/batch', [MidtransTransactionController::class, 'initiateBatch']);
        Route::get('/midtrans/transactions/{order_id}', [MidtransTransactionController::class, 'show']);
    });

    // Admin - Midtrans
    Route::middleware('permission:view-midtrans-transactions')->group(function () {
        Route::get('/midtrans/admin/transactions', [MidtransAdminController::class, 'index']);
        Route::get('/midtrans/admin/transactions/{order_id}', [MidtransAdminController::class, 'show']);
        Route::get('/midtrans/admin/transactions/{order_id}/logs', [MidtransAdminController::class, 'logs']);
    });
    Route::middleware('permission:sync-midtrans-transactions')->group(function () {
        Route::post('/midtrans/admin/transactions/{order_id}/sync', [MidtransAdminController::class, 'sync']);
    });
});
