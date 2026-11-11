<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Requests\UpdateAssetStatusRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assets) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Asset::class);

        return AssetResource::collection($this->assets->paginate($request->query()));
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $this->authorize('create', Asset::class);

        $asset = $this->assets->create(
            $request->validated(),
            $this->actorId($request),
        );

        return (new AssetResource($asset))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Asset $asset): AssetResource
    {
        $this->authorize('view', $asset);

        return new AssetResource(
            $asset->load(['category', 'location', 'department', 'statusHistories'])
        );
    }

    public function update(UpdateAssetRequest $request, Asset $asset): AssetResource
    {
        $this->authorize('update', $asset);

        return new AssetResource(
            $this->assets->update($asset, $request->validated())
        );
    }

    public function updateStatus(UpdateAssetStatusRequest $request, Asset $asset): AssetResource
    {
        $this->authorize('changeStatus', $asset);

        /** @var array{status: string, reason?: string|null} $data */
        $data = $request->validated();

        return new AssetResource(
            $this->assets->changeStatus(
                $asset,
                AssetStatus::from($data['status']),
                $data['reason'] ?? null,
                $this->actorId($request),
            )
        );
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $this->authorize('delete', $asset);

        $this->assets->delete($asset);

        return response()->json(null, 204);
    }

    private function actorId(Request $request): ?int
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->attributes->get('jwt.claims', []);

        return isset($claims['sub']) ? (int) $claims['sub'] : null;
    }
}
