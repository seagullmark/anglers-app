<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\FishingTripController;
use App\Http\Controllers\UserPhotoController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

use App\Models\User;

Route::get('/fm-eloquent-test', function () {

    $start = microtime(true);

    try {

        $user = User::where(
            'email',
            'nishino@hoge.com'
        )->first();

        return response()->json([
            'seconds' => microtime(true) - $start,
            'found'   => ! is_null($user),
            'user'    => $user,
        ]);
    } catch (\Throwable $e) {

        return response()->json([
            'seconds' => microtime(true) - $start,
            'error'   => $e->getMessage(),
            'class'   => get_class($e),
            'trace'   => $e->getTraceAsString(),
        ], 500);
    }
});

Route::get('/fm-test', function () {
    try {
        $response = Http::timeout(10)->get(
            'https://hay-luxury-folks-worship.trycloudflare.com/fmi/data/vLatest/productInfo'
        );

        return response()->json([
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/fm-login-test', function () {

    try {
        $response = Http::withBasicAuth(
            env('DB_USERNAME'),
            env('DB_PASSWORD')
        )->post(
            'https://hay-luxury-folks-worship.trycloudflare.com/fmi/data/vLatest/databases/anglers/sessions'
        );

        return [
            'status' => $response->status(),
            'body' => $response->json(),
            'raw' => $response->body(),
        ];
    } catch (\Throwable $e) {

        return response()->json([
            'error' => $e->getMessage(),
            'class' => get_class($e),
        ], 500);
    }
});

Route::get('/fm-login-timer', function () {

    $start = microtime(true);

    try {

        $response = Http::withBasicAuth(
            env('DB_USERNAME'),
            env('DB_PASSWORD')
        )->withBody('', 'application/json')
            ->post(
                'https://hay-luxury-folks-worship.trycloudflare.com/fmi/data/vLatest/databases/anglers/sessions'
            );

        return [
            'status' => $response->status(),
            'body'   => $response->json(),
            'raw'    => $response->body(),
        ];
    } catch (\Throwable $e) {

        return response()->json([
            'seconds' => microtime(true) - $start,
            'error'   => $e->getMessage(),
            'class'   => get_class($e),
        ], 500);
    }
});

Route::get('/fm-find-test', function () {

    $start = microtime(true);
    $baseUrl = 'https://hay-luxury-folks-worship.trycloudflare.com/fmi/data/vLatest/databases/anglers';

    try {

        $login = Http::withBasicAuth(
            env('DB_USERNAME'),
            env('DB_PASSWORD')
        )->withBody('', 'application/json')
            ->post($baseUrl . '/sessions');

        $token = data_get($login->json(), 'response.token');

        if (blank($token)) {
            return response()->json([
                'seconds' => microtime(true) - $start,
                'login_status' => $login->status(),
                'login_body' => $login->json(),
                'login_raw' => $login->body(),
                'error' => 'FileMaker session token was not returned.',
            ], 502);
        }

        $response = Http::withToken($token)->post(
            $baseUrl . '/layouts/users/_find',
            [
                'query' => [
                    ['email' => 'nishino@hoge.com'],
                ],
                'limit' => 1,
            ]
        );

        return response()->json([
            'seconds' => microtime(true) - $start,
            'login_status' => $login->status(),
            'find_status' => $response->status(),
            'body' => $response->json(),
            'raw' => $response->body(),
        ]);
    } catch (\Throwable $e) {

        return response()->json([
            'seconds' => microtime(true) - $start,
            'error'   => $e->getMessage(),
            'class'   => get_class($e),
        ], 500);
    }
});

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
