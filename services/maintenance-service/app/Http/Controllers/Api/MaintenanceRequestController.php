<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleMaintenanceRequest;
use App\Http\Requests\StoreInterventionRequest;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Resources\InterventionResource;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceRequestController extends Controller
{
    public function __construct(private readonly MaintenanceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $filters = $request->validate([
            'status' => ['sometimes', 'string'],
            'asset_id' => ['sometimes', 'integer', 'min:1'],
            'priority' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        // Les utilisateurs non gestionnaires ne voient que leurs demandes
        $user = $request->user();
        if ($user && ! in_array($user->role(), ['admin', 'asset_manager'], true)) {
            $filters['mine'] = $user->getAuthIdentifier();
        }

        return MaintenanceRequestResource::collection(
            $this->service->paginate($filters)
        )->response();
    }

    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        $this->authorize('create', MaintenanceRequest::class);

        $user = $request->user();
        $model = $this->service->create(
            $request->validated(),
            (string) $request->bearerToken(),
            $user?->getAuthIdentifier(),
            $user?->email(),
            $user?->email(),
        );

        return (new MaintenanceRequestResource($model->load('interventions')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $model = $this->service->find($id);
        abort_if($model === null, Response::HTTP_NOT_FOUND, 'Demande introuvable.');

        $this->authorize('view', $model);

        return (new MaintenanceRequestResource($model))->response();
    }

    public function schedule(ScheduleMaintenanceRequest $request, int $id): JsonResponse
    {
        $model = MaintenanceRequest::findOrFail($id);
        $this->authorize('manage', $model);

        $model = $this->service->schedule(
            $model,
            $request->validated('scheduled_date'),
            $request->user()?->getAuthIdentifier(),
            $request->validated('technician_name'),
        );

        return (new MaintenanceRequestResource($model))->response();
    }

    public function start(Request $request, int $id): JsonResponse
    {
        $model = MaintenanceRequest::findOrFail($id);
        $this->authorize('manage', $model);

        $model = $this->service->start($model, (string) $request->bearerToken(), $request->user()?->getAuthIdentifier());

        return (new MaintenanceRequestResource($model))->response();
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $model = MaintenanceRequest::findOrFail($id);
        $this->authorize('manage', $model);

        $model = $this->service->complete($model, (string) $request->bearerToken());

        return (new MaintenanceRequestResource($model))->response();
    }

    public function addIntervention(StoreInterventionRequest $request, int $id): JsonResponse
    {
        $model = MaintenanceRequest::findOrFail($id);
        $this->authorize('manage', $model);

        $intervention = $this->service->addIntervention(
            $model,
            $request->validated(),
            $request->user()?->getAuthIdentifier(),
        );

        return (new InterventionResource($intervention->load('request')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function summary(int $assetId): JsonResponse
    {
        return response()->json(['data' => $this->service->summaryForAsset($assetId)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $model = MaintenanceRequest::findOrFail($id);
        $this->authorize('delete', $model);

        abort_if($model->status !== MaintenanceStatus::Requested, Response::HTTP_CONFLICT,
            'Seules les demandes non planifiées peuvent être supprimées.');

        $model->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
