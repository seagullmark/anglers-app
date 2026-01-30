<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserPhotoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return inertia('Index/Index');
    })->name('index');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::resource('user-photos', UserPhotoController::class)->only(['store']);

    Route::get('/container/{path}', [App\Http\Controllers\ContainerController::class, 'getImage'])
        ->name('container');
});
