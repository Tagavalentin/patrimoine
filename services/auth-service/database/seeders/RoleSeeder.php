<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Les 5 acteurs du projet — un rôle par acteur.
     *
     * @var array<string, string>
     */
    private const ROLES = [
        'admin' => 'Administrateur — accès complet à la plateforme',
        'asset_manager' => 'Gestionnaire du patrimoine — biens, affectations, cessions',
        'service_head' => 'Responsable de service — consultation et demandes',
        'accountant' => 'Agent comptable — valeurs, amortissements, validation des cessions',
        'auditor' => 'Auditeur — lecture seule et journal d\'audit',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $slug => $description) {
            Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucfirst(str_replace('_', ' ', $slug)),
                    'description' => $description,
                ],
            );
        }
    }
}
