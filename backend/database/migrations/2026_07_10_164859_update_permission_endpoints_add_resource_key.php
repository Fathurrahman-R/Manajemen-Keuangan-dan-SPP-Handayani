<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only run this migration if upgrading from old schema (with method/path_pattern)
        if (Schema::hasColumn('permission_endpoints', 'method')) {
            // Drop old unique constraint and method/path_pattern columns
            Schema::table('permission_endpoints', function (Blueprint $table) {
                $table->dropUnique(['method', 'path_pattern']);
                $table->dropColumn(['method', 'path_pattern']);

                // If resource_key already exists from partial upgrade, ensure it's NOT NULL and unique
                if (Schema::hasColumn('permission_endpoints', 'resource_key')) {
                    $table->string('resource_key', 255)->nullable(false)->change();
                }
            });
        }

        // If resource_key doesn't exist at all (shouldn't happen with fresh install), add it
        if (! Schema::hasColumn('permission_endpoints', 'resource_key')) {
            Schema::table('permission_endpoints', function (Blueprint $table) {
                $table->string('resource_key', 255)->unique();
            });
        }
    }

    public function down(): void
    {
        // Revert: make resource_key nullable again
        Schema::table('permission_endpoints', function (Blueprint $table) {
            $table->string('resource_key', 255)->nullable()->change();
        });
    }
};
