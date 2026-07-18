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
        Schema::table('siswas', function (Blueprint $table) {
            $table->char('batch_reference', 36)->nullable()->after('id');
            $table->index('batch_reference');
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->char('batch_reference', 36)->nullable()->after('branch_id');
            $table->index('batch_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropIndex(['batch_reference']);
            $table->dropColumn('batch_reference');
        });

        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropIndex(['batch_reference']);
            $table->dropColumn('batch_reference');
        });
    }
};
