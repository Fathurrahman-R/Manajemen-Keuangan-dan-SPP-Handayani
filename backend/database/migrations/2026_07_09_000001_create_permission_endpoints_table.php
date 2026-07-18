<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->nullable()->constrained('permissions')->nullOnDelete();
            $table->string('resource_key', 255)->unique();
            $table->string('method', 10);
            $table->string('path_pattern', 255);
            $table->string('group', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['method', 'path_pattern']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_endpoints');
    }
};
