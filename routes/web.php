<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientAreaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('client-area')->name('client-area.')->group(function () {
    Route::get('/', [ClientAreaController::class, 'index'])->name('index');
    Route::get('/pix/create', [ClientAreaController::class, 'createPix'])->name('pix.create');
    Route::post('/pix', [ClientAreaController::class, 'storePix'])->name('pix.store');
    Route::get('/withdraw/create', [ClientAreaController::class, 'createWithdraw'])->name('withdraw.create');
    Route::post('/withdraw', [ClientAreaController::class, 'storeWithdraw'])->name('withdraw.store');
    Route::post('/process', [ClientAreaController::class, 'processTransaction'])->name('process');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
});

require __DIR__.'/auth.php';
