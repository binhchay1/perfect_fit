<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\WishlistController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\ProductReviewController;
use App\Http\Controllers\API\PerfectFitController;
use App\Http\Controllers\API\OrderReturnController;
use App\Http\Controllers\API\SocialAuthController;
use App\Http\Controllers\API\OtpController;
use App\Http\Controllers\API\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\API\Admin\ProductController as AdminProductController;
use App\Http\Controllers\API\Admin\UserController as AdminUserController;
use App\Http\Controllers\API\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\API\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\API\Admin\ShippingSettingsController as AdminShippingSettingsController;
use App\Http\Controllers\API\Admin\ShippingCarrierController as AdminShippingCarrierController;
use App\Http\Controllers\API\Admin\PaymentAccountController as AdminPaymentAccountController;
use App\Http\Controllers\API\Admin\OrderReturnController as AdminOrderReturnController;
use App\Http\Controllers\API\PaymentController;
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

// Social Authentication routes (Public)
Route::post('/auth/social/google', [SocialAuthController::class, 'googleLogin']);
Route::post('/auth/social/facebook', [SocialAuthController::class, 'facebookLogin']);
Route::post('/auth/social/tiktok', [SocialAuthController::class, 'tiktokLogin']);

// OTP Authentication routes (Public)
Route::post('/auth/phone/send-otp', [OtpController::class, 'sendOtp']);
Route::post('/auth/phone/verify-otp', [OtpController::class, 'verifyOtpAndLogin']);
Route::post('/auth/phone/resend-otp', [OtpController::class, 'resendOtp']);

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

// Device management routes
Route::middleware('auth:api')->group(function () {
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::put('/devices/{deviceId}/name', [DeviceController::class, 'updateName']);
    Route::post('/devices/{deviceId}/trust', [DeviceController::class, 'toggleTrust']);
    Route::delete('/devices/{deviceId}', [DeviceController::class, 'revoke']);
    Route::post('/devices/revoke-others', [DeviceController::class, 'revokeAllOthers']);
    Route::put('/devices/fcm-token', [DeviceController::class, 'updateFcmToken']);
});

// Product Reviews (Protected - creating, updating, deleting)
Route::middleware('auth:api')->group(function () {
    Route::post('/products/{productId}/reviews', [ProductReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ProductReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ProductReviewController::class, 'destroy']);
    Route::post('/reviews/{id}/react', [ProductReviewController::class, 'react']);
});

// Perfect Fit AI routes
Route::middleware('auth:api')->group(function () {
    Route::get('/user/body-measurements', [PerfectFitController::class, 'getMeasurements']);
    Route::post('/user/body-measurements', [PerfectFitController::class, 'saveMeasurements']);
    Route::delete('/user/body-measurements', [PerfectFitController::class, 'deleteMeasurements']);
    Route::post('/products/{productId}/size-recommend', [PerfectFitController::class, 'recommendFromMeasurements']);
    Route::post('/products/{productId}/size-recommend-from-image', [PerfectFitController::class, 'recommendFromImage']);
});

// Order Returns routes
Route::middleware('auth:api')->group(function () {
    Route::get('/returns', [OrderReturnController::class, 'index']);
    Route::post('/orders/{orderId}/return', [OrderReturnController::class, 'store']);
    Route::get('/returns/{returnCode}', [OrderReturnController::class, 'show']);
    Route::post('/returns/{id}/cancel', [OrderReturnController::class, 'cancel']);
});

// Brand routes (Public)
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brand/with-products', [BrandController::class, 'getWithProducts']);
Route::get('/brands/search', [BrandController::class, 'search']);
Route::get('/brand/{brand}', [BrandController::class, 'showBySlug']);

// Product routes (Public)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/product/featured', [ProductController::class, 'getFeatured']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/filters', [ProductController::class, 'getWithFilters']);
Route::get('/product/brand/{brandId}', [ProductController::class, 'getByBrand']);
Route::get('/product/gender/{gender}', [ProductController::class, 'getByGender']);
Route::get('/product/{product}', [ProductController::class, 'showBySlug']);

// Product Reviews (Public - viewing)
Route::get('/products/{productId}/reviews', [ProductReviewController::class, 'index']);

// Admin routes (Admin only)
Route::prefix('admin')->middleware(['auth:api', 'admin'])->group(function () {
    // Dashboard & Analytics
    Route::get('/dashboard/overview', [AdminDashboardController::class, 'overview']);
    Route::get('/dashboard/revenue-analytics', [AdminDashboardController::class, 'revenueAnalytics']);
    Route::get('/dashboard/order-analytics', [AdminDashboardController::class, 'orderAnalytics']);
    Route::get('/dashboard/top-products', [AdminDashboardController::class, 'topProducts']);
    Route::get('/dashboard/customer-analytics', [AdminDashboardController::class, 'customerAnalytics']);
    Route::get('/dashboard/brand-analytics', [AdminDashboardController::class, 'brandAnalytics']);

    // User management
    Route::get('/users', [AdminUserController::class, 'getAll']);
    Route::get('/users/statistics', [AdminUserController::class, 'statistics']);
    Route::get('/user/{id}', [AdminUserController::class, 'show']);
    Route::post('/user/{id}', [AdminUserController::class, 'update']);
    Route::delete('/user/{id}', [AdminUserController::class, 'destroy']);
    Route::post('/user/{id}/toggle-status', [AdminUserController::class, 'toggleStatus']);

    // Order management
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/statistics', [AdminOrderController::class, 'statistics']);
    Route::get('/order/{id}', [AdminOrderController::class, 'show']);
    Route::put('/order/{id}/status', [AdminOrderController::class, 'updateStatus']);
    Route::put('/order/{id}/tracking', [AdminOrderController::class, 'updateTracking']);
    Route::post('/order/{id}/cancel', [AdminOrderController::class, 'cancel']);
    Route::post('/order/{id}/refund', [AdminOrderController::class, 'refund']);

    // Brand management
    Route::get('/brands', [AdminBrandController::class, 'getAll']);
    Route::post('/brand', [AdminBrandController::class, 'store']);
    Route::get('/brand/{id}', [AdminBrandController::class, 'show']);
    Route::post('/brand/{id}', [AdminBrandController::class, 'update']);
    Route::delete('/brand/{id}', [AdminBrandController::class, 'destroy']);
    Route::post('/brand/{id}/toggle-status', [AdminBrandController::class, 'toggleStatus']);

    // Product management
    Route::get('/products', [AdminProductController::class, 'getAll']);
    Route::post('/product', [AdminProductController::class, 'store']);
    Route::get('/product/{id}', [AdminProductController::class, 'show']);
    Route::post('/product/{id}', [AdminProductController::class, 'update']);
    Route::delete('/product/{id}', [AdminProductController::class, 'destroy']);
    Route::post('/product/{id}/toggle-status', [AdminProductController::class, 'toggleStatus']);
    Route::delete('/products/bulk-delete', [AdminProductController::class, 'bulkDelete']);

    // Shipping Settings management
    Route::get('/shipping/settings', [AdminShippingSettingsController::class, 'getSettings']);
    Route::post('/shipping/settings', [AdminShippingSettingsController::class, 'updateSettings']);


    // Shipping Carriers management
    Route::get('/shipping/carriers/domestic', [AdminShippingCarrierController::class, 'getDomesticCarriers']);
    Route::get('/shipping/carriers/inter-province', [AdminShippingCarrierController::class, 'getInterProvinceCarriers']);
    Route::post('/shipping/carrier', [AdminShippingCarrierController::class, 'createCarrier']);
    Route::post('/shipping/carrier/{id}', [AdminShippingCarrierController::class, 'updateCarrier']);
    Route::post('/shipping/carrier/{id}/set-default', [AdminShippingCarrierController::class, 'setAsDefault']);

    // Payment Accounts management
    Route::get('/payment-accounts', [AdminPaymentAccountController::class, 'index']);
    Route::post('/payment-accounts', [AdminPaymentAccountController::class, 'store']);
    Route::put('/payment-accounts/{id}', [AdminPaymentAccountController::class, 'update']);
    Route::delete('/payment-accounts/{id}', [AdminPaymentAccountController::class, 'destroy']);
    Route::post('/payment-accounts/{id}/set-default', [AdminPaymentAccountController::class, 'setDefault']);
    Route::post('/payment-accounts/{id}/toggle-status', [AdminPaymentAccountController::class, 'toggleStatus']);

    // Order Returns management
    Route::get('/returns', [AdminOrderReturnController::class, 'index']);
    Route::put('/returns/{id}/status', [AdminOrderReturnController::class, 'updateStatus']);
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
    Route::get('/purchased-products', [OrderController::class, 'purchasedProducts']);
});

// Payment routes
Route::middleware('auth:api')->group(function () {
    Route::post('/payment/create', [PaymentController::class, 'createPayment']);
    Route::get('/payment/status', [PaymentController::class, 'getPaymentStatus']);
    Route::get('/orders/{order_id}/payment-link', [PaymentController::class, 'getPaymentLink']);
});

// Payment callback routes (Public - VNPay will call these)
Route::get('/payment/vnpay/callback', [PaymentController::class, 'vnpayCallback']);
