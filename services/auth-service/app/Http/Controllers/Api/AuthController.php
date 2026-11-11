<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Jwt\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}

    /**
     * POST /api/login — authentification, retourne un JWT Bearer.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validated();

        /** @var User|null $user */
        $user = User::with('role.permissions')
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $token = $this->jwt->issue([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role?->slug,
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => $this->jwt->ttlSeconds(),
            'user' => new UserResource($user),
        ]);
    }

    /**
     * POST /api/refresh — émet un nouveau token (l'ancien doit être valide).
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->attributes->get('jwt.claims', []);

        $token = $this->jwt->issue([
            'sub' => $claims['sub'] ?? null,
            'email' => $claims['email'] ?? null,
            'role' => $claims['role'] ?? null,
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => $this->jwt->ttlSeconds(),
        ]);
    }

    /**
     * POST /api/logout — JWT sans état : le client doit supprimer
     * son token. Endpoint présent pour la symétrie de l'API.
     */
    public function logout(): JsonResponse
    {
        return response()->json(['message' => 'Déconnecté. Supprimez le token côté client.']);
    }

    /**
     * GET /api/me — profil de l'utilisateur authentifié.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->attributes->get('jwt.claims', []);

        /** @var User|null $user */
        $user = User::with('role.permissions')->find($claims['sub'] ?? null);

        abort_if($user === null, 401, 'Utilisateur introuvable.');

        return response()->json(['data' => new UserResource($user)]);
    }
}
