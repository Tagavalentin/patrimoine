<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Permissions fonctionnelles du domaine patrimoine.
     * Le seeder suivant (RolePermissionSeeder) les distribue par rôle.
     *
     * @var array<string, string>
     */
    private const PERMISSIONS = [
        'users.manage' => 'Gérer les utilisateurs',
        'referentials.manage' => 'Gérer les référentiels (catégories, sites, services)',
        'assets.view' => 'Consulter les biens',
        'assets.create' => 'Créer un bien',
        'assets.update' => 'Modifier un bien',
        'assets.delete' => 'Supprimer un bien',
        'assets.assign' => 'Affecter et transférer un bien',
        'maintenance.view' => 'Consulter les demandes de maintenance',
        'maintenance.request' => 'Demander une maintenance',
        'maintenance.manage' => 'Gérer les interventions de maintenance',
        'inventory.view' => 'Consulter les campagnes d\'inventaire',
        'inventory.manage' => 'Gérer les campagnes d\'inventaire',
        'disposals.view' => 'Consulter les cessions et réformes',
        'disposals.request' => 'Demander une cession ou réforme',
        'disposals.validate' => 'Valider une cession ou réforme',
        'depreciation.view' => 'Consulter les amortissements',
        'depreciation.manage' => 'Gérer les amortissements',
        'reports.view' => 'Consulter les rapports',
        'reports.export' => 'Exporter les rapports',
        'audit.view' => 'Consulter le journal d\'audit',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $slug => $name) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'group' => explode('.', $slug)[0],
                ],
            );
        }
    }
}
