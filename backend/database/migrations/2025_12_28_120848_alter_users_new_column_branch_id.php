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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('location');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('token')->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
        });
        Schema::table('siswas', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('keterangan')->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
        });
        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('nama')->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
        });
        Schema::table('kategoris', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('nama')->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
        });
        Schema::table('jenis_tagihans', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('jumlah')->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
        });
        Schema::table('app_settings', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('logo')->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
        });
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('pembayar')->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
        });
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('jumlah')->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
        });
        Schema::table('tagihans', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('status')->constrained('branches')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('kategoris', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('jenis_tagihans', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::dropIfExists('branches');

        Schema::enableForeignKeyConstraints();
    }
};
