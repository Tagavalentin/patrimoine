<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InventoryCampaign;
use App\Models\InventoryLine;
use App\Support\Jwt\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.asset.url' => 'http://asset-service.test']);

        Http::fake([
            'asset-service.test/api/assets*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'code' => 'AST-2026-0001',
                        'designation' => 'Ordinateur portable',
                        'status' => 'assigned',
                        'department' => ['id' => 3, 'name' => 'Informatique'],
                        'location' => ['id' => 1, 'name' => 'Siège Dakar'],
                    ],
                    [
                        'id' => 2,
                        'code' => 'AST-2026-0002',
                        'designation' => 'Imprimante laser',
                        'status' => 'acquired',
                        'department' => ['id' => 3, 'name' => 'Informatique'],
                        'location' => ['id' => 1, 'name' => 'Siège Dakar'],
                    ],
                ],
                'meta' => ['last_page' => 1],
            ]),
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

    private function createCampaign(array $overrides = [], string $role = 'admin'): int
    {
        return $this->postJson('/api/campaigns', array_merge([
            'name' => 'Inventaire annuel 2026',
        ], $overrides), $this->headers($role))
            ->assertCreated()->json('data.id');
    }

    // ------------------------------------------------ accès

    public function test_index_requires_a_token(): void
    {
        $this->getJson('/api/campaigns')->assertUnauthorized();
    }

    public function test_campaign_creation_snapshots_assets(): void
    {
        $id = $this->createCampaign(['department_id' => 3, 'department_name' => 'Informatique']);

        $this->assertDatabaseHas('inventory_campaigns', [
            'id' => $id,
            'status' => 'draft',
        ]);
        $this->assertStringStartsWith('INV-', InventoryCampaign::findOrFail($id)->reference);
        $this->assertDatabaseCount('inventory_lines', 2);
        $this->assertDatabaseHas('inventory_lines', [
            'inventory_campaign_id' => $id,
            'asset_id' => 1,
            'asset_code' => 'AST-2026-0001',
            'check_status' => 'pending',
        ]);

        // Le filtre département est propagé à l'asset-service
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/assets')
            && $r['department_id'] == 3);
    }

    public function test_asset_service_outage_returns_503(): void
    {
        Http::fake(['*' => fn () => throw new ConnectionException('down')]);

        $this->postJson('/api/campaigns', ['name' => 'Inventaire test'], $this->headers())
            ->assertStatus(503);
    }

    // ------------------------------------------------ workflow

    public function test_full_inventory_workflow_with_discrepancies(): void
    {
        $id = $this->createCampaign();

        // draft -> in_progress
        $this->postJson("/api/campaigns/{$id}/start", [], $this->headers())
            ->assertOk()->assertJsonPath('data.status', 'in_progress');

        // Contrôle des lignes
        $lines = InventoryLine::where('inventory_campaign_id', $id)->orderBy('asset_id')->get();

        $this->patchJson("/api/campaigns/{$id}/lines/{$lines[0]->id}", [
            'check_status' => 'found',
        ], $this->headers())->assertOk()->assertJsonPath('data.check_status', 'found');

        $this->patchJson("/api/campaigns/{$id}/lines/{$lines[1]->id}", [
            'check_status' => 'missing',
            'notes' => 'Absente du service Informatique',
        ], $this->headers())->assertOk()->assertJsonPath('data.is_discrepancy', true);

        // Synthèse avant clôture
        $summary = $this->getJson("/api/campaigns/{$id}/summary", $this->headers())->assertOk()->json('data');
        $this->assertSame(2, $summary['lines_total']);
        $this->assertSame(1, $summary['missing']);
        $this->assertSame(1, $summary['discrepancies']);
        $this->assertEquals(50.0, $summary['discrepancy_rate']);

        // Clôture
        $this->postJson("/api/campaigns/{$id}/complete", [], $this->headers())
            ->assertOk()->assertJsonPath('data.status', 'completed');

        // Plus aucune modification possible
        $this->patchJson("/api/campaigns/{$id}/lines/{$lines[0]->id}", [
            'check_status' => 'damaged',
        ], $this->headers())->assertStatus(422);
    }

    public function test_unchecked_lines_become_missing_on_completion(): void
    {
        $id = $this->createCampaign();
        $this->postJson("/api/campaigns/{$id}/start", [], $this->headers())->assertOk();
        $this->postJson("/api/campaigns/{$id}/complete", [], $this->headers())->assertOk();

        $this->assertSame(0, InventoryLine::where('inventory_campaign_id', $id)
            ->where('check_status', 'pending')->count());
        $this->assertSame(2, InventoryLine::where('inventory_campaign_id', $id)
            ->where('check_status', 'missing')->count());
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $id = InventoryCampaign::factory()->create()->id;

        $this->postJson("/api/campaigns/{$id}/complete", [], $this->headers())
            ->assertStatus(422);
    }

    // ------------------------------------------------ droits

    public function test_only_managers_can_create_or_start(): void
    {
        $this->postJson('/api/campaigns', ['name' => 'Test droits'], $this->headers('service_head'))
            ->assertForbidden();

        $id = InventoryCampaign::factory()->inProgress()->create()->id;
        $this->postJson("/api/campaigns/{$id}/complete", [], $this->headers('auditor'))
            ->assertForbidden();
    }

    public function test_auditor_can_check_lines(): void
    {
        $campaign = InventoryCampaign::factory()->inProgress()->create();
        $line = InventoryLine::factory()->create(['inventory_campaign_id' => $campaign->id]);

        $this->patchJson("/api/campaigns/{$campaign->id}/lines/{$line->id}", [
            'check_status' => 'damaged',
            'notes' => 'Coque fissurée',
        ], $this->headers('auditor'))->assertOk()
            ->assertJsonPath('data.check_status', 'damaged');
    }

    public function test_only_admin_can_delete_draft(): void
    {
        $id = InventoryCampaign::factory()->create()->id;

        $this->deleteJson("/api/campaigns/{$id}", [], $this->headers('asset_manager'))
            ->assertForbidden();
        $this->deleteJson("/api/campaigns/{$id}", [], $this->headers('admin'))
            ->assertNoContent();
    }

    public function test_lines_filter_by_discrepancy_status(): void
    {
        $campaign = InventoryCampaign::factory()->inProgress()->create();
        InventoryLine::factory()->found()->create(['inventory_campaign_id' => $campaign->id]);
        InventoryLine::factory()->missing()->create(['inventory_campaign_id' => $campaign->id]);

        $response = $this->getJson("/api/campaigns/{$campaign->id}/lines?check_status=missing", $this->headers())
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('missing', $response->json('data.0.check_status'));
    }
}
