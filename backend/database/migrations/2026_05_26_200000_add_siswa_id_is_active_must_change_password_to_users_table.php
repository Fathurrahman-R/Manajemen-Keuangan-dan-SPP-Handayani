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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('siswa_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('siswas')
                ->nullOnDelete();

            $table->boolean('is_active')
                ->default(true)
                ->after('siswa_id');

            $table->boolean('must_change_password')
                ->default(false)
                ->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
            $table->dropColumn(['siswa_id', 'is_active', 'must_change_password']);
        });
    }
};
