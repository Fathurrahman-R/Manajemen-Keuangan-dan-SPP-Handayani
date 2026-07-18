<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old composite unique index first, then drop the column
        Schema::table('page_permissions', function (Blueprint $table) {
            // Drop the unique constraint that references route_pattern
            $table->dropUnique(['route_pattern', 'permission_name']);
            // Now safe to drop the column
            $table->dropColumn('route_pattern');
        });
    }

    public function down(): void
    {
        Schema::table('page_permissions', function (Blueprint $table) {
            $table->string('route_pattern')->nullable()->after('id');
            $table->unique(['route_pattern', 'permission_name']);
        });
    }
};
