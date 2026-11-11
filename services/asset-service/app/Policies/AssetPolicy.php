<?php

declare(strict_types=1);

namespace App\Policies;

use App\Support\Jwt\JwtUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Biens : consultation pour tout rôle authentifié,
 * gestion réservée à admin et asset_manager
 * (équivalent des permissions assets.*).
 */
class AssetPolicy extends ReferentialPolicy
{
    private const MANAGERS = ['admin', 'asset_manager'];

    public function create(Authenticatable $user): bool
    {
        return $this->isManager($user);
    }

    public function update(Authenticatable $user, Model $model): bool
    {
        return $this->isManager($user);
    }

    public function delete(Authenticatable $user, Model $model): bool
    {
        return $this->isManager($user);
    }

    public function changeStatus(Authenticatable $user, Model $model): bool
    {
        return $this->isManager($user);
    }

    protected function isManager(Authenticatable $user): bool
    {
        return $user instanceof JwtUser && in_array($user->role(), self::MANAGERS, true);
    }
}
