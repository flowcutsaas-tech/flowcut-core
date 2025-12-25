<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeTestController;

Route::get('/test-checkout', [StripeTestController::class, 'testCheckout']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => 'v1-from-git'
    ]);
});
