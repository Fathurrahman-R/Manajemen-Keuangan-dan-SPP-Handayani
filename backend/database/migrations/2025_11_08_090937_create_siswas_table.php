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
            $table->id();
            $table->string('nis',20)->unique()->nullable(false);
            $table->string('nisn',20)->unique()->nullable()->default(null);
            $table->string('nama',100)->nullable(false);
            $table->enum('jenis_kelamin',['Laki-laki','Perempuan'])->nullable(false);
            $table->string('tempat_lahir',100)->nullable(false);
            $table->date('tanggal_lahir')->nullable(false);
            $table->string('agama',50)->nullable(false);
            $table->text('alamat')->nullable(false);
            $table->foreignId('ayah_id')->nullable()->constrained('walis')->onUpdate('cascade');
            $table->foreignId('ibu_id')->nullable()->constrained('walis')->onUpdate('cascade');
            $table->foreignId('wali_id')->nullable()->constrained('walis')->onUpdate('cascade');
            $table->enum('jenjang',['TK','MI','KB'])->nullable(false);
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->onUpdate('cascade');
            $table->foreignId('kategori_id')->nullable()->constrained('kategoris')->onUpdate('cascade');
            $table->string('asal_sekolah',150)->nullable()->default(null);
            $table->string('kelas_diterima',10)->nullable()->default(null);
            $table->year('tahun_diterima')->nullable()->default(null);
            $table->enum('status',['Aktif','Lulus','Pindah','Keluar'])->nullable(false)->default('Aktif');
            $table->text('keterangan')->nullable()->default(null);
            $table->timestamps();
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
