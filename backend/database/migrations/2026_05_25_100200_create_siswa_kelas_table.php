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
        Schema::create('siswa_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('kelas_id')->constrained('kelas')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();

            // One class per student per period
            $table->unique(['siswa_id', 'tahun_ajaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa_kelas');
    }
};
