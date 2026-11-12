<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde d'accès aux pages : exige un JWT en session
 * (posé par AuthController après login via la gateway).
 */
class EnsureFrontendAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_string(session('jwt_token')) || session('jwt_token') === '') {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
