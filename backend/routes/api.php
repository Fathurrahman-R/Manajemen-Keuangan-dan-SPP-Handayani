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
    Route::get('/users/current/siswa-detail', [UserController::class, 'siswaDetail']);
    Route::post('/users/send-verification-otp', [UserController::class, 'sendVerificationOtp']);
    Route::post('/users/verify-email-otp', [UserController::class, 'verifyEmailOtp']);
    Route::post('/users/send-wali-otp', [UserController::class, 'sendWaliOtp']);
    Route::post('/users/verify-wali-otp', [UserController::class, 'verifyWaliOtp']);
    Route::post('/users/change-password', [UserController::class, 'changePassword']);

    // Siswa-accessible route (permission:view-tagihan-siswa middleware)
    Route::get('/tagihan/siswa', [TagihanController::class, 'siswaView']);
    Route::get('/pembayaran/siswa', [PembayaranController::class, 'siswaView']);
    
    // Shared Pembayaran routes (accessible by students or admin with permission)
    Route::get('/pembayaran/kwitansi/{kode_pembayaran}', [PdfGeneratorController::class, 'get']);

    // Dashboard routes
    Route::prefix('/dashboard')->group(function () {
        // Admin dashboard endpoints — require view-dashboard permission
        Route::group([], function () {
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
            ;
    });

    // Tahun Ajaran routes (needed by all roles for dropdown filters)
    Route::prefix('/tahun-ajaran')->group(function () {
        Route::get('/', [TahunAjaranController::class, 'index']);
        Route::post('/', [TahunAjaranController::class, 'store']);
        Route::get('/{id}', [TahunAjaranController::class, 'show']);
        Route::put('/{id}', [TahunAjaranController::class, 'update']);
        Route::delete('/{id}', [TahunAjaranController::class, 'destroy']);
        Route::patch('/{id}/activate', [TahunAjaranController::class, 'activate']);
        Route::patch('/{id}/deactivate', [TahunAjaranController::class, 'deactivate']);
    });

    // Admin panel routes — completely driven by specific permissions
    // User management routes
    Route::prefix('/users')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ;
        Route::post('/', [UserController::class, 'store'])
            ;
        Route::get('/{id}', [UserController::class, 'show'])
            
            ->where('id', '[0-9]+');
        Route::put('/{id}', [UserController::class, 'update'])
            
            ->where('id', '[0-9]+');
        Route::delete('/{id}', [UserController::class, 'destroy'])
            
            ->where('id', '[0-9]+');
        Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive'])
            
            ->where('id', '[0-9]+');
    });

        // Role management — permission-based
        Route::prefix('/roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::post('/', [RoleController::class, 'store']);
            Route::post('/attach', [RoleController::class, 'attach']);
            Route::post('/detach', [RoleController::class, 'detach']);
            Route::get('/permissions', [RoleController::class, 'permissions']);
            Route::get('/{id}', [RoleController::class, 'show']);
            Route::put('/{id}', [RoleController::class, 'update']);
            Route::delete('/{id}', [RoleController::class, 'destroy']);
        });

        // Siswa routes
        Route::prefix('/siswa')->group(function () {
            Route::get('/{jenjang}', [SiswaController::class, 'index']);
            Route::post('/{jenjang}', [SiswaController::class, 'create']);
            Route::get('/{jenjang}/{id}', [SiswaController::class, 'get']);
            Route::put('/{jenjang}/{id}', [SiswaController::class, 'update']);
            Route::delete('/{jenjang}/{id}', [SiswaController::class, 'delete']);
        });

        // Kelas routes
        Route::prefix('/kelas')->group(function () {
            Route::get('/', [KelasController::class, 'all']);
            Route::get('/{jenjang}', [KelasController::class, 'index']);
            Route::post('/{jenjang}', [KelasController::class, 'create']);
            Route::get('/{jenjang}/{id}', [KelasController::class, 'get']);
            Route::put('/{jenjang}/{id}', [KelasController::class, 'update']);
            Route::delete('/{jenjang}/{id}', [KelasController::class, 'delete']);
        });

        // Kategori routes
        Route::prefix('/kategori')->group(function () {
            Route::get('/', [KategoriController::class, 'index']);
            Route::post('/', [KategoriController::class, 'create']);
            Route::get('/{id}', [KategoriController::class, 'get']);
            Route::put('/{id}', [KategoriController::class, 'update']);
            Route::delete('/{id}', [KategoriController::class, 'delete']);
        });

        // Wali routes (related to siswa management)
        Route::prefix('/wali')->group(function () {
            Route::get('/', [WaliController::class, 'index']);
            Route::post('/', [WaliController::class, 'create']);
            Route::get('/{id}', [WaliController::class, 'get']);
            Route::put('/{id}', [WaliController::class, 'update']);
            Route::delete('/{id}', [WaliController::class, 'delete']);
        });

        // Ayah routes (parent search for siswa creation)
        Route::get('/ayah', [ParentSearchController::class, 'ayah']);
        Route::get('/ayah/{id}', [ParentSearchController::class, 'showAyah']);

        // Ibu routes (parent search for siswa creation)
        Route::get('/ibu', [ParentSearchController::class, 'ibu']);
        Route::get('/ibu/{id}', [ParentSearchController::class, 'showIbu']);

        // Tagihan routes (admin)
        Route::get('/tagihan/grouped', [TagihanController::class, 'grouped']);
        Route::get('/tagihan/export-pdf', [TagihanController::class, 'exportPdf']);
        Route::get('/tagihan', [TagihanController::class, 'index']);
        Route::prefix('/tagihan')->group(function () {
            Route::post('/', [TagihanController::class, 'create']);
            Route::get('/{kode_tagihan}', [TagihanController::class, 'get']);
            Route::patch('/{kode_tagihan}', [TagihanController::class, 'update']);
            Route::delete('/{kode_tagihan}', [TagihanController::class, 'delete']);
        });

        // Pembayaran routes
        Route::prefix('/pembayaran')->group(function () {
            Route::get('/grouped', [PembayaranController::class, 'grouped']);
            Route::get('/', [PembayaranController::class, 'index']);
            Route::post('/batch', [PembayaranController::class, 'batchLunas']);
            Route::post('/bayar/{kode_tagihan}', [PembayaranController::class, 'bayar']);
            Route::post('/lunas/{kode_tagihan}', [PembayaranController::class, 'lunas']);
            Route::delete('/{kode_pembayaran}', [PembayaranController::class, 'delete']);
        });

        // Pengeluaran routes
        Route::prefix('/pengeluaran')->group(function () {
            Route::get('/', [PengeluaranController::class, 'index']);
            Route::post('/', [PengeluaranController::class, 'create']);
            Route::get('/{id}', [PengeluaranController::class, 'get']);
            Route::put('/{id}', [PengeluaranController::class, 'update']);
            Route::delete('/{id}', [PengeluaranController::class, 'delete']);
        });

        // Jenis Tagihan routes
        Route::prefix('/jenis-tagihan')->group(function () {
            Route::get('/', [JenisTagihanController::class, 'index']);
            Route::post('/', [JenisTagihanController::class, 'create']);
            Route::get('/{id}', [JenisTagihanController::class, 'get']);
            Route::put('/{id}', [JenisTagihanController::class, 'update']);
            Route::delete('/{id}', [JenisTagihanController::class, 'delete']);
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
            Route::get('/kas', [KasController::class, 'kasHarian']);
            Route::get('/kas/detail', [KasController::class, 'kasDetail']);
            Route::get('/rekap', [KasController::class, 'rekapBulanan']);
            Route::get('/rekap/detail', [KasController::class, 'rekapDetail']);
            Route::prefix('/export')->group(function () {
                Route::get('/kas', [PdfGeneratorController::class, 'exportKas']);
                Route::get('/rekap', [PdfGeneratorController::class, 'exportRekapBulanan']);
            });
        });

        // Kenaikan Kelas routes
        Route::prefix('/kenaikan-kelas')->group(function () {
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
        Route::prefix('/akun-siswa')->group(function () {
            Route::get('/', [AkunSiswaController::class, 'index']);
            Route::get('/unregistered', [AkunSiswaController::class, 'unregistered']);
            Route::post('/bulk', [AkunSiswaController::class, 'bulkCreate']);
            Route::post('/{id}/reset-password', [AkunSiswaController::class, 'resetPassword']);
            Route::patch('/{id}/toggle-active', [AkunSiswaController::class, 'toggleActive']);
            Route::get('/credentials', [AkunSiswaController::class, 'credentials']);
            Route::get('/credentials-pdf', [AkunSiswaController::class, 'credentialsPdf']);
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
            Route::post('/', [PengeluaranRequestController::class, 'store']);
            Route::put('/{id}', [PengeluaranRequestController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [PengeluaranRequestController::class, 'destroy'])->where('id', '[0-9]+');
            Route::post('/{id}/submit', [PengeluaranRequestController::class, 'submit'])->where('id', '[0-9]+');
            Route::post('/{id}/approve', [PengeluaranRequestController::class, 'approve'])->where('id', '[0-9]+');
            Route::post('/{id}/reject', [PengeluaranRequestController::class, 'reject'])->where('id', '[0-9]+');
            Route::post('/{id}/disburse', [PengeluaranRequestController::class, 'disburse'])->where('id', '[0-9]+');
        });

        // Branch Approval Settings
        Route::prefix('/branch-approval-settings')->group(function () {
            Route::get('/', [BranchApprovalSettingController::class, 'show']);
            Route::put('/', [BranchApprovalSettingController::class, 'update']);
        });

        // Branch routes
        Route::prefix('/branches')->group(function () {
            Route::get('/', [BranchController::class, 'index']);
            Route::post('/', [BranchController::class, 'store']);
            Route::get('/{id}', [BranchController::class, 'show']);
            Route::put('/{id}', [BranchController::class, 'update']);
            Route::delete('/{id}', [BranchController::class, 'destroy']);
        });

        // Import & Export routes
        Route::prefix('/import-export')->group(function () {
            // Export routes - require 'export-data' permission
            Route::group([], function () {
                Route::post('/export/siswa', [ImportExportController::class, 'exportSiswa']);
                Route::post('/export/tagihan', [ImportExportController::class, 'exportTagihan']);
                Route::post('/export/pembayaran', [ImportExportController::class, 'exportPembayaran']);
                Route::post('/export/kas-harian', [ImportExportController::class, 'exportKasHarian']);
                Route::post('/export/rekap-bulanan', [ImportExportController::class, 'exportRekapBulanan']);
            });

            // Import routes - require 'import-data' permission
            Route::group([], function () {
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
            Route::get('/job/{jobId}/status', [ImportExportController::class, 'jobStatus']);
        });
});

// ──────────────────────────────────────────────────────────────
// Midtrans Webhook - public, no Sanctum (protected by signature)
// ──────────────────────────────────────────────────────────────
Route::post('/midtrans/notification', [MidtransNotificationController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    // Portal Siswa - Midtrans
    Route::group([], function () {
        Route::get('/midtrans/fee-channels', [MidtransTransactionController::class, 'feeChannels']);
        Route::post('/midtrans/transactions', [MidtransTransactionController::class, 'initiate']);
        Route::post('/midtrans/transactions/batch', [MidtransTransactionController::class, 'initiateBatch']);
        Route::get('/midtrans/transactions/{order_id}', [MidtransTransactionController::class, 'show']);
    });

    // Admin - Midtrans
    Route::group([], function () {
        Route::get('/midtrans/admin/transactions', [MidtransAdminController::class, 'index']);
        Route::get('/midtrans/admin/transactions/{order_id}', [MidtransAdminController::class, 'show']);
        Route::get('/midtrans/admin/transactions/{order_id}/logs', [MidtransAdminController::class, 'logs']);
    });
    Route::group([], function () {
        Route::post('/midtrans/admin/transactions/{order_id}/sync', [MidtransAdminController::class, 'sync']);
    });
});


