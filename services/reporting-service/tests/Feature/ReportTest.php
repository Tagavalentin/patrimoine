<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Jwt\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.internal.asset' => 'http://asset.test',
            'services.internal.maintenance' => 'http://maintenance.test',
            'services.internal.inventory' => 'http://inventory.test',
        ]);

        Http::fake([
            // asset-service : comptages par statut
            'asset.test/api/assets*' => function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
                // Export CSV : per_page=100 → fixture complète
                if ((int) ($q['per_page'] ?? 0) >= 100) {
                    return Http::response([
                        'data' => [
                            ['id' => 1, 'code' => 'AST-2026-0001', 'designation' => 'PC Portable',
                                'status' => 'assigned', 'category' => ['name' => 'Informatique'],
                                'acquisition_value' => 850000, 'acquisition_date' => '2025-01-15',
                                'useful_life_years' => 5],
                            ['id' => 2, 'code' => 'AST-2026-0002', 'designation' => 'Serveur',
                                'status' => 'acquired', 'category' => null,
                                'acquisition_value' => 3000000, 'acquisition_date' => '2024-06-01',
                                'useful_life_years' => 8],
                        ],
                        'meta' => ['total' => 2, 'last_page' => 1],
                    ]);
                }
                $status = $q['status'] ?? null;
                $totals = [
                    'acquired' => 10, 'assigned' => 20, 'in_maintenance' => 2,
                    'decommissioned' => 1, 'disposed' => 3,
                ];
                $count = $status !== null ? ($totals[$status] ?? 0) : array_sum($totals);

                return Http::response([
                    'data' => $status === null ? [] : [['id' => 1]],
                    'meta' => ['total' => $count, 'last_page' => 1],
                ]);
            },
            'asset.test/api/depreciation/summary' => Http::response([
                'data' => [
                    'net_book_value' => 9500000.0,
                    'accumulated_depreciation' => 1500000.0,
                ],
            ]),
            'asset.test/api/assets/export*' => Http::response([
                'data' => [
                    ['id' => 1, 'code' => 'AST-2026-0001', 'designation' => 'PC Portable',
                        'status' => 'assigned', 'category' => ['name' => 'Informatique'],
                        'acquisition_value' => 850000, 'acquisition_date' => '2025-01-15',
                        'useful_life_years' => 5],
                    ['id' => 2, 'code' => 'AST-2026-0002', 'designation' => 'Serveur',
                        'status' => 'acquired', 'category' => null,
                        'acquisition_value' => 3000000, 'acquisition_date' => '2024-06-01',
                        'useful_life_years' => 8],
                ],
                'meta' => ['total' => 2, 'last_page' => 1],
            ]),
            // maintenance-service
            'maintenance.test/api/requests*' => function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
                $status = $q['status'] ?? null;
                $totals = ['requested' => 4, 'scheduled' => 3, 'in_progress' => 1, 'completed' => 12];
                $count = $status !== null ? ($totals[$status] ?? 0) : array_sum($totals);

                return Http::response([
                    'data' => $status === null ? [
                        ['id' => 1, 'reference' => 'MNT-2026-0001', 'description' => 'Panne écran',
                            'priority' => 'high', 'status' => 'requested', 'total_cost' => 50000,
                            'asset' => ['code' => 'AST-1', 'designation' => 'PC'],
                            'requested_by' => ['name' => 'M. X'], 'created_at' => '2026-11-01'],
                    ] : [['id' => 1]],
                    'meta' => ['total' => $count, 'last_page' => 1],
                ]);
            },
            // inventory-service
            'inventory.test/api/campaigns*' => function ($request) {
                if (str_contains($request->url(), '/summary')) {
                    return Http::response(['data' => ['lines_total' => 50, 'discrepancies' => 3, 'discrepancy_rate' => 6.0]]);
                }

                return Http::response([
                    'data' => [['id' => 7, 'reference' => 'INV-2026-0001', 'name' => 'Inventaire Q4',
                        'status' => 'in_progress', 'lines_count' => 50,
                        'started_at' => '2026-11-01', 'completed_at' => null,
                        'created_by' => ['name' => 'Admin']]],
                    'meta' => ['total' => 1, 'last_page' => 1],
                ]);
            },
        ]);
    }

    private function headers(string $role = 'admin', int $sub = 1): array
    {
        $token = app(JwtService::class)->issue([
            'sub' => $sub,
            'email' => 'test@patrimoine.local',
            'role' => $role,
        ]);

        return ['Authorization' => 'Bearer '.$token];
    }

    // ------------------------------------------------ accès

    public function test_dashboard_requires_a_token(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_dashboard_aggregates_all_services(): void
    {
        $data = $this->getJson('/api/dashboard', $this->headers('accountant'))
            ->assertOk()->json('data');

        $this->assertSame(36, $data['assets']['total']);
        $this->assertSame(20, $data['assets']['by_status']['assigned']);
        $this->assertEquals(9500000.0, (float) $data['assets']['net_book_value']);
        $this->assertSame(20, $data['maintenance']['total']);
        $this->assertSame(8, $data['maintenance']['open']);
        $this->assertSame('INV-2026-0001', $data['inventory']['latest_campaign']);
        $this->assertSame(3, $data['inventory']['latest_summary']['discrepancies']);
    }

    public function test_service_head_cannot_view_dashboard(): void
    {
        $this->getJson('/api/dashboard', $this->headers('service_head'))->assertForbidden();
    }

    // ------------------------------------------------ exports

    public function test_assets_csv_export_is_logged(): void
    {
        $response = $this->get('/api/exports/assets.csv?status=assigned', $this->headers('accountant'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('biens', (string) $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('AST-2026-0001', $content);
        $this->assertStringContainsString('PC Portable', $content);

        // Le filtre est propagé et l'export journalisé
        Http::assertSent(fn ($r) => str_contains($r->url(), 'asset.test/api/assets')
            && str_contains($r->url(), 'status=assigned'));

        $this->assertDatabaseHas('report_audit_logs', [
            'report_type' => 'assets_csv',
            'format' => 'csv',
            'rows_count' => 2,
            'generated_by' => 1,
        ]);
    }

    public function test_maintenance_csv_export(): void
    {
        $response = $this->get('/api/exports/maintenance.csv', $this->headers());
        $response->assertOk();
        $this->assertStringContainsString('MNT-2026-0001', $response->streamedContent());
    }

    public function test_service_head_cannot_export(): void
    {
        $this->get('/api/exports/assets.csv', $this->headers('service_head'))->assertForbidden();
        $this->get('/api/exports/assets.csv', $this->headers('auditor'))->assertForbidden();
    }

    public function test_internal_service_outage_returns_503(): void
    {
        Http::fake(['*' => fn () => throw new ConnectionException('down')]);

        $this->getJson('/api/dashboard', $this->headers())->assertStatus(503);
    }

    // ------------------------------------------------ audit

    public function test_audit_logs_are_recorded_and_readable(): void
    {
        $this->get('/api/exports/assets.csv', $this->headers('accountant'))->assertOk();
        $this->get('/api/exports/maintenance.csv', $this->headers('accountant'))->assertOk();

        $body = $this->getJson('/api/audit-logs', $this->headers('auditor'))->assertOk()->json();
        $logs = $body['data']['data'] ?? [];

        $this->assertCount(2, $logs);
        $this->assertSame('assets_csv', $logs[0]['report_type']);

        // Filtre par type
        $filtered = $this->getJson('/api/audit-logs?report_type=maintenance_csv', $this->headers('auditor'))
            ->assertOk()->json();
        $this->assertCount(1, $filtered['data']['data'] ?? []);
    }

    public function test_only_admin_and_auditor_read_audit_logs(): void
    {
        $this->getJson('/api/audit-logs', $this->headers('accountant'))->assertForbidden();
        $this->getJson('/api/audit-logs', $this->headers('admin'))->assertOk();
    }
}
