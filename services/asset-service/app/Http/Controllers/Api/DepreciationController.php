<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\DepreciationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepreciationController extends Controller
{
    public function __construct(private readonly DepreciationService $service) {}

    /** Valorisation nette comptable d'un bien à une date donnée. */
    public function show(Request $request, Asset $asset): JsonResponse
    {
        $this->authorize('view', $asset);

        $date = $request->validate(['date' => ['sometimes', 'date']])['date'] ?? null;

        return response()->json(['data' => $this->service->valuation($asset, $date)]);
    }

    /** Échéancier annuel d'amortissement d'un bien. */
    public function schedule(Request $request, Asset $asset): JsonResponse
    {
        $this->authorize('view', $asset);

        return response()->json(['data' => $this->service->schedule($asset)]);
    }

    /** Synthèse du portefeuille (biens non sortis du patrimoine). */
    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $date = $request->validate(['date' => ['sometimes', 'date']])['date'] ?? null;

        return response()->json(['data' => $this->service->portfolioSummary($date)]);
    }
}
