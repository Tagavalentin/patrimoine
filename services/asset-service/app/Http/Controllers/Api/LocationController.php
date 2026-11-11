<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Location::class);

        $locations = Location::query()
            ->when($request->query('search'), fn (Builder $q, string $s) => $q
                ->where(fn (Builder $w) => $w
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('city', 'like', "%{$s}%")))
            ->when($request->query('type'), fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($request->query('city'), fn (Builder $q, string $city) => $q->where('city', 'like', "%{$city}%"))
            ->when($request->has('is_active'), fn (Builder $q) => $q
                ->where('is_active', $request->boolean('is_active')))
            ->when(
                in_array($request->query('sort'), ['name', 'code', 'city', 'created_at'], true),
                fn (Builder $q) => $q->orderBy($request->query('sort'), $request->query('direction') === 'desc' ? 'desc' : 'asc'),
                fn (Builder $q) => $q->orderBy('name'),
            )
            ->paginate((int) $request->query('per_page', 15));

        return LocationResource::collection($locations);
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $this->authorize('create', Location::class);

        $location = Location::create($request->validated());

        return (new LocationResource($location))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Location $location): LocationResource
    {
        $this->authorize('view', $location);

        return new LocationResource($location);
    }

    public function update(UpdateLocationRequest $request, Location $location): LocationResource
    {
        $this->authorize('update', $location);

        $location->update($request->validated());

        return new LocationResource($location->fresh());
    }

    public function destroy(Location $location): JsonResponse
    {
        $this->authorize('delete', $location);

        $location->delete();

        return response()->json(null, 204);
    }
}
