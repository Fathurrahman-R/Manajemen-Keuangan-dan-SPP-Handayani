<?php

use App\Http\Controllers\AkunSiswaController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchApprovalSettingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailOptOutController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\JenisTagihanController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KenaikanKelasController;
use App\Http\Controllers\MidtransAdminController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\MidtransTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationLogController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\ParentSearchController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PdfGeneratorController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PengeluaranRequestController;
use App\Http\Controllers\RbacController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaliController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Password reset routes (public, no auth required)
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::get('/reset-password/{token}', [PasswordResetController::class, 'validateToken']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Public unsubscribe routes (no auth required)
Route::get('/unsubscribe/{token}', [EmailOptOutController::class, 'show']);
Route::post('/unsubscribe/{token}', [EmailOptOutController::class, 'update']);

Route::middleware(['auth:sanctum', 'active.branch'])->group(function () {
    Route::delete('/logout', [AuthController::class, 'logout']);
    Route::get('/users/current', [UserController::class, 'get']);
    Route::patch('/users/current', [UserController::class, 'updateCurrent']);
    Route::patch('/users/current/email', [UserController::class, 'updateEmail']);
    Route::get('/users/current/notification-preferences', [UserController::class, 'getNotificationPreferences']);
    Route::put('/users/current/notification-preferences', [UserController::class, 'updateNotificationPreferences']);
    Route::get('/users/current/siswa-detail', [UserController::class, 'siswaDetail']);
    Route::post('/users/send-verification-otp', [UserController::class, 'sendVerificationOtp']);
    Route::post('/users/verify-email-otp', [UserController::class, 'verifyEmailOtp']);
    Route::post('/users/send-wali-otp', [UserController::class, 'sendWaliOtp']);
    Route::post('/users/verify-wali-otp', [UserController::class, 'verifyWaliOtp']);
    Route::post('/users/change-password', [UserController::class, 'changePassword']);

    // Siswa-accessible route
    Route::get('/tagihan/siswa', [TagihanController::class, 'siswaView'])->middleware('endpoint.permission:tagihan.siswa');
    Route::get('/pembayaran/siswa', [PembayaranController::class, 'siswaView'])->middleware('endpoint.permission:pembayaran.siswa');

    // Shared Pembayaran routes
    Route::get('/pembayaran/kwitansi/{kode_pembayaran}', [PdfGeneratorController::class, 'get'])->middleware('endpoint.permission:pembayaran.kwitansi');

    // Dashboard routes
    Route::prefix('/dashboard')->group(function () {
        // Admin dashboard endpoints — require view-dashboard permission
        Route::group(['middleware' => 'endpoint.permission:dashboard'], function () {
            Route::get('/overview', [DashboardController::class, 'overview']);
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

        // Siswa/Wali dashboard endpoint
        Route::get('/siswa', [DashboardController::class, 'siswaDashboard'])
            ->middleware('endpoint.permission:portal');
    });

    // Tahun Ajaran routes — index is public for any authenticated user (period filter dropdown)
    Route::get('/tahun-ajaran', [TahunAjaranController::class, 'index']);
    Route::prefix('/tahun-ajaran')->middleware('endpoint.permission:tahun-ajaran.view')->group(function () {
        Route::post('/', [TahunAjaranController::class, 'store'])->middleware('endpoint.permission:tahun-ajaran.create');
        Route::get('/{id}', [TahunAjaranController::class, 'show']);
        Route::put('/{id}', [TahunAjaranController::class, 'update'])->middleware('endpoint.permission:tahun-ajaran.update');
        Route::delete('/{id}', [TahunAjaranController::class, 'destroy'])->middleware('endpoint.permission:tahun-ajaran.delete');
        Route::patch('/{id}/activate', [TahunAjaranController::class, 'activate'])->middleware('endpoint.permission:tahun-ajaran.toggle');
        Route::patch('/{id}/deactivate', [TahunAjaranController::class, 'deactivate'])->middleware('endpoint.permission:tahun-ajaran.toggle');
    });

    // User management routes
    Route::prefix('/users')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->middleware('endpoint.permission:users.view');
        Route::post('/', [UserController::class, 'store'])
            ->middleware('endpoint.permission:users.create');
        Route::get('/{id}', [UserController::class, 'show'])
            ->middleware('endpoint.permission:users.read')
            ->where('id', '[0-9]+');
        Route::put('/{id}', [UserController::class, 'update'])
            ->middleware('endpoint.permission:users.update')
            ->where('id', '[0-9]+');
        Route::delete('/{id}', [UserController::class, 'destroy'])
            ->middleware('endpoint.permission:users.delete')
            ->where('id', '[0-9]+');
        Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive'])
            ->middleware('endpoint.permission:users.toggle')
            ->where('id', '[0-9]+');
    });

    // RBAC Management — route digabung dari rbac.php (merged)
    Route::prefix('/rbac')->middleware('endpoint.permission:rbac')->group(function () {
        // Permissions CRUD
        Route::get('/permissions', [RbacController::class, 'indexPermissions'])->middleware('endpoint.permission:permission.view');
        Route::post('/permissions', [RbacController::class, 'storePermission'])->middleware('endpoint.permission:permission.create');
        Route::put('/permissions/{permission}', [RbacController::class, 'updatePermission'])->middleware('endpoint.permission:permission.update');
        Route::delete('/permissions/{permission}', [RbacController::class, 'destroyPermission'])->middleware('endpoint.permission:permission.delete');

        // Endpoints CRUD
        Route::get('/endpoints', [RbacController::class, 'indexEndpoints'])->middleware('endpoint.permission:endpoint-mapping.view');
        Route::post('/endpoints', [RbacController::class, 'storeEndpoint'])->middleware('endpoint.permission:endpoint-mapping.create');
        Route::put('/endpoints/{endpoint}', [RbacController::class, 'updateEndpoint'])->middleware('endpoint.permission:endpoint-mapping.update');
        Route::delete('/endpoints/{endpoint}', [RbacController::class, 'destroyEndpoint'])->middleware('endpoint.permission:endpoint-mapping.delete');

        // Page Permissions CRUD
        Route::get('/page-permissions', [RbacController::class, 'indexPagePermissions'])->middleware('endpoint.permission:resource-registry.view');
        Route::post('/page-permissions', [RbacController::class, 'storePagePermission'])->middleware('endpoint.permission:resource-registry.create');
        Route::put('/page-permissions/{pagePermission}', [RbacController::class, 'updatePagePermission'])->middleware('endpoint.permission:resource-registry.update');
        Route::delete('/page-permissions/{pagePermission}', [RbacController::class, 'destroyPagePermission'])->middleware('endpoint.permission:resource-registry.delete');

        // Role management (ported from RoleController)
        Route::get('/roles', [RbacController::class, 'indexRoles'])->middleware('endpoint.permission:role.view');
        Route::post('/roles', [RbacController::class, 'storeRole'])->middleware('endpoint.permission:role.create');
        Route::get('/roles/permissions-tree', [RbacController::class, 'permissionsTree'])->middleware('endpoint.permission:role.create');
        Route::get('/roles/{id}', [RbacController::class, 'showRole'])->middleware('endpoint.permission:role.view');
        Route::put('/roles/{id}', [RbacController::class, 'updateRole'])->middleware('endpoint.permission:role.update');
        Route::delete('/roles/{id}', [RbacController::class, 'destroyRole'])->middleware('endpoint.permission:role.delete');
        Route::get('/roles/{role}/permissions', [RbacController::class, 'getRolePermissions']);
        Route::put('/roles/{role}/permissions', [RbacController::class, 'syncRolePermissions']);
    });

    // User resources & groups — outside rbac middleware because every authenticated user
    // (including siswa) needs to fetch their own resource list for PermissionHelper::hasResource()
    Route::get('/rbac/user-resources', [RbacController::class, 'userResources']);
    Route::get('/rbac/user-groups', [RbacController::class, 'userGroups']);

    // Siswa routes
    Route::prefix('/siswa')->group(function () {
        Route::get('/{jenjang}', [SiswaController::class, 'index'])->middleware('endpoint.permission:siswa.view');
        Route::post('/{jenjang}', [SiswaController::class, 'create'])->middleware('endpoint.permission:siswa.create');
        Route::get('/{jenjang}/{id}', [SiswaController::class, 'get'])->middleware('endpoint.permission:siswa.read');
        Route::put('/{jenjang}/{id}', [SiswaController::class, 'update'])->middleware('endpoint.permission:siswa.update');
        Route::delete('/{jenjang}/{id}', [SiswaController::class, 'delete'])->middleware('endpoint.permission:siswa.delete');
    });

    // Kelas routes
    Route::prefix('/kelas')->group(function () {
        Route::get('/', [KelasController::class, 'all'])->middleware('endpoint.permission:kelas.view');
        Route::get('/{jenjang}', [KelasController::class, 'index'])->middleware('endpoint.permission:kelas.view');
        Route::post('/{jenjang}', [KelasController::class, 'create'])->middleware('endpoint.permission:kelas.create');
        Route::get('/{jenjang}/{id}', [KelasController::class, 'get'])->middleware('endpoint.permission:kelas.read');
        Route::put('/{jenjang}/{id}', [KelasController::class, 'update'])->middleware('endpoint.permission:kelas.update');
        Route::delete('/{jenjang}/{id}', [KelasController::class, 'delete'])->middleware('endpoint.permission:kelas.delete');
    });

    // Kategori routes
    Route::prefix('/kategori')->group(function () {
        Route::get('/', [KategoriController::class, 'index'])->middleware('endpoint.permission:kategori.view');
        Route::post('/', [KategoriController::class, 'create'])->middleware('endpoint.permission:kategori.create');
        Route::get('/{id}', [KategoriController::class, 'get'])->middleware('endpoint.permission:kategori.read');
        Route::put('/{id}', [KategoriController::class, 'update'])->middleware('endpoint.permission:kategori.update');
        Route::delete('/{id}', [KategoriController::class, 'delete'])->middleware('endpoint.permission:kategori.delete');
    });

    // Wali routes
    Route::prefix('/wali')->group(function () {
        Route::get('/', [WaliController::class, 'index'])->middleware('endpoint.permission:wali.view');
        Route::post('/', [WaliController::class, 'create'])->middleware('endpoint.permission:wali.create');
        Route::get('/{id}', [WaliController::class, 'get'])->middleware('endpoint.permission:wali.view');
        Route::put('/{id}', [WaliController::class, 'update'])->middleware('endpoint.permission:wali.update');
        Route::delete('/{id}', [WaliController::class, 'delete'])->middleware('endpoint.permission:wali.delete');
    });

    // Ayah routes (parent search)
    Route::get('/ayah', [ParentSearchController::class, 'ayah'])->middleware('endpoint.permission:ayah.view');
    Route::get('/ayah/{id}', [ParentSearchController::class, 'showAyah'])->middleware('endpoint.permission:ayah.view');

    // Ibu routes (parent search)
    Route::get('/ibu', [ParentSearchController::class, 'ibu'])->middleware('endpoint.permission:ibu.view');
    Route::get('/ibu/{id}', [ParentSearchController::class, 'showIbu'])->middleware('endpoint.permission:ibu.view');

    // Tagihan routes (admin)
    Route::get('/tagihan/grouped', [TagihanController::class, 'grouped'])->middleware('endpoint.permission:tagihan.view');
    Route::get('/tagihan/export-pdf', [TagihanController::class, 'exportPdf'])->middleware('endpoint.permission:tagihan.export');
    Route::get('/tagihan', [TagihanController::class, 'index'])->middleware('endpoint.permission:tagihan.view');
    Route::prefix('/tagihan')->group(function () {
        Route::post('/', [TagihanController::class, 'create'])->middleware('endpoint.permission:tagihan.create');
        Route::get('/{kode_tagihan}', [TagihanController::class, 'get'])->middleware('endpoint.permission:tagihan.view');
        Route::patch('/{kode_tagihan}', [TagihanController::class, 'update'])->middleware('endpoint.permission:tagihan.update');
        Route::delete('/{kode_tagihan}', [TagihanController::class, 'delete'])->middleware('endpoint.permission:tagihan.delete');
    });

    // Pembayaran routes (admin)
    Route::prefix('/pembayaran')->group(function () {
        Route::get('/grouped', [PembayaranController::class, 'grouped'])->middleware('endpoint.permission:pembayaran.view');
        Route::get('/', [PembayaranController::class, 'index'])->middleware('endpoint.permission:pembayaran.view');
        Route::post('/batch', [PembayaranController::class, 'batchLunas'])->middleware('endpoint.permission:pembayaran.create');
        Route::post('/bayar/{kode_tagihan}', [PembayaranController::class, 'bayar'])->middleware('endpoint.permission:pembayaran.create');
        Route::delete('/{kode_pembayaran}', [PembayaranController::class, 'delete'])->middleware('endpoint.permission:pembayaran.delete');
    });

    // Jenis Tagihan routes
    Route::prefix('/jenis-tagihan')->group(function () {
        Route::get('/', [JenisTagihanController::class, 'index'])->middleware('endpoint.permission:jenis-tagihan.view');
        Route::post('/', [JenisTagihanController::class, 'create'])->middleware('endpoint.permission:jenis-tagihan.create');
        Route::get('/{id}', [JenisTagihanController::class, 'get'])->middleware('endpoint.permission:jenis-tagihan.view');
        Route::put('/{id}', [JenisTagihanController::class, 'update'])->middleware('endpoint.permission:jenis-tagihan.update');
        Route::delete('/{id}', [JenisTagihanController::class, 'delete'])->middleware('endpoint.permission:jenis-tagihan.delete');
    });

    // Setting routes
    Route::get('/setting', [AppSettingController::class, 'get'])->middleware('endpoint.permission:pengaturan.view');
    Route::post('/setting/{id}', [AppSettingController::class, 'update'])->middleware('endpoint.permission:pengaturan.update');

    // Notification settings routes
    Route::get('/notification-settings', [NotificationSettingController::class, 'show'])->middleware('endpoint.permission:notification-setting.view');
    Route::put('/notification-settings', [NotificationSettingController::class, 'update'])->middleware('endpoint.permission:notification-setting.update');

    // Notification log routes
    Route::get('/notification-logs', [NotificationLogController::class, 'index'])->middleware('endpoint.permission:notification-logs.view');
    Route::post('/notification-logs/retry', [NotificationLogController::class, 'retry'])->middleware('endpoint.permission:notification-logs.retry');

    // Laporan routes
    Route::prefix('/laporan')->group(function () {
        Route::get('/kas', [KasController::class, 'kasHarian'])->middleware('endpoint.permission:laporan.kas');
        Route::get('/kas/detail', [KasController::class, 'kasDetail'])->middleware('endpoint.permission:laporan.kas-detail');
        Route::get('/rekap', [KasController::class, 'rekapBulanan'])->middleware('endpoint.permission:laporan.rekap');
        Route::get('/rekap/detail', [KasController::class, 'rekapDetail'])->middleware('endpoint.permission:laporan.rekap-detail');
        Route::prefix('/export')->group(function () {
            Route::get('/kas', [PdfGeneratorController::class, 'exportKas']);
            Route::get('/rekap', [PdfGeneratorController::class, 'exportRekapBulanan']);
        })->middleware('endpoint.permission:laporan.export');
    });

    // Kenaikan Kelas routes
    Route::prefix('/kenaikan-kelas')->group(function () {
        Route::post('/bulk-promotion', [KenaikanKelasController::class, 'bulkPromotion'])->middleware('endpoint.permission:kenaikan-kelas.process');
        Route::post('/graduation', [KenaikanKelasController::class, 'graduation'])->middleware('endpoint.permission:kenaikan-kelas.process');
        Route::post('/retention', [KenaikanKelasController::class, 'retention'])->middleware('endpoint.permission:kenaikan-kelas.process');
        Route::post('/cross-level-transfer', [KenaikanKelasController::class, 'crossLevelTransfer'])->middleware('endpoint.permission:kenaikan-kelas.process');
        Route::post('/{batchId}/undo', [KenaikanKelasController::class, 'undo'])->middleware('endpoint.permission:kenaikan-kelas.undo');
        Route::get('/batches', [KenaikanKelasController::class, 'listBatches'])->middleware('endpoint.permission:kenaikan-kelas.view');
        Route::get('/batches/{id}', [KenaikanKelasController::class, 'showBatch'])->middleware('endpoint.permission:kenaikan-kelas.detail');
        Route::get('/eligible-students', [KenaikanKelasController::class, 'eligibleStudents'])->middleware('endpoint.permission:kenaikan-kelas.view');
        Route::get('/class-hierarchy', [KenaikanKelasController::class, 'classHierarchy'])->middleware('endpoint.permission:kenaikan-kelas.view');
    });

    // Akun Siswa management routes
    Route::prefix('/akun-siswa')->group(function () {
        Route::get('/', [AkunSiswaController::class, 'index'])->middleware('endpoint.permission:akun-siswa.view');
        Route::get('/unregistered', [AkunSiswaController::class, 'unregistered'])->middleware('endpoint.permission:akun-siswa.view');
        Route::post('/bulk', [AkunSiswaController::class, 'bulkCreate'])->middleware('endpoint.permission:akun-siswa.create');
        Route::post('/{id}/reset-password', [AkunSiswaController::class, 'resetPassword'])->middleware('endpoint.permission:akun-siswa.reset');
        Route::patch('/{id}/toggle-active', [AkunSiswaController::class, 'toggleActive'])->middleware('endpoint.permission:akun-siswa.toggle');
        Route::get('/credentials', [AkunSiswaController::class, 'credentials'])->middleware('endpoint.permission:akun-siswa.view-credentials');
        Route::get('/credentials-pdf', [AkunSiswaController::class, 'credentialsPdf'])->middleware('endpoint.permission:akun-siswa.print-credentials');
    });

    // Notification routes
    //    Route::prefix('/notifications')->group(function () {
    //        Route::get('/', [NotificationController::class, 'index'])->middleware('endpoint.permission:notifications.view');
    //        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->middleware('endpoint.permission:notifications.view');
    //        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->middleware('endpoint.permission:notifications.update');
    //        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->middleware('endpoint.permission:notifications.update');
    //    });

    // Pengeluaran Request (Approval Workflow) routes
    Route::prefix('/pengeluaran-request')->group(function () {
        Route::get('/', [PengeluaranRequestController::class, 'index'])->middleware('endpoint.permission:pengeluaran.view');
        Route::get('/{id}', [PengeluaranRequestController::class, 'show'])->middleware('endpoint.permission:pengeluaran.view')->where('id', '[0-9]+');
        Route::post('/', [PengeluaranRequestController::class, 'store'])->middleware('endpoint.permission:pengeluaran.create');
        Route::put('/{id}', [PengeluaranRequestController::class, 'update'])->middleware('endpoint.permission:pengeluaran.update')->where('id', '[0-9]+');
        Route::delete('/{id}', [PengeluaranRequestController::class, 'destroy'])->middleware('endpoint.permission:pengeluaran.delete')->where('id', '[0-9]+');
        Route::post('/{id}/submit', [PengeluaranRequestController::class, 'submit'])->middleware('endpoint.permission:pengeluaran.create')->where('id', '[0-9]+');
        Route::post('/{id}/approve', [PengeluaranRequestController::class, 'approve'])->middleware('endpoint.permission:pengeluaran.approve')->where('id', '[0-9]+');
        Route::post('/{id}/reject', [PengeluaranRequestController::class, 'reject'])->middleware('endpoint.permission:pengeluaran.approve')->where('id', '[0-9]+');
        Route::post('/{id}/disburse', [PengeluaranRequestController::class, 'disburse'])->middleware('endpoint.permission:pengeluaran.disburse')->where('id', '[0-9]+');
    });

    // Branch Approval Settings
    Route::prefix('/branch-approval-settings')->group(function () {
        Route::get('/', [BranchApprovalSettingController::class, 'show'])->middleware('endpoint.permission:auto-approve.view');
        Route::put('/', [BranchApprovalSettingController::class, 'update'])->middleware('endpoint.permission:auto-approve.update');
    });

    // Branch routes
    Route::prefix('/branches')->group(function () {
        Route::get('/switcher-options', [BranchController::class, 'index'])->middleware('endpoint.permission:api.branch.switch');
        Route::get('/', [BranchController::class, 'index'])->middleware('endpoint.permission:branch.view');
        Route::post('/', [BranchController::class, 'store'])->middleware('endpoint.permission:branch.create');
        Route::get('/{id}', [BranchController::class, 'show'])->middleware('endpoint.permission:branch.read');
        Route::put('/{id}', [BranchController::class, 'update'])->middleware('endpoint.permission:branch.update');
        Route::delete('/{id}', [BranchController::class, 'destroy'])->middleware('endpoint.permission:branch.delete');
    });

    // Import & Export routes
    Route::prefix('/import-export')->group(function () {
        // Export routes
        Route::group(['middleware' => 'endpoint.permission:export-data'], function () {
            Route::post('/export/siswa', [ImportExportController::class, 'exportSiswa']);
            Route::post('/export/tagihan', [ImportExportController::class, 'exportTagihan']);
            Route::post('/export/pembayaran', [ImportExportController::class, 'exportPembayaran']);
            Route::post('/export/kas-harian', [ImportExportController::class, 'exportKasHarian']);
            Route::post('/export/rekap-bulanan', [ImportExportController::class, 'exportRekapBulanan']);
        });

        // Import routes
        Route::group(['middleware' => 'endpoint.permission:import-data'], function () {
            Route::post('/import/siswa/upload', [ImportExportController::class, 'uploadSiswa']);
            Route::post('/import/siswa/confirm', [ImportExportController::class, 'confirmSiswa']);
            Route::post('/import/tagihan/upload', [ImportExportController::class, 'uploadTagihan']);
            Route::post('/import/tagihan/confirm', [ImportExportController::class, 'confirmTagihan']);
            Route::get('/import/template/siswa', [ImportExportController::class, 'templateSiswa']);
            Route::get('/import/template/tagihan', [ImportExportController::class, 'templateTagihan']);
            Route::get('/import/history', [ImportExportController::class, 'importHistory']);
            Route::post('/import/{batchId}/rollback', [ImportExportController::class, 'rollbackImport']);
        });

    });
});

// ──────────────────────────────────────────────────────────────
// Midtrans Webhook - public, no Sanctum (protected by signature)
// ──────────────────────────────────────────────────────────────
Route::post('/midtrans/notification', [MidtransNotificationController::class, 'handle']);

Route::middleware(['auth:sanctum', 'active.branch'])->group(function () {
    // Portal Siswa - Midtrans
    Route::group(['middleware' => 'endpoint.permission:midtrans.pay'], function () {
        Route::get('/midtrans/fee-channels', [MidtransTransactionController::class, 'feeChannels']);
        Route::post('/midtrans/transactions', [MidtransTransactionController::class, 'initiate']);
        Route::post('/midtrans/transactions/batch', [MidtransTransactionController::class, 'initiateBatch']);
        Route::get('/midtrans/transactions/{order_id}', [MidtransTransactionController::class, 'show']);
    });

    // Admin - Midtrans
    Route::group(['middleware' => 'endpoint.permission:midtrans.admin'], function () {
        Route::get('/midtrans/admin/transactions', [MidtransAdminController::class, 'index']);
        Route::get('/midtrans/admin/transactions/{order_id}', [MidtransAdminController::class, 'show']);
        Route::get('/midtrans/admin/transactions/{order_id}/logs', [MidtransAdminController::class, 'logs']);
    });
    Route::group(['middleware' => 'endpoint.permission:midtrans.sync'], function () {
        Route::post('/midtrans/admin/transactions/{order_id}/sync', [MidtransAdminController::class, 'sync']);
    });
});
