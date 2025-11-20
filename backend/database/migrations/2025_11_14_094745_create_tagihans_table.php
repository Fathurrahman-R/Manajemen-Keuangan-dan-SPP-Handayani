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
        Schema::create('tagihans', function (Blueprint $table) {
            $table->char('kode_tagihan',30)->primary()->nullable(false);
            $table->foreignId('jenis_tagihan_id')->constrained('jenis_tagihans')->onUpdate('cascade');
            $table->string('nis',20)->unique()->nullable(false);
            $table->foreign('nis')->references('nis')->on('siswas')->onUpdate('cascade');
            $table->decimal('tmp',12,2)->nullable()->default(0);
            $table->enum('status',['Lunas','Belum Lunas','Belum Dibayar'])->nullable(false)->default('Belum Dibayar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihans');
    }
};
