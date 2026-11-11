<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignmentRequest;
use App\Http\Requests\ReturnAssetRequest;
use App\Http\Resources\AssignmentResource;
use App\Models\Asset;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments) {}

    /**
     * GET /api/assignments — liste filtrée (asset, service, responsable, actif).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Assignment::class);

        return AssignmentResource::collection(
            $this->assignments->paginate($request->query())
        );
    }

    /**
     * GET /api/assets/{asset}/assignments — historique complet d'un bien.
     */
    public function history(Asset $asset): JsonResponse
    {
        $this->authorize('viewAny', Assignment::class);

        return response()->json([
            'data' => AssignmentResource::collection($this->assignments->historyFor($asset)),
        ]);
    }

    /**
     * POST /api/assets/{asset}/assign — affecte un bien acquis.
     */
    public function assign(AssignmentRequest $request, Asset $asset): JsonResponse
    {
        $this->authorize('create', Assignment::class);

        $assignment = $this->assignments->assign(
            $asset,
            $request->validated(),
            $this->actorId($request),
        );

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * POST /api/assets/{asset}/transfer — transfère vers un autre service.
     */
    public function transfer(AssignmentRequest $request, Asset $asset): JsonResponse
    {
        $this->authorize('create', Assignment::class);

        $assignment = $this->assignments->transfer(
            $asset,
            $request->validated(),
            $this->actorId($request),
        );

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * POST /api/assets/{asset}/return — restitue le bien au parc.
     */
    public function returnAsset(ReturnAssetRequest $request, Asset $asset): JsonResponse
    {
        $this->authorize('create', Assignment::class);

        /** @var array{end_date?: string|null, reason?: string|null} $data */
        $data = $request->validated();

        $assignment = $this->assignments->returnToPool(
            $asset,
            $data['end_date'] ?? null,
            $data['reason'] ?? null,
            $this->actorId($request),
        );

        return (new AssignmentResource($assignment))->response();
    }

    private function actorId(Request $request): ?int
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->attributes->get('jwt.claims', []);

        return isset($claims['sub']) ? (int) $claims['sub'] : null;
    }
}
