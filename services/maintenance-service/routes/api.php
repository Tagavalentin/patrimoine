<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MaintenanceRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API — maintenance-service
|--------------------------------------------------------------------------
| Préfixe : /api/maintenance (après retrait du préfixe par la gateway).
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::middleware('jwt.auth')->group(function (): void {
    Route::get('/requests', [MaintenanceRequestController::class, 'index']);
    Route::post('/requests', [MaintenanceRequestController::class, 'store']);
    Route::get('/requests/{id}', [MaintenanceRequestController::class, 'show'])->whereNumber('id');
    Route::delete('/requests/{id}', [MaintenanceRequestController::class, 'destroy'])->whereNumber('id');
    Route::post('/requests/{id}/schedule', [MaintenanceRequestController::class, 'schedule'])->whereNumber('id');
    Route::post('/requests/{id}/start', [MaintenanceRequestController::class, 'start'])->whereNumber('id');
    Route::post('/requests/{id}/complete', [MaintenanceRequestController::class, 'complete'])->whereNumber('id');
    Route::post('/requests/{id}/interventions', [MaintenanceRequestController::class, 'addIntervention'])->whereNumber('id');
    Route::get('/assets/{assetId}/summary', [MaintenanceRequestController::class, 'summary'])->whereNumber('assetId');
});
