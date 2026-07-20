<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE notification_logs MODIFY notification_type ENUM('tagihan_baru', 'reminder', 'kwitansi', 'overdue', 'workflow')");

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->foreignId('pengeluaran_request_id')->nullable()->after('tagihan_kode')
                ->constrained('pengeluaran_requests')->nullOnDelete();
            $table->string('workflow_event')->nullable()->after('pengeluaran_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pengeluaran_request_id');
            $table->dropColumn('workflow_event');
        });

        DB::statement("ALTER TABLE notification_logs MODIFY notification_type ENUM('tagihan_baru', 'reminder', 'kwitansi', 'overdue')");
    }
};
