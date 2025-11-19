<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PixController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WithdrawController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', 'throttle:200,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/pix', [PixController::class, 'store']);
    Route::post('/withdraw', [WithdrawController::class, 'store']);
});

Route::prefix('webhooks')->group(function () {
    Route::post('/pix/{subacquirer}', [WebhookController::class, 'pix']);
    Route::post('/withdraw/{subacquirer}', [WebhookController::class, 'withdraw']);
});

