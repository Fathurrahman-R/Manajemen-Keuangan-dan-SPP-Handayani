<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ayah', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        Schema::table('ibu', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        Schema::table('walis', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('ayah', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });

        Schema::table('ibu', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });

        Schema::table('walis', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
