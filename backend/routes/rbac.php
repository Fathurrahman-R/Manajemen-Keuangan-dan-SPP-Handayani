<?php

use App\Http\Controllers\RbacController;
use Illuminate\Support\Facades\Route;

// RBAC Management API
Route::middleware('auth:sanctum')->prefix('rbac')->group(function () {

    // Permissions CRUD
    Route::get('/permissions', [RbacController::class, 'indexPermissions']);
    Route::post('/permissions', [RbacController::class, 'storePermission']);
    Route::put('/permissions/{permission}', [RbacController::class, 'updatePermission']);
    Route::delete('/permissions/{permission}', [RbacController::class, 'destroyPermission']);

    // Endpoint Mapping (independen — resource_key + permission_id)
    Route::get('/endpoints', [RbacController::class, 'indexEndpoints']);
    Route::post('/endpoints', [RbacController::class, 'storeEndpoint']);
    Route::put('/endpoints/{endpoint}', [RbacController::class, 'updateEndpoint']);
    Route::delete('/endpoints/{endpoint}', [RbacController::class, 'destroyEndpoint']);

    // Page Permissions (merged Resource Registry + Page Security)
    Route::get('/page-permissions', [RbacController::class, 'indexPagePermissions']);
    Route::post('/page-permissions', [RbacController::class, 'storePagePermission']);
    Route::put('/page-permissions/{pagePermission}', [RbacController::class, 'updatePagePermission']);
    Route::delete('/page-permissions/{pagePermission}', [RbacController::class, 'destroyPagePermission']);

    // Role Assignment
    Route::get('/roles', [RbacController::class, 'indexRoles']);
    Route::get('/roles/{role}/permissions', [RbacController::class, 'getRolePermissions']);
    Route::put('/roles/{role}/permissions', [RbacController::class, 'syncRolePermissions']);

    // Current user's accessible resources (for frontend PermissionHelper)
    Route::get('/user-resources', [RbacController::class, 'userResources']);
    Route::get('/user-groups', [RbacController::class, 'userGroups']);
});
