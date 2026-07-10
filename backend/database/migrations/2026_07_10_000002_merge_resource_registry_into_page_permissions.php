<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add columns to page_permissions for merge ──
        Schema::table('page_permissions', function (Blueprint $table) {
            $table->string('group', 100)->nullable()->after('guard_name');
            $table->text('description')->nullable()->after('group');
            // Make permission_name nullable (since some resources might not have direct permission binding yet)
            $table->string('permission_name', 255)->nullable()->change();
        });

        // ── 2. Copy data from permission_resources to page_permissions ──
        $resources = DB::table('permission_resources')->get();
        foreach ($resources as $r) {
            // Resolve permission_name from permission_id
            $permName = null;
            if ($r->permission_id) {
                $perm = DB::table('permissions')->find($r->permission_id);
                $permName = $perm?->name;
            }

            // Check if resource_key already exists in page_permissions
            $existing = DB::table('page_permissions')
                ->where('resource_key', $r->resource_key)
                ->first();

            if ($existing) {
                // Update existing row with resource registry data
                DB::table('page_permissions')
                    ->where('id', $existing->id)
                    ->update([
                        'permission_name' => $permName ?? $existing->permission_name,
                        'group' => $r->group ?? $existing->group,
                        'description' => $r->description ?? $existing->description,
                        'is_active' => $r->is_active,
                    ]);
            } else {
                // Insert new row
                DB::table('page_permissions')->insert([
                    'resource_key' => $r->resource_key,
                    'permission_name' => $permName,
                    'guard_name' => 'web',
                    'group' => $r->group,
                    'description' => $r->description,
                    'is_active' => $r->is_active,
                    'created_at' => $r->created_at ?? now(),
                    'updated_at' => $r->updated_at ?? now(),
                ]);
            }
        }

        // ── 3. Make resource_key unique and NOT NULL ──
        Schema::table('page_permissions', function (Blueprint $table) {
            // Drop any existing rows with null resource_key (they're orphans now)
            DB::table('page_permissions')->whereNull('resource_key')->delete();
            // Make resource_key unique and not null
            $table->string('resource_key', 255)->unique()->nullable(false)->change();
        });

        // ── 4. Drop permission_resources table ──
        Schema::dropIfExists('permission_resources');
    }

    public function down(): void
    {
        // Recreate permission_resources table
        Schema::create('permission_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->nullable()->constrained('permissions')->nullOnDelete();
            $table->string('resource_key', 255)->unique();
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->string('group', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Copy data back
        $pages = DB::table('page_permissions')->get();
        foreach ($pages as $p) {
            $permId = null;
            if ($p->permission_name) {
                $perm = DB::table('permissions')->where('name', $p->permission_name)->first();
                $permId = $perm?->id;
            }
            DB::table('permission_resources')->insert([
                'permission_id' => $permId,
                'resource_key' => $p->resource_key,
                'label' => $p->resource_key,
                'description' => $p->description,
                'group' => $p->group,
                'is_active' => $p->is_active,
                'created_at' => $p->created_at ?? now(),
                'updated_at' => $p->updated_at ?? now(),
            ]);
        }

        // Revert page_permissions changes
        Schema::table('page_permissions', function (Blueprint $table) {
            $table->dropUnique(['resource_key']);
            $table->string('resource_key', 255)->nullable()->change();
            $table->string('permission_name', 255)->nullable(false)->change();
            $table->dropColumn(['group', 'description']);
        });
    }
};
