<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssignmentController;
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

    // Biens — code généré automatiquement, statut via machine à états
    Route::apiResource('assets', AssetController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('assets/{asset}/status', [AssetController::class, 'updateStatus']);

    // Affectations : affecter, transférer, restituer, historique
    Route::get('assignments', [AssignmentController::class, 'index']);
    Route::get('assets/{asset}/assignments', [AssignmentController::class, 'history']);
    Route::post('assets/{asset}/assign', [AssignmentController::class, 'assign']);
    Route::post('assets/{asset}/transfer', [AssignmentController::class, 'transfer']);
    Route::post('assets/{asset}/return', [AssignmentController::class, 'returnAsset']);
});
