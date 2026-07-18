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
        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();
            $table->char('job_reference', 36)->unique();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade');
            $table->string('export_type', 50);
            $table->json('filters')->nullable();
            $table->string('format', 10);
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->string('file_path', 500)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->onUpdate('cascade');
            $table->timestamps();

            $table->index(['branch_id', 'status'], 'idx_branch_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};
