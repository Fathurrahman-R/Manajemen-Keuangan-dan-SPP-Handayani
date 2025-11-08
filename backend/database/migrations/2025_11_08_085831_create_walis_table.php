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
        Schema::create('walis', function (Blueprint $table) {
            $table->id();
            $table->string('nama',100)->nullable(false);
            $table->enum('jenis_kelamin',['Laki-laki','Perempuan'])->nullable(false);
            $table->string('agama',50)->nullable(false);
            $table->string('pendidikan_terakhir',100)->nullable(false);
            $table->string('pekerjaan',100)->nullable();
            $table->text('alamat')->nullable(false);
            $table->string('no_hp',100)->nullable(false);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('walis');
    }
};
