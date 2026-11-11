<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\DisposalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteDisposalRequest;
use App\Http\Requests\DecideDisposalRequest;
use App\Http\Requests\StoreDisposalRequest;
use App\Http\Resources\DisposalResource;
use App\Models\Asset;
use App\Models\Disposal;
use App\Services\DisposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisposalController extends Controller
{
    public function __construct(private readonly DisposalService $service) {}

    /** Liste globale des demandes de sortie. */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Disposal::class);

        $filters = $request->validate([
            'status' => ['sometimes', 'string'],
            'asset_id' => ['sometimes', 'integer', 'min:1'],
            'disposal_type' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return DisposalResource::collection($this->service->paginate($filters))->response();
    }

    /** Historique des sorties d'un bien. */
    public function history(Request $request, Asset $asset): JsonResponse
    {
        $this->authorize('view', $asset);

        return DisposalResource::collection($this->service->historyFor($asset))->response();
    }

    /** Nouvelle demande de sortie pour un bien. */
    public function store(StoreDisposalRequest $request, Asset $asset): JsonResponse
    {
        $this->authorize('create', Disposal::class);

        $user = $request->user();
        $disposal = $this->service->request(
            $asset,
            $request->validated(),
            $user?->getAuthIdentifier(),
            $user?->email(),
            $user?->email(),
        );

        return (new DisposalResource($disposal->load('asset')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Disposal $disposal): JsonResponse
    {
        $this->authorize('view', $disposal);

        return (new DisposalResource($disposal->load('asset')))->response();
    }

    /** Approbation (admin) : le bien passe « réformé ». */
    public function approve(DecideDisposalRequest $request, Disposal $disposal): JsonResponse
    {
        $this->authorize('decide', $disposal);

        $disposal = $this->service->approve(
            $disposal,
            $request->user()?->getAuthIdentifier(),
            $request->validated('decision_reason'),
        );

        return (new DisposalResource($disposal))->response();
    }

    /** Rejet (admin) : motif conservé. */
    public function reject(DecideDisposalRequest $request, Disposal $disposal): JsonResponse
    {
        $this->authorize('decide', $disposal);

        $reason = $request->validated('decision_reason');
        abort_if($reason === null || trim($reason) === '', Response::HTTP_UNPROCESSABLE_ENTITY,
            'Le motif du rejet est obligatoire.');

        $disposal = $this->service->reject(
            $disposal,
            $request->user()?->getAuthIdentifier(),
            $reason,
        );

        return (new DisposalResource($disposal))->response();
    }

    /** Réalisation (admin) : le bien passe « disposed » (terminal). */
    public function complete(CompleteDisposalRequest $request, Disposal $disposal): JsonResponse
    {
        $this->authorize('decide', $disposal);

        $disposal = $this->service->complete(
            $disposal,
            $request->validated(),
            $request->user()?->getAuthIdentifier(),
        );

        return (new DisposalResource($disposal))->response();
    }

    /** Suppression d'une demande encore « requested » (admin). */
    public function destroy(Request $request, Disposal $disposal): JsonResponse
    {
        $this->authorize('delete', $disposal);

        abort_if($disposal->status !== DisposalStatus::Requested, Response::HTTP_CONFLICT,
            'Seules les demandes non traitées peuvent être supprimées.');

        $disposal->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
