<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->foreignId('pengeluaran_request_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('pengeluaran_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropForeign(['pengeluaran_request_id']);
            $table->dropColumn('pengeluaran_request_id');
        });
    }
};
