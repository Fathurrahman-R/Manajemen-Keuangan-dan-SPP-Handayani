<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE email_opt_outs MODIFY notification_type ENUM('tagihan_baru', 'reminder', 'kwitansi', 'overdue', 'workflow', 'all')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE email_opt_outs MODIFY notification_type ENUM('tagihan_baru', 'reminder', 'kwitansi', 'overdue', 'all')");
    }
};
