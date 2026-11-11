<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Disposal;
use App\Support\Jwt\JwtUser;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Sorties de patrimoine : consultation pour tout rôle authentifié,
 * demande par admin/asset_manager, décision et réalisation
 * réservées à l'admin.
 */
class DisposalPolicy
{
    private const MANAGERS = ['admin', 'asset_manager'];

    public function viewAny(Authenticatable $user): bool
    {
        return $user instanceof JwtUser;
    }

    public function view(Authenticatable $user, Disposal $disposal): bool
    {
        return $user instanceof JwtUser;
    }

    public function create(Authenticatable $user): bool
    {
        return $user instanceof JwtUser && in_array($user->role(), self::MANAGERS, true);
    }

    public function decide(Authenticatable $user): bool
    {
        return $user instanceof JwtUser && $user->role() === 'admin';
    }

    public function delete(Authenticatable $user, Disposal $disposal): bool
    {
        return $user instanceof JwtUser && $user->role() === 'admin';
    }
}
