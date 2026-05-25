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
        Schema::table('ayah', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->after('pekerjaan');
        });

        Schema::table('ibu', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->after('pekerjaan');
        });

        Schema::table('walis', function (Blueprint $table) {
            $table->string('email', 255)->nullable()->after('no_hp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ayah', function (Blueprint $table) {
            $table->dropColumn('email');
        });

        Schema::table('ibu', function (Blueprint $table) {
            $table->dropColumn('email');
        });

        Schema::table('walis', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
