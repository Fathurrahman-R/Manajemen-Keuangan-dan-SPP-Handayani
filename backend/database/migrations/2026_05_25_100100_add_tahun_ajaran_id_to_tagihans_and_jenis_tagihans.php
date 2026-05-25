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
        Schema::table('tagihans', function (Blueprint $table) {
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable()->after('branch_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajarans')->onUpdate('cascade')->onDelete('set null');
            $table->index('tahun_ajaran_id');
        });

        Schema::table('jenis_tagihans', function (Blueprint $table) {
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable()->after('branch_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajarans')->onUpdate('cascade')->onDelete('restrict');
            $table->index('tahun_ajaran_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropForeign(['tahun_ajaran_id']);
            $table->dropIndex(['tahun_ajaran_id']);
            $table->dropColumn('tahun_ajaran_id');
        });

        Schema::table('jenis_tagihans', function (Blueprint $table) {
            $table->dropForeign(['tahun_ajaran_id']);
            $table->dropIndex(['tahun_ajaran_id']);
            $table->dropColumn('tahun_ajaran_id');
        });
    }
};
