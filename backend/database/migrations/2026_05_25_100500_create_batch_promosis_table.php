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
        Schema::create('batch_promosis', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->enum('batch_type', [
                'bulk_promotion',
                'individual_promotion',
                'kelulusan',
                'tinggal_kelas',
                'pindah_jenjang',
            ]);
            $table->foreignId('source_tahun_ajaran_id')->constrained('tahun_ajarans')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('target_tahun_ajaran_id')->constrained('tahun_ajarans')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('processed_by')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamp('processed_at');
            $table->enum('status', ['completed', 'undone'])->default('completed');
            $table->foreignId('branch_id')->constrained('branches')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();

            // Indexes for listing and sorting
            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_promosis');
    }
};
