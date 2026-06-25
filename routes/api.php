<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\HistoryController;

// PUBLIC ROUTES (tidak perlu login)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// PROTECTED ROUTES (wajib login + token)
Route::middleware('auth:sanctum')->group(function () {
    // CARS
    Route::get('/cars', [CarController::class, 'index']);
    Route::get('/cars/available', [CarController::class, 'available']);
    Route::get('/cars/{id}', [CarController::class, 'show']);
    Route::post('/cars', [CarController::class, 'store']);
    Route::post('/cars/batch', [CarController::class, 'batchStore']);
    Route::put('/cars/{id}', [CarController::class, 'update']);
    Route::delete('/cars/{id}', [CarController::class, 'destroy']);
    
    // CUSTOMERS
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    
    // RENTALS
    Route::get('/rentals', [RentalController::class, 'index']);
    Route::get('/rentals/{id}', [RentalController::class, 'show']);
    Route::post('/rentals', [RentalController::class, 'store']);
    Route::get('/rentals/customer/{id}', [RentalController::class, 'getByCustomer']);
    Route::put('/rentals/{id}', [RentalController::class, 'update']);
    Route::put('/rentals/{id}/status', [RentalController::class, 'updateStatus']);
    Route::delete('/rentals/{id}', [RentalController::class, 'destroy']);
    
    //PAYMENTS
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/paid', [PaymentController::class, 'paid']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::put('/payments/{id}', [PaymentController::class, 'update']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
    
    // HISTORY
    Route::get('/history', [HistoryController::class, 'index']);
    
    // AUTH
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});