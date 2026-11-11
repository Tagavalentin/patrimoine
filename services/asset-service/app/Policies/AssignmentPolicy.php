<?php

declare(strict_types=1);

namespace App\Policies;

use App\Support\Jwt\JwtUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Affectations : consultation pour tout rôle authentifié
 * (un service_head doit pouvoir voir les siennes),
 * gestion réservée à admin et asset_manager.
 */
class AssignmentPolicy extends ReferentialPolicy
{
    private const MANAGERS = ['admin', 'asset_manager'];

    public function create(Authenticatable $user): bool
    {
        return $user instanceof JwtUser && in_array($user->role(), self::MANAGERS, true);
    }

    public function update(Authenticatable $user, Model $model): bool
    {
        return $this->create($user);
    }

    public function delete(Authenticatable $user, Model $model): bool
    {
        return $this->create($user);
    }
}
