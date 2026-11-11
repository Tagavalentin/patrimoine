<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Un compte de démonstration par rôle.
     * Mot de passe commun : « password » (démo locale uniquement).
     *
     * @var array<string, array{name: string, email: string}>
     */
    private const USERS = [
        'admin' => ['name' => 'Administrateur', 'email' => 'admin@patrimoine.local'],
        'asset_manager' => ['name' => 'Gestionnaire Patrimoine', 'email' => 'asset.manager@patrimoine.local'],
        'service_head' => ['name' => 'Responsable Service', 'email' => 'service.head@patrimoine.local'],
        'accountant' => ['name' => 'Agent Comptable', 'email' => 'accountant@patrimoine.local'],
        'auditor' => ['name' => 'Auditeur', 'email' => 'auditor@patrimoine.local'],
    ];

    public function run(): void
    {
        foreach (self::USERS as $roleSlug => $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password', // hashé par le cast 'hashed' du modèle
                    'role_id' => Role::where('slug', $roleSlug)->value('id'),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
