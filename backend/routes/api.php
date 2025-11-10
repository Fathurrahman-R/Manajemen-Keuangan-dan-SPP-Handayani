<?php

use App\Http\Controllers\JenjangController;
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

        Route::get('/siswas/{jenjang}',[SiswaController::class,'index']);
        Route::post('/siswas/{jenjang}',[SiswaController::class,'create']);
        Route::put('/siswas/{jenjang}/{id}',[SiswaController::class,'update']);
        Route::get('/siswas/{jenjang}/{id}',[SiswaController::class,'get']);
        Route::delete('/siswas/{jenjang}/{id}',[SiswaController::class,'delete']);
    });
});
