<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardMenuTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGateway(string $role): void
    {
        Http::fake([
            '*/api/auth/me' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'Test User',
                    'email' => 'test@patrimoine.local',
                    'role' => ['id' => 1, 'name' => 'Role', 'slug' => $role],
                ],
            ], 200),
            '*/api/reports/dashboard' => Http::response([
                'data' => [
                    'generated_at' => '2026-11-13T09:00:00+00:00',
                    'assets' => [
                        'total' => 12,
                        'by_status' => [],
                        'net_book_value' => 9800000,
                        'accumulated_depreciation' => 1200000,
                    ],
                    'maintenance' => [
                        'total' => 3,
                        'by_status' => [],
                        'open' => 3,
                    ],
                    'inventory' => [
                        'latest_campaign' => 'INV-2026-0002',
                        'latest_status' => 'in_progress',
                        'latest_summary' => [],
                    ],
                ],
            ], 200),
        ]);
    }

    private function sessionFor(string $role): array
    {
        return ['jwt_token' => 'jwt-test-token', 'jwt_user' => [
            'id' => 1, 'name' => 'Test User', 'email' => 'test@patrimoine.local',
            'role' => $role, 'role_name' => ucfirst(str_replace('_', ' ', $role)),
        ]];
    }

    /**
     * Vérifie qu'un libellé de menu apparaît comme texte d'un lien de navigation.
     */
    private function assertMenuLink($response, string $label): void
    {
        $this->assertMatchesRegularExpression(
            '/>\s*'.preg_quote($label, '/').'\s*<\/a>/',
            $response->getContent(),
            "Le lien de menu « {$label} » devrait être visible."
        );
    }

    private function assertNoMenuLink($response, string $label): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/>\s*'.preg_quote($label, '/').'\s*<\/a>/',
            $response->getContent(),
            "Le lien de menu « {$label} » ne devrait pas être visible."
        );
    }

    /**
     * @return array<string, array{visible: list<string>, hidden: list<string>}>
     */
    public static function roleMenuProvider(): array
    {
        return [
            'admin voit tout' => [
                'role' => 'admin',
                'visible' => ['Tableau de bord', 'Biens', 'Affectations', 'Maintenance', 'Inventaires', 'Rapports'],
                'hidden' => [],
            ],
            'asset_manager sans rapports' => [
                'role' => 'asset_manager',
                'visible' => ['Tableau de bord', 'Biens', 'Affectations', 'Maintenance', 'Inventaires'],
                'hidden' => ['Rapports'],
            ],
            'service_head sans rapports' => [
                'role' => 'service_head',
                'visible' => ['Tableau de bord', 'Biens', 'Affectations', 'Maintenance', 'Inventaires'],
                'hidden' => ['Rapports'],
            ],
            'accountant limité' => [
                'role' => 'accountant',
                'visible' => ['Tableau de bord', 'Biens', 'Rapports'],
                'hidden' => ['Affectations', 'Maintenance', 'Inventaires'],
            ],
            'auditor sans affectations ni maintenance' => [
                'role' => 'auditor',
                'visible' => ['Tableau de bord', 'Biens', 'Inventaires', 'Rapports'],
                'hidden' => ['Affectations', 'Maintenance'],
            ],
        ];
    }

    #[DataProvider('roleMenuProvider')]
    public function test_dashboard_renders_for_each_role_with_scoped_menu(string $role, array $visible, array $hidden): void
    {
        $this->fakeGateway($role);

        $response = $this->withSession($this->sessionFor($role))->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Valeur nette comptable');
        $response->assertSee('INV-2026-0002');

        foreach ($visible as $label) {
            $this->assertMenuLink($response, $label);
        }
        foreach ($hidden as $label) {
            $this->assertNoMenuLink($response, $label);
        }
    }
}
