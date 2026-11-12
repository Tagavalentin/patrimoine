<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function authSession(array $overrides = []): array
    {
        return ['jwt_token' => 'jwt-test-token', 'jwt_user' => $overrides + [
            'id' => 1, 'name' => 'Admin', 'email' => 'a@x.sn', 'role' => 'admin', 'role_name' => 'Administrateur',
        ]];
    }

    public function test_reports_index_shows_export_cards(): void
    {
        Http::fake([
            '*/api/reports/audit-logs*' => Http::response([
                'data' => [
                    'data' => [[
                        'created_at' => '2026-11-11T09:00:00+00:00',
                        'export_type' => 'assets_csv',
                        'format' => 'csv',
                        'rows_count' => 15,
                        'actor_name' => 'Admin',
                        'actor_email' => 'a@x.sn',
                        'ip_address' => '172.18.0.1',
                    ]],
                    'total' => 1,
                    'last_page' => 1,
                ],
            ], 200),
        ]);

        $this->withSession($this->authSession())
            ->get('/rapports')
            ->assertOk()
            ->assertSee('Rapports et exports')
            ->assertSee('assets_csv')
            ->assertSee('Télécharger le CSV');
    }

    public function test_audit_logs_hidden_for_non_auditor_roles(): void
    {
        Http::fake();

        $this->withSession($this->authSession(['role' => 'accountant', 'role_name' => 'Comptable']))
            ->get('/rapports')
            ->assertOk()
            ->assertSee('Télécharger le CSV')
            ->assertDontSee('Journal des exports');
    }

    public function test_export_proxies_csv_download(): void
    {
        Http::fake([
            '*/api/reports/exports/assets.csv*' => Http::response("Code;Désignation\nAST-1;PC", 200),
        ]);

        $response = $this->withSession($this->authSession())
            ->get('/rapports/export/assets');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertDownload();

        $this->assertStringContainsString('AST-1', $response->getContent());
    }

    public function test_export_rejects_unknown_type(): void
    {
        $this->withSession($this->authSession())
            ->get('/rapports/export/inconnu')
            ->assertNotFound();
    }

    public function test_export_requires_authentication(): void
    {
        $this->get('/rapports')->assertRedirect(route('login'));
        $this->get('/rapports/export/assets')->assertRedirect(route('login'));
    }
}
