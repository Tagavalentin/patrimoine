<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Distribution des permissions par rôle.
 * Les préfixes « domaine.* » sont expansés sur les permissions existantes.
 */
class RolePermissionSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    private const MATRIX = [
        'admin' => ['*'],
        'asset_manager' => [
            'assets.*', 'maintenance.view', 'maintenance.manage',
            'inventory.view', 'inventory.manage',
            'disposals.view', 'disposals.request',
            'reports.view',
        ],
        'service_head' => [
            'assets.view', 'maintenance.view', 'maintenance.request',
            'inventory.view', 'disposals.view', 'disposals.request',
        ],
        'accountant' => [
            'assets.view', 'depreciation.*',
            'disposals.view', 'disposals.validate',
            'reports.view', 'reports.export',
        ],
        'auditor' => [
            'assets.view', 'maintenance.view', 'inventory.view',
            'disposals.view', 'depreciation.view',
            'reports.view', 'audit.view',
        ],
    ];

    public function run(): void
    {
        $allSlugs = Permission::pluck('slug')->all();

        foreach (self::MATRIX as $roleSlug => $patterns) {
            $role = Role::where('slug', $roleSlug)->firstOrFail();
            $slugs = collect($patterns)->flatMap(
                fn (string $pattern): array => $pattern === '*'
                    ? $allSlugs
                    : array_values(array_filter(
                        $allSlugs,
                        fn (string $slug): bool => str_starts_with($slug, rtrim($pattern, '*')),
                    )),
            )->unique()->values();

            $role->permissions()->sync(
                Permission::whereIn('slug', $slugs)->pluck('id'),
            );
        }
    }
}
