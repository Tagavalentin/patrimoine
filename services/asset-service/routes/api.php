<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DepreciationController;
use App\Http\Controllers\Api\DisposalController;
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

    // Sorties de patrimoine : demande, approbation, réalisation
    Route::get('disposals', [DisposalController::class, 'index']);
    Route::get('assets/{asset}/disposals', [DisposalController::class, 'history']);
    Route::post('assets/{asset}/disposals', [DisposalController::class, 'store']);
    Route::get('disposals/{disposal}', [DisposalController::class, 'show']);
    Route::post('disposals/{disposal}/approve', [DisposalController::class, 'approve']);
    Route::post('disposals/{disposal}/reject', [DisposalController::class, 'reject']);
    Route::post('disposals/{disposal}/complete', [DisposalController::class, 'complete']);
    Route::delete('disposals/{disposal}', [DisposalController::class, 'destroy']);

    // Amortissement linéaire : valorisation et échéancier
    Route::get('depreciation/summary', [DepreciationController::class, 'summary']);
    Route::get('assets/{asset}/depreciation', [DepreciationController::class, 'show']);
    Route::get('assets/{asset}/depreciation/schedule', [DepreciationController::class, 'schedule']);
});
