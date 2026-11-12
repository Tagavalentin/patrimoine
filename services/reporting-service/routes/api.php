<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API — reporting-service
|--------------------------------------------------------------------------
| Préfixe : /api/reports (après retrait du préfixe par la gateway).
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::middleware('jwt.auth')->group(function (): void {
    Route::get('/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/exports/assets.csv', [ReportController::class, 'assetsCsv']);
    Route::get('/exports/maintenance.csv', [ReportController::class, 'maintenanceCsv']);
    Route::get('/exports/inventory.csv', [ReportController::class, 'inventoryCsv']);
    Route::get('/audit-logs', [ReportController::class, 'auditLogs']);
});
