<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Support\Jwt\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.asset.url' => 'http://asset-service.test']);

        // L'asset-service est simulé : il répond toujours pour l'asset 1.
        Http::fake([
            'asset-service.test/api/assets/1' => Http::response([
                'data' => [
                    'id' => 1,
                    'code' => 'AST-2026-0001',
                    'designation' => 'Ordinateur portable',
                    'status' => 'assigned',
                ],
            ]),
            'asset-service.test/api/assets/*/status' => Http::response([
                'data' => ['id' => 1, 'status' => 'in_maintenance'],
            ], 200),
            'asset-service.test/*' => Http::response(['data' => null], 404),
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'asset_id' => 1,
            'description' => 'Écran cassé, ne s\'allume plus',
            'priority' => 'high',
        ], $overrides);
    }

    // ------------------------------------------------ accès

    public function test_index_requires_a_token(): void
    {
        $this->getJson('/api/requests')->assertUnauthorized();
    }

    public function test_any_authenticated_role_can_create_a_request(): void
    {
        $this->postJson('/api/requests', $this->payload(), $this->headers('service_head', 7))
            ->assertCreated()
            ->assertJsonPath('data.reference', fn (string $ref) => str_starts_with($ref, 'MNT-'));

        $this->assertDatabaseHas('maintenance_requests', [
            'asset_id' => 1,
            'status' => 'requested',
            'requested_by' => 7,
        ]);
    }

    public function test_unknown_asset_returns_404(): void
    {
        $this->postJson('/api/requests', $this->payload(['asset_id' => 999]), $this->headers())
            ->assertNotFound();
    }

    // ------------------------------------------------ workflow

    public function test_full_workflow_updates_status_and_calls_asset_service(): void
    {
        $id = $this->postJson('/api/requests', $this->payload(), $this->headers())
            ->assertCreated()->json('data.id');

        $this->postJson("/api/requests/{$id}/schedule", [
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'technician_name' => 'Tech X',
        ], $this->headers())->assertOk()->assertJsonPath('data.status', 'scheduled');

        $this->postJson("/api/requests/{$id}/start", [], $this->headers())
            ->assertOk()->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('maintenance_requests', [
            'id' => $id,
            'asset_previous_status' => 'assigned',
        ]);

        // L'asset-service doit avoir reçu les changements de statut
        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/assets/1/status')
            && $r['status'] === 'in_maintenance');

        $this->postJson("/api/requests/{$id}/interventions", [
            'description' => 'Remplacement de la dalle',
            'cost' => 150000,
            'performed_at' => now()->toDateString(),
        ], $this->headers())->assertCreated();

        $completed = $this->postJson("/api/requests/{$id}/complete", [], $this->headers())
            ->assertOk()->assertJsonPath('data.status', 'completed')
            ->json('data');
        $this->assertEquals(150000.0, (float) $completed['total_cost']);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/assets/1/status')
            && $r['status'] === 'assigned');
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $id = MaintenanceRequest::factory()->create()->id;

        $this->postJson("/api/requests/{$id}/complete", [], $this->headers())
            ->assertStatus(422);
    }

    public function test_intervention_rejected_on_requested_status(): void
    {
        $id = MaintenanceRequest::factory()->create()->id;

        $this->postJson("/api/requests/{$id}/interventions", [
            'description' => 'Trop tôt',
            'performed_at' => now()->toDateString(),
        ], $this->headers())->assertStatus(422);
    }

    public function test_asset_service_outage_returns_503(): void
    {
        $id = $this->postJson('/api/requests', $this->payload(), $this->headers())
            ->assertCreated()->json('data.id');

        $this->postJson("/api/requests/{$id}/schedule", [
            'scheduled_date' => now()->addDay()->toDateString(),
        ], $this->headers())->assertOk();

        // L'asset-service tombe en panne à partir de maintenant
        Http::fake(['*' => fn () => throw new ConnectionException('timeout')]);

        $this->postJson("/api/requests/{$id}/start", [], $this->headers())
            ->assertStatus(503);

        // La demande reste planifiée (rollback)
        $this->assertDatabaseHas('maintenance_requests', ['id' => $id, 'status' => 'scheduled']);
    }

    // ------------------------------------------------ droits

    public function test_only_managers_can_schedule(): void
    {
        $id = MaintenanceRequest::factory()->create()->id;

        $this->postJson("/api/requests/{$id}/schedule", [
            'scheduled_date' => now()->addDay()->toDateString(),
        ], $this->headers('service_head'))->assertForbidden();
    }

    public function test_non_manager_only_sees_own_requests(): void
    {
        MaintenanceRequest::factory()->create(['requested_by' => 1]);
        MaintenanceRequest::factory()->create(['requested_by' => 99]);

        $response = $this->getJson('/api/requests', $this->headers('service_head', 99))->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame(99, $response->json('data.0.requested_by.id'));
    }

    // ------------------------------------------------ synthèse

    public function test_asset_maintenance_summary(): void
    {
        MaintenanceRequest::factory()->completed()->create(['asset_id' => 5]);
        MaintenanceRequest::factory()->create(['asset_id' => 5]);

        $this->getJson('/api/assets/5/summary', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.requests_count', 2)
            ->assertJsonPath('data.completed_count', 1);
    }
}
