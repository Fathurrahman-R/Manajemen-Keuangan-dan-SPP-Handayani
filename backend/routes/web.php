<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to Handayani API 🚀',
        'version' => app()->version(),
    ]);
});
