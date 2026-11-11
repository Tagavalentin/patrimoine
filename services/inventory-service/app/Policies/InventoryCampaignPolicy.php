<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CampaignStatus;
use App\Models\InventoryCampaign;
use App\Support\Jwt\JwtUser;

class InventoryCampaignPolicy
{
    public function viewAny(JwtUser $user): bool
    {
        return true;
    }

    public function view(JwtUser $user, InventoryCampaign $campaign): bool
    {
        return true;
    }

    /** Création / démarrage / clôture : gestion patrimoniale. */
    public function manage(JwtUser $user): bool
    {
        return in_array($user->role(), ['admin', 'asset_manager'], true);
    }

    /** Contrôle physique des lignes : gestion + auditeur (il constate). */
    public function check(JwtUser $user): bool
    {
        return in_array($user->role(), ['admin', 'asset_manager', 'auditor'], true);
    }

    public function delete(JwtUser $user, InventoryCampaign $campaign): bool
    {
        return $user->role() === 'admin' && $campaign->status === CampaignStatus::Draft;
    }
}
