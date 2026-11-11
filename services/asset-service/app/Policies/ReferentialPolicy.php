<?php

declare(strict_types=1);

namespace App\Policies;

use App\Support\Jwt\JwtUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Politique de base des référentiels (catégories, localisations,
 * départements) : lecture pour tout utilisateur authentifié,
 * écriture réservée au rôle admin (seul détenteur de la
 * permission referentials.manage).
 */
abstract class ReferentialPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $user instanceof JwtUser;
    }

    public function view(Authenticatable $user, Model $model): bool
    {
        return $user instanceof JwtUser;
    }

    public function create(Authenticatable $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(Authenticatable $user, Model $model): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(Authenticatable $user, Model $model): bool
    {
        return $this->isAdmin($user);
    }

    protected function isAdmin(Authenticatable $user): bool
    {
        return $user instanceof JwtUser && $user->role() === 'admin';
    }
}
