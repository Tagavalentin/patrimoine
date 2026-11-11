<?php

declare(strict_types=1);

namespace App\Support\Jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Service JWT — kit partagé entre les services.
 *
 * Ce fichier fait partie du « kit JWT » documenté dans
 * shared/jwt/README.md : chaque service possède une copie identique
 * et valide les tokens avec le même JWT_SECRET.
 *
 * Les services ne partagent AUCUNE base de données : ils se
 * contentent de valider la signature et de lire les claims
 * (sub, email, role) sans recharger l'utilisateur en base.
 */
class JwtService
{
    public const ALG = 'HS256';

    /**
     * Émet un token à partir de claims métier.
     *
     * @param  array<string, mixed>  $claims
     */
    public function issue(array $claims): string
    {
        $now = now();

        $payload = array_merge([
            'iss' => config('jwt.issuer'),
            'iat' => $now->timestamp,
            'nbf' => $now->timestamp,
            'exp' => $now->copy()->addMinutes($this->ttlMinutes())->timestamp,
            'jti' => (string) Str::uuid(),
        ], $claims);

        return JWT::encode($payload, $this->secret(), self::ALG);
    }

    /**
     * Décode et valide un token (signature + expiration).
     *
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        try {
            return (array) JWT::decode($token, new Key($this->secret(), self::ALG));
        } catch (\Throwable) {
            throw new UnauthorizedHttpException('Bearer', 'Token invalide ou expiré.');
        }
    }

    public function ttlMinutes(): int
    {
        return (int) config('jwt.ttl', 60);
    }

    public function ttlSeconds(): int
    {
        return $this->ttlMinutes() * 60;
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');

        if ($secret === '' || $secret === 'change_me_in_production') {
            throw new \RuntimeException('JWT_SECRET non configuré.');
        }

        return $secret;
    }
}
