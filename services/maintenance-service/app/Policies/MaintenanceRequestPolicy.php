<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Support\Jwt\JwtUser;

class MaintenanceRequestPolicy
{
    public function viewAny(JwtUser $user): bool
    {
        return true; // tout utilisateur authentifié peut lister
    }

    public function view(JwtUser $user, MaintenanceRequest $request): bool
    {
        return true;
    }

    /** Création de demande : tout rôle authentifié. */
    public function create(JwtUser $user): bool
    {
        return true;
    }

    /** Planifier / démarrer / clôturer : gestion patrimoniale uniquement. */
    public function manage(JwtUser $user, MaintenanceRequest $request): bool
    {
        return in_array($user->role(), ['admin', 'asset_manager'], true);
    }

    public function delete(JwtUser $user, MaintenanceRequest $request): bool
    {
        return $user->role() === 'admin';
    }
}
