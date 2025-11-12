<?php

use App\Http\Controllers\JenjangController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post("/users/login",[UserController::class,"login"]);

Route::middleware(\App\Http\Middleware\ApiAuthMiddleware::class)->group(function(){
    Route::middleware(\App\Http\Middleware\ApiRoleMiddleware::class.':admin')->group(function(){
        Route::post("/users",[UserController::class,"register"]);
        Route::get("/users/current",[UserController::class,"get"]);
        Route::patch('/users/current',[UserController::class,"update"]);
        Route::delete('users/logout',[UserController::class,"logout"]);

        Route::prefix('/siswa')->group(function(){
            Route::get('/{jenjang}',[SiswaController::class,'index']);
            Route::post('/{jenjang}',[SiswaController::class,'create']);
            Route::put('/{jenjang}/{id}',[SiswaController::class,'update']);
            Route::get('/{jenjang}/{id}',[SiswaController::class,'get']);
            Route::delete('/{jenjang}/{id}',[SiswaController::class,'delete']);

        });

        Route::prefix('/kelas')->group(function(){
            Route::get('/{jenjang}',[KelasController::class,'index']);
            Route::post('/{jenjang}',[KelasController::class,'create']);
            Route::put('/{jenjang}/{id}',[KelasController::class,'update']);
            Route::get('/{jenjang}/{id}',[KelasController::class,'get']);
            Route::delete('/{jenjang}/{id}',[KelasController::class,'delete']);
        });

    });
});
