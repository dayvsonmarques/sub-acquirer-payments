<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientAreaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->prefix('client-area')->name('client-area.')->group(function () {
    Route::get('/', [ClientAreaController::class, 'index'])->name('index');
    Route::get('/pix/{pixTransaction}', [ClientAreaController::class, 'showPix'])->name('pix.show');
    Route::get('/withdraw/{withdrawTransaction}', [ClientAreaController::class, 'showWithdraw'])->name('withdraw.show');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
});

require __DIR__.'/auth.php';
