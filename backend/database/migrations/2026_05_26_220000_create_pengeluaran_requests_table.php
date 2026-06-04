<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran_requests', function (Blueprint $table) {
            $table->id();
            $table->string('uraian');
            $table->decimal('jumlah', 13, 2);
            $table->date('tanggal_kebutuhan');
            $table->string('kategori_pengeluaran')->nullable();
            $table->string('lampiran')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'disbursed'])->default('draft');
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('requester_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_requests');
    }
};
