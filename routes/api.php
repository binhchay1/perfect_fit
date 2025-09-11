<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\WishlistController;
use App\Http\Controllers\API\OrderController;
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
Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
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
Route::get('/brand/with-products', [BrandController::class, 'getWithProducts']);
Route::get('/brands/search', [BrandController::class, 'search']);
Route::get('/brand/{brand}', [BrandController::class, 'showBySlug']);

// Protected brand routes (Admin)
Route::middleware('auth:api')->group(function () {
    Route::get('/admin/brands', [BrandController::class, 'getAll']);
    Route::post('/admin/brand', [BrandController::class, 'store']);
    Route::get('/admin/brand/{id}', [BrandController::class, 'show']);
    Route::post('/admin/brand/{id}', [BrandController::class, 'update']);
    Route::delete('/admin/brand/{id}', [BrandController::class, 'destroy']);
    Route::post('/admin/brand/{id}/toggle-status', [BrandController::class, 'toggleStatus']);
});

// Product routes (Public)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/product/featured', [ProductController::class, 'getFeatured']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/filters', [ProductController::class, 'getWithFilters']);
Route::get('/product/brand/{brandId}', [ProductController::class, 'getByBrand']);
Route::get('/product/gender/{gender}', [ProductController::class, 'getByGender']);
Route::get('/product/{product}', [ProductController::class, 'showBySlug']);

// Protected product routes (Admin)
Route::middleware('auth:api')->group(function () {
    Route::get('/admin/products', [ProductController::class, 'getAll']);
    Route::post('/admin/product', [ProductController::class, 'store']);
    Route::get('/admin/product/{id}', [ProductController::class, 'show']);
    Route::post('/admin/product/{id}', [ProductController::class, 'update']);
    Route::delete('/admin/product/{id}', [ProductController::class, 'destroy']);
    Route::post('/admin/product/{id}/toggle-status', [ProductController::class, 'toggleStatus']);
});

// Cart routes (Protected - User must be logged in)
Route::middleware('auth:api')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);
    Route::get('/cart/summary', [CartController::class, 'summary']);
});

// Wishlist routes (Protected - User must be logged in)
Route::middleware('auth:api')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);
    Route::post('/wishlist/remove-by-product', [WishlistController::class, 'removeByProduct']);
    Route::delete('/wishlist', [WishlistController::class, 'clear']);
    Route::get('/wishlist/count', [WishlistController::class, 'count']);
    Route::post('/wishlist/check', [WishlistController::class, 'check']);
});

// Order routes (Protected - User must be logged in)
Route::middleware('auth:api')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/order', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{id}/tracking', [OrderController::class, 'tracking']);
});
