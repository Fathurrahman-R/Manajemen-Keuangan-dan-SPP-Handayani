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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('nama_sekolah',255)->nullable(false);
            $table->string('lokasi',100)->nullable(false);
            $table->text('alamat')->nullable(false);
            $table->string('email')->nullable(false);
            $table->string('telepon',20)->nullable(false);
            $table->string('kepala_sekolah',100)->nullable(false);
            $table->string('bendahara',100)->nullable(false);
            $table->string('kode_pos',15)->nullable(false);
            $table->string('logo',255)->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
