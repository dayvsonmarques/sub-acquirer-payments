<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PixController;
use App\Http\Controllers\Api\WithdrawController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/pix', [PixController::class, 'store']);
    Route::post('/withdraw', [WithdrawController::class, 'store']);
});

