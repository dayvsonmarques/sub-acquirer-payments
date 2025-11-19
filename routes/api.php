<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PixController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WithdrawController;
use Illuminate\Support\Facades\Route;

$localThrottle = app()->environment('local', 'testing') ? 'throttle:2000,1' : 'throttle:200,1';

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['ensure-bearer', 'auth:sanctum', $localThrottle])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/pix', [PixController::class, 'store']);
    Route::post('/withdraw', [WithdrawController::class, 'store']);
});

Route::prefix('webhooks')->group(function () {
    Route::post('/pix/{subacquirer}', [WebhookController::class, 'pix']);
    Route::post('/withdraw/{subacquirer}', [WebhookController::class, 'withdraw']);
});

