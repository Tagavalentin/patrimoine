<?php

declare(strict_types=1);

namespace App\Support\Jwt;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Principal authentifié reconstruit depuis les claims JWT.
 * Pas d'accès base de données : les services font confiance
 * à la signature du token émise par l'auth-service.
 */
class JwtUser implements Authenticatable
{
    /**
     * @param  array<string, mixed>  $claims
     */
    public function __construct(private readonly array $claims) {}

    public function getAuthIdentifierName(): string
    {
        return 'sub';
    }

    public function getAuthIdentifier(): ?int
    {
        return isset($this->claims['sub']) ? (int) $this->claims['sub'] : null;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): ?string
    {
        return null; // jamais utilisé : pas de mot de passe côté JWT
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // no-op : les JWT ne sont pas « mémorisables »
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function id(): ?int
    {
        return $this->getAuthIdentifier();
    }

    public function email(): ?string
    {
        return $this->claims['email'] ?? null;
    }

    public function role(): ?string
    {
        return $this->claims['role'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function claims(): array
    {
        return $this->claims;
    }
}
