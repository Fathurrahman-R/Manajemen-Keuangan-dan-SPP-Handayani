<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_permissions', function (Blueprint $table) {
            $table->string('resource_key')->nullable()->after('permission_name');
        });
    }

    public function down(): void
    {
        Schema::table('page_permissions', function (Blueprint $table) {
            $table->dropColumn('resource_key');
        });
    }
};
