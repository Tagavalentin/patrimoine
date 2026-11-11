<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckLineRequest;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\InventoryLineResource;
use App\Models\InventoryCampaign;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CampaignController extends Controller
{
    public function __construct(private readonly InventoryService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryCampaign::class);

        $filters = $request->validate([
            'status' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return CampaignResource::collection(
            $this->service->paginateCampaigns($filters)
        )->response();
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $this->authorize('manage', InventoryCampaign::class);

        $user = $request->user();
        $campaign = $this->service->create(
            $request->validated(),
            (string) $request->bearerToken(),
            $user?->getAuthIdentifier(),
            $user?->email(),
            $user?->email(),
        );

        return (new CampaignResource($campaign))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $campaign = $this->service->findCampaign($id);
        abort_if($campaign === null, Response::HTTP_NOT_FOUND, 'Campagne introuvable.');

        $this->authorize('view', $campaign);

        return (new CampaignResource($campaign))->response();
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $campaign = InventoryCampaign::findOrFail($id);
        $this->authorize('delete', $campaign);

        $this->service->deleteDraft($campaign);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function start(Request $request, int $id): JsonResponse
    {
        $campaign = InventoryCampaign::findOrFail($id);
        $this->authorize('manage', InventoryCampaign::class);

        return (new CampaignResource($this->service->start($campaign)))->response();
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $campaign = InventoryCampaign::findOrFail($id);
        $this->authorize('manage', InventoryCampaign::class);

        return (new CampaignResource($this->service->complete($campaign)))->response();
    }

    /** Lignes d'inventaire (contrôle physique). */
    public function lines(Request $request, int $id): JsonResponse
    {
        $campaign = InventoryCampaign::findOrFail($id);
        $this->authorize('view', $campaign);

        $filters = $request->validate([
            'check_status' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        return InventoryLineResource::collection(
            $this->service->paginateLines($campaign, $filters)
        )->response();
    }

    public function checkLine(CheckLineRequest $request, int $id, int $lineId): JsonResponse
    {
        $campaign = InventoryCampaign::findOrFail($id);
        $this->authorize('check', InventoryCampaign::class);

        $line = $this->service->findLine($id, $lineId);
        abort_if($line === null, Response::HTTP_NOT_FOUND, 'Ligne introuvable.');

        $user = $request->user();
        $line = $this->service->checkLine(
            $line,
            $request->validated('check_status'),
            $request->validated('notes'),
            $user?->getAuthIdentifier(),
            $user?->email(),
        );

        return (new InventoryLineResource($line))->response();
    }

    /** Synthèse : totaux et taux d'écarts. */
    public function summary(Request $request, int $id): JsonResponse
    {
        $campaign = InventoryCampaign::findOrFail($id);
        $this->authorize('view', $campaign);

        return response()->json(['data' => $this->service->summary($campaign)]);
    }
}
