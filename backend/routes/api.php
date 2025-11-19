<?php

use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaliController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post("/users/login",[UserController::class,"login"]);

Route::middleware(\App\Http\Middleware\ApiAuthMiddleware::class)->group(function(){
    Route::get('/tagihan',[TagihanController::class,'index']);
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

        Route::prefix('/kategori')->group(function(){
            Route::get('/',[KategoriController::class,'index']);
            Route::post('/',[KategoriController::class,'create']);
            Route::put('/{id}',[KategoriController::class,'update']);
            Route::get('/{id}',[KategoriController::class,'get']);
            Route::delete('/{id}',[KategoriController::class,'delete']);
        });

        Route::prefix('/wali')->group(function(){
            Route::get('/',[WaliController::class,'index']);
            Route::post('/',[WaliController::class,'create']);
            Route::put('/{id}',[WaliController::class,'update']);
            Route::get('/{id}',[WaliController::class,'get']);
            Route::delete('/{id}',[WaliController::class,'delete']);
        });

        Route::prefix('/tagihan')->group(function(){
            Route::post('/',[TagihanController::class,'create']);
        });

        Route::prefix('/pembayaran')->group(function(){
            Route::get('/',[PembayaranController::class,'index']);
            Route::post('/bayar/{kode_tagihan}',[PembayaranController::class,'bayar']);
            Route::post('/lunas/{kode_tagihan}',[PembayaranController::class,'lunas']);
            Route::get('/kwitansi/{kode_tagihan}',[PembayaranController::class,'kwitansi']);
            Route::delete('/{kode_pembayaran}',[PembayaranController::class,'delete']);
        });

        Route::put('/setting',[AppSettingController::class,'update']);
    });
});
