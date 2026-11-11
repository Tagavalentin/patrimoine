<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

// ------------------------------------------------------------------
// Authentification — publique via la gateway sous /api/auth/*
// (la gateway retire le préfixe /api/auth avant de transmettre)
// ------------------------------------------------------------------
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/refresh', [AuthController::class, 'refresh'])
    ->middleware(['jwt.auth', 'throttle:10,1']);

Route::middleware('jwt.auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // CRUD utilisateurs — réservé à l'admin (claim role + authorize())
    Route::apiResource('users', UserController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->middleware('role:admin');
});
