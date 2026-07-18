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
        Schema::create('batch_promosi_details', function (Blueprint $table) {
            $table->id();
            $table->char('batch_id', 36);
            $table->foreignId('siswa_id')->constrained('siswas')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('action', ['naik_kelas', 'tinggal_kelas', 'lulus', 'pindah_jenjang']);
            $table->foreignId('source_kelas_id')->constrained('kelas')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('target_kelas_id')->nullable()->constrained('kelas')->onUpdate('cascade')->onDelete('cascade');
            $table->string('previous_status', 20);
            $table->string('previous_jenjang', 5)->nullable();
            $table->timestamps();

            // Foreign key for batch_id referencing batch_promosis UUID
            $table->foreign('batch_id')->references('id')->on('batch_promosis')->onUpdate('cascade')->onDelete('cascade');

            // Indexes
            $table->index('batch_id');
            $table->index('siswa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_promosi_details');
    }
};
