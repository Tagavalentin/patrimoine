<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->with('parent:id,name,code')
            ->when($request->query('search'), fn (Builder $q, string $s) => $q
                ->where(fn (Builder $w) => $w
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")))
            ->when($request->query('parent_id'), fn (Builder $q, int $id) => $q->where('parent_id', $id))
            ->when($request->has('is_active'), fn (Builder $q) => $q
                ->where('is_active', $request->boolean('is_active')))
            ->when(
                in_array($request->query('sort'), ['name', 'code', 'created_at'], true),
                fn (Builder $q) => $q->orderBy($request->query('sort'), $request->query('direction') === 'desc' ? 'desc' : 'asc'),
                fn (Builder $q) => $q->orderBy('name'),
            )
            ->paginate((int) $request->query('per_page', 15));

        return DepartmentResource::collection($departments);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);

        $department = Department::create($request->validated());

        return (new DepartmentResource($department->load('parent')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Department $department): DepartmentResource
    {
        $this->authorize('view', $department);

        return new DepartmentResource($department->load(['parent', 'children']));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource
    {
        $this->authorize('update', $department);

        $department->update($request->validated());

        return new DepartmentResource($department->fresh(['parent', 'children']));
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->authorize('delete', $department);

        $department->delete();

        return response()->json(null, 204);
    }
}
