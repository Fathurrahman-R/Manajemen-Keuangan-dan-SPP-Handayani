<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permission_resources', function (Blueprint $table) {
            $table->string('group', 100)->nullable()->after('description')
                ->comment('Navigation group (e.g. akademik, keuangan, laporan, pengaturan)');
        });
    }

    public function down(): void
    {
        Schema::table('permission_resources', function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
};
