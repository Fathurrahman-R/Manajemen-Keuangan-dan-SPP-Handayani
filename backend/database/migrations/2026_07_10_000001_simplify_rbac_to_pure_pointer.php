<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // page_permissions: drop route_pattern (method/path_pattern already removed from permission_endpoints)
        Schema::table('page_permissions', function (Blueprint $table) {
            $table->dropColumn('route_pattern');
        });
    }

    public function down(): void
    {
        Schema::table('page_permissions', function (Blueprint $table) {
            $table->string('route_pattern')->nullable()->after('id');
        });
    }
};
