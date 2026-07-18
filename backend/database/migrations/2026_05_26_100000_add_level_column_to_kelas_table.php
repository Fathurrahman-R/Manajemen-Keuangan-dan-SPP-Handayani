<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->unsignedInteger('level')->nullable()->default(null)->after('nama');

            // In MySQL/MariaDB, NULL values are excluded from unique index checks,
            // so multiple rows with level = NULL are allowed while non-NULL levels
            // must be unique within the same jenjang and branch_id.
            $table->unique(['jenjang', 'branch_id', 'level'], 'kelas_jenjang_branch_level_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique('kelas_jenjang_branch_level_unique');
            $table->dropColumn('level');
        });
    }
};
