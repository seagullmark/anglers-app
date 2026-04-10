<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\FishingTripController;
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

    Route::get('/user/profile-photo', [UserPhotoController::class, 'create'])
        ->name('user.profile-photo');

    Route::resource('fishing-trips', FishingTripController::class);
    Route::get('fishing-trip-photos/{fishing_trip_photo}/image', [ContainerController::class, 'showFishingTripPhoto'])
        ->name('fishing-trip-photos.image');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::resource('user-photos', UserPhotoController::class)->only(['store']);
    Route::get('user-photos/{user_photo}/image', [ContainerController::class, 'showUserPhoto'])
        ->name('user-photos.image');
});
