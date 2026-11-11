<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CampaignController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API — inventory-service
|--------------------------------------------------------------------------
| Préfixe : /api/inventory (après retrait du préfixe par la gateway).
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::middleware('jwt.auth')->group(function (): void {
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::get('/campaigns/{id}', [CampaignController::class, 'show'])->whereNumber('id');
    Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy'])->whereNumber('id');
    Route::post('/campaigns/{id}/start', [CampaignController::class, 'start'])->whereNumber('id');
    Route::post('/campaigns/{id}/complete', [CampaignController::class, 'complete'])->whereNumber('id');
    Route::get('/campaigns/{id}/lines', [CampaignController::class, 'lines'])->whereNumber('id');
    Route::patch('/campaigns/{id}/lines/{lineId}', [CampaignController::class, 'checkLine'])
        ->whereNumber(['id', 'lineId']);
    Route::get('/campaigns/{id}/summary', [CampaignController::class, 'summary'])->whereNumber('id');
});
