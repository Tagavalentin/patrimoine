<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->when($request->query('search'), fn (Builder $q, string $s) => $q
                ->where(fn (Builder $w) => $w
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")))
            ->when($request->has('is_active'), fn (Builder $q) => $q
                ->where('is_active', $request->boolean('is_active')))
            ->when(
                in_array($request->query('sort'), ['name', 'code', 'created_at'], true),
                fn (Builder $q) => $q->orderBy($request->query('sort'), $request->query('direction') === 'desc' ? 'desc' : 'asc'),
                fn (Builder $q) => $q->orderBy('name'),
            )
            ->paginate((int) $request->query('per_page', 15));

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = Category::create($request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        $this->authorize('view', $category);

        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        return new CategoryResource($category->fresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $category->delete(); // soft delete

        return response()->json(null, 204);
    }
}
