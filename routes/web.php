<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to Rental Mobil API',
        'version' => '1.0'
    ]);
});