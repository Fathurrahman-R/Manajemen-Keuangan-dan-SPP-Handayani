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
            $table->dropUnique('kelas_jenjang_branch_level_unique');

            // TK punya beberapa kelas paralel di level yang sama (mis. MATAHARI,
            // BINTANG, BULAN semua level 1) — KenaikanKelasService hanya
            // membutuhkan level unik di dalam satu jenjang untuk mencari kelas
            // berikutnya, jadi menyertakan `nama` di unique key mengizinkan
            // beberapa kelas berbagi level sambil tetap mencegah baris duplikat
            // persis (jenjang+branch+level+nama sama).
            $table->unique(['jenjang', 'branch_id', 'level', 'nama'], 'kelas_jenjang_branch_level_nama_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique('kelas_jenjang_branch_level_nama_unique');
            $table->unique(['jenjang', 'branch_id', 'level'], 'kelas_jenjang_branch_level_unique');
        });
    }
};
