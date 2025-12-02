<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop foreign key & unique index if they exist
        Schema::table('tagihans', function (Blueprint $table) {
            try { $table->dropForeign(['nis']); } catch (Throwable $e) { /* ignore if not exists */ }
            try { $table->dropUnique('tagihans_nis_unique'); } catch (Throwable $e) { /* ignore if not exists */ }
        });

        // 2. Alter column (remove unique constraint, keep NOT NULL) & recreate FK with cascade
        Schema::table('tagihans', function (Blueprint $table) {
            // Ensure column stays string(20) NOT NULL
            $table->string('nis', 20)->nullable(false)->change();
            // Re-add foreign key referencing siswas.nis with cascade
            $table->foreign('nis')
                ->references('nis')
                ->on('siswas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert: drop FK with cascade, optionally restore unique + FK without cascade
        Schema::table('tagihans', function (Blueprint $table) {
            try { $table->dropForeign(['nis']); } catch (Throwable $e) { /* ignore */ }
        });

        Schema::table('tagihans', function (Blueprint $table) {
            // Keep same column definition
            $table->string('nis', 20)->nullable(false)->change();
            // (Optional) restore unique index & original FK without delete cascade (adjust if original differed)
            $table->unique('nis');
            $table->foreign('nis')
                ->references('nis')
                ->on('siswas')
                ->cascadeOnUpdate(); // previous behavior assumed no cascadeOnDelete
        });
    }
};
