<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Vérifie le claim `role` du JWT : ->middleware('role:admin')
 * ou 'role:admin,accountant'. Aucune base de données requise.
 *
 * Copie partagée : voir shared/jwt/README.md.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var array<string, mixed>|null $claims */
        $claims = $request->attributes->get('jwt.claims');
        $role = $claims['role'] ?? null;

        if ($role === null || ! in_array($role, $roles, true)) {
            throw new AccessDeniedHttpException('Votre rôle ne permet pas cette action.');
        }

        return $next($request);
    }
}
