<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes (Public)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::get('/auth/verify/{token}', [AuthController::class, 'verifyAccount']);
Route::post('/auth/verify/resend', [AuthController::class, 'resendVerifyAccount']);

// Protected authentication routes
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/token/refresh', [AuthController::class, 'refreshToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

//user
Route::post('/forget-password', [UserController::class, 'forgotPassword'])->name('forget.password');
Route::post('/reset-password/{token}', [UserController::class, 'resetPassword'])->name('reset.password');

// Protected user routes
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/update-info', [UserController::class, 'updateCurrentUser']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
});

// Brand routes (Public)
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/with-products', [BrandController::class, 'getWithProducts']);
Route::get('/brands/search', [BrandController::class, 'search']);
Route::get('/brands/{brand}', [BrandController::class, 'showBySlug']);

// Protected brand routes (Admin)
Route::middleware('auth:api')->group(function () {
    Route::get('/admin/brands', [BrandController::class, 'getAll']);
    Route::post('/admin/brands', [BrandController::class, 'store']);
    Route::get('/admin/brands/{id}', [BrandController::class, 'show']);
    Route::put('/admin/brands/{id}', [BrandController::class, 'update']);
    Route::delete('/admin/brands/{id}', [BrandController::class, 'destroy']);
    Route::patch('/admin/brands/{id}/toggle-status', [BrandController::class, 'toggleStatus']);
});
