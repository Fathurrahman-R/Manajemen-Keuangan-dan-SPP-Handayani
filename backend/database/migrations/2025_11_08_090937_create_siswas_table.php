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
        Schema::create('siswas', function (Blueprint $table) {
            $table->string('nis',20)->primary()->nullable(false);
            $table->string('nisn',20)->unique()->nullable(false);
            $table->string('nama',100)->nullable(false);
            $table->enum('jenis_kelamin',['Laki-laki','Perempuan'])->nullable(false);
            $table->string('tempat_lahir',100)->nullable(false);
            $table->date('tanggal_lahir')->nullable(false);
            $table->string('agama',50)->nullable(false);
            $table->text('alamat')->nullable(false);
            $table->unsignedBigInteger('ayah')->nullable()->default(null);
            $table->unsignedBigInteger('ibu')->nullable()->default(null);
            $table->unsignedBigInteger('wali')->nullable(false);
            $table->enum('jenjang',['TK','MI','KB'])->nullable(false);
            $table->unsignedBigInteger('kelas')->nullable(false);
            $table->unsignedBigInteger('kategori')->nullable(false);
            $table->string('asal_sekolah',150)->nullable()->default(null);
            $table->string('kelas_diterima',10)->nullable()->default(null);
            $table->year('tahun_diterima')->nullable()->default(null);
            $table->enum('status',['Aktif','Lulus','Pindah','Keluar'])->nullable()->default('Aktif');
            $table->text('keterangan')->nullable()->default(null);
            $table->timestamps();

            $table->foreign('ayah')->on('walis')->references('id')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('ibu')->on('walis')->references('id')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('wali')->on('walis')->references('id')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('kelas')->on('kelas')->references('id')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('kategori')->on('kategoris')->references('id')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
