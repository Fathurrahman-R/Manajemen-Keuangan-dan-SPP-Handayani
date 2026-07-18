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
        Schema::create('tahun_ajarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 9); // Format: YYYY/YYYY
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['Aktif', 'Non-Aktif'])->default('Non-Aktif');
            $table->foreignId('branch_id')->constrained('branches')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint: one nama per branch
            $table->unique(['nama', 'branch_id']);
            // Index for fast active period lookup
            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahun_ajarans');
    }
};
