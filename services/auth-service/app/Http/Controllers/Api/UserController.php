<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UserController extends Controller
{
    /**
     * GET /api/users — liste paginée, recherche et filtre par rôle.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::with('role.permissions')
            ->when($request->query('search'), function ($query, string $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->query('role'), fn ($query, string $role) => $query
                ->whereHas('role', fn ($q) => $q->where('slug', $role)))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * POST /api/users — création (admin).
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // hashé par le cast 'hashed'
            'role_id' => $this->roleId($data['role']),
        ]);

        return (new UserResource($user->load('role.permissions')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/users/{user} — détail (admin).
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user->load('role.permissions'));
    }

    /**
     * PUT|PATCH /api/users/{user} — modification (admin).
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        $user->fill(collect($data)->except('role')->all());

        if (isset($data['role'])) {
            $user->role_id = $this->roleId($data['role']);
        }

        $user->save();

        return new UserResource($user->fresh('role.permissions'));
    }

    /**
     * DELETE /api/users/{user} — suppression (admin, sauf soi-même).
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->attributes->get('jwt.claims', []);

        if ((int) ($claims['sub'] ?? 0) === $user->id) {
            throw new UnprocessableEntityHttpException('Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return response()->json(null, 204);
    }

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->value('id')
            ?? throw new UnprocessableEntityHttpException("Rôle « {$slug} » inconnu.");
    }
}
