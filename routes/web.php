<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Payment test page
Route::get('/payment/test', function () {
    $token = 'YOUR_TEST_TOKEN'; // You can change this to a real token
    return view('payment.test', compact('token'));
});

/*
|--------------------------------------------------------------------------
| Swagger Documentation Routes
|--------------------------------------------------------------------------
|
| Routes for API documentation using Swagger
|
*/

Route::get('/docs', function () {
    return view('vendor.l5-swagger.index', [
        'documentation' => 'default',
        'urlToDocs' => route('l5-swagger.default.docs'),
        'useAbsolutePath' => config('l5-swagger.defaults.paths.use_absolute_path', true),
        'operationsSorter' => config('l5-swagger.defaults.ui.operations_sort'),
        'configUrl' => config('l5-swagger.defaults.paths.additional_config_url'),
        'validatorUrl' => config('l5-swagger.defaults.ui.validator_url')
    ]);
})->name('api.documentation');

// L5-Swagger will handle the /swagger-docs route automatically