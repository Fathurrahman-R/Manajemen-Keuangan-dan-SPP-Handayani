<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('route_pattern');  // /admin/users*, /admin/roles/*
            $table->string('permission_name');
            $table->string('guard_name')->default('web');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['route_pattern', 'permission_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_permissions');
    }
};
