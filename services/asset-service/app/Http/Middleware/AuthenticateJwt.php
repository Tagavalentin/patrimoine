<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Jwt\JwtService;
use App\Support\Jwt\JwtUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Valide le token JWT Bearer et expose les claims :
 *  - $request->attributes->get('jwt.claims')   => array des claims
 *  - $request->user()                          => JwtUser (sauf auth-service
 *    qui recharge l'entité User pour /me et le CRUD)
 *
 * Copie partagée : voir shared/jwt/README.md.
 */
class AuthenticateJwt
{
    public function __construct(private readonly JwtService $jwt) {}
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            throw new UnauthorizedHttpException('Bearer', 'Token d\'authentification manquant.');
        }

        $claims = $this->jwt->decode($token);
        $jwtUser = new JwtUser($claims);

        $request->attributes->set('jwt.claims', $claims);
        $request->setUserResolver(static fn (): JwtUser => $jwtUser);
        // Enregistre aussi le principal auprès du guard pour que
        // Gate / $this->authorize() (policies) fonctionnent.
        auth()->setUser($jwtUser);

        return $next($request);
    }
}
