<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\LocationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Tous les endpoints métier exigent un JWT valide.
// L'autorisation fine est assurée par les Policies (voir app/Policies).
Route::middleware('jwt.auth')->group(function (): void {
    Route::apiResource('categories', CategoryController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::apiResource('locations', LocationController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::apiResource('departments', DepartmentController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
});
