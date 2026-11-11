<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\Disposal;
use App\Support\Jwt\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisposalTest extends TestCase
{
    use RefreshDatabase;

    private function headers(string $role = 'admin', int $sub = 1): array
    {
        $token = app(JwtService::class)->issue([
            'sub' => $sub,
            'email' => 'test@patrimoine.local',
            'role' => $role,
        ]);

        return ['Authorization' => 'Bearer '.$token];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'disposal_type' => 'sale',
            'reason' => 'Fin de vie technique, remplacement prévu',
            'estimated_value' => 250000,
        ], $overrides);
    }

    private function requestDisposal(Asset $asset, string $role = 'asset_manager'): int
    {
        return $this->postJson("/api/assets/{$asset->id}/disposals", $this->validPayload(), $this->headers($role))
            ->assertCreated()->json('data.id');
    }

    // ------------------------------------------------ création

    public function test_disposals_index_requires_a_token(): void
    {
        $this->getJson('/api/disposals')->assertUnauthorized();
    }

    public function test_asset_manager_can_request_disposal(): void
    {
        $asset = Asset::factory()->create();

        $id = $this->requestDisposal($asset);

        $this->assertDatabaseHas('disposals', [
            'id' => $id,
            'asset_id' => $asset->id,
            'status' => 'requested',
        ]);
        $this->assertStringStartsWith('DSP-', Disposal::findOrFail($id)->reference);
    }

    public function test_disposed_asset_cannot_be_requested_again(): void
    {
        $asset = Asset::factory()->disposed()->create();

        $this->postJson("/api/assets/{$asset->id}/disposals", $this->validPayload(), $this->headers())
            ->assertStatus(422);
    }

    public function test_assigned_asset_must_be_returned_first(): void
    {
        $asset = Asset::factory()->assigned()->create();
        Assignment::create([
            'asset_id' => $asset->id,
            'department_id' => Department::factory()->create()->id,
            'responsible_name' => 'M. X',
            'start_date' => now()->subMonth()->toDateString(),
            'reason' => 'Affectation initiale',
            'created_by' => 1,
        ]);

        $this->postJson("/api/assets/{$asset->id}/disposals", $this->validPayload(), $this->headers())
            ->assertStatus(422);
    }

    public function test_only_one_active_disposal_per_asset(): void
    {
        $asset = Asset::factory()->create();
        $this->requestDisposal($asset);

        $this->postJson("/api/assets/{$asset->id}/disposals", $this->validPayload(), $this->headers())
            ->assertStatus(422);
    }

    public function test_service_head_cannot_request_disposal(): void
    {
        $asset = Asset::factory()->create();

        $this->postJson("/api/assets/{$asset->id}/disposals", $this->validPayload(), $this->headers('service_head'))
            ->assertForbidden();
    }

    // ------------------------------------------------ workflow

    public function test_full_disposal_workflow(): void
    {
        $asset = Asset::factory()->assigned()->create();
        $id = $this->requestDisposal($asset, 'asset_manager');

        // Approbation : le bien passe « réformé »
        $this->postJson("/api/disposals/{$id}/approve", [], $this->headers('admin'))
            ->assertOk()->assertJsonPath('data.status', 'approved');

        $this->assertSame(AssetStatus::Decommissioned, Asset::findOrFail($asset->id)->status);

        // Historique de statuts tracé
        $this->assertDatabaseHas('asset_status_histories', [
            'asset_id' => $asset->id,
            'to_status' => 'decommissioned',
        ]);

        // Réalisation : le bien passe « disposed » (terminal)
        $completed = $this->postJson("/api/disposals/{$id}/complete", [
            'disposal_date' => now()->toDateString(),
            'sale_value' => 300000,
        ], $this->headers('admin'))
            ->assertOk()->assertJsonPath('data.status', 'completed')
            ->json('data');
        $this->assertEquals(300000.0, (float) $completed['sale_value']);

        $this->assertSame(AssetStatus::Disposed, Asset::findOrFail($asset->id)->status);

        // Un bien sorti ne peut plus changer de statut
        $this->postJson("/api/assets/{$asset->id}/status", ['status' => 'acquired'], $this->headers('admin'))
            ->assertStatus(422);
    }

    public function test_rejection_requires_reason_and_keeps_asset_unchanged(): void
    {
        $asset = Asset::factory()->create();
        $id = $this->requestDisposal($asset);

        $this->postJson("/api/disposals/{$id}/reject", [], $this->headers('admin'))
            ->assertStatus(422);

        $this->postJson("/api/disposals/{$id}/reject", ['decision_reason' => 'Motif insuffisant'], $this->headers('admin'))
            ->assertOk()->assertJsonPath('data.status', 'rejected');

        $this->assertSame(AssetStatus::Acquired, Asset::findOrFail($asset->id)->status);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $asset = Asset::factory()->create();
        $id = $this->requestDisposal($asset);

        // requested -> completed directement : interdit
        $this->postJson("/api/disposals/{$id}/complete", [], $this->headers('admin'))
            ->assertStatus(422);
    }

    public function test_only_admin_can_decide(): void
    {
        $asset = Asset::factory()->create();
        $id = $this->requestDisposal($asset);

        $this->postJson("/api/disposals/{$id}/approve", [], $this->headers('asset_manager'))
            ->assertForbidden();
    }

    // ------------------------------------------------ consultation

    public function test_disposals_list_and_history(): void
    {
        $asset = Asset::factory()->create();
        $id = $this->requestDisposal($asset);

        $this->getJson('/api/disposals?status=requested', $this->headers('auditor'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/assets/{$asset->id}/disposals", $this->headers('auditor'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->getJson("/api/disposals/{$id}", $this->headers())
            ->assertOk()
            ->assertJsonStructure(['data' => ['reference', 'asset' => ['code', 'designation']]]);
    }

    public function test_completed_disposal_allows_new_request(): void
    {
        $asset = Asset::factory()->create();
        $id = $this->requestDisposal($asset);
        $this->postJson("/api/disposals/{$id}/approve", [], $this->headers())->assertOk();
        $this->postJson("/api/disposals/{$id}/complete", [], $this->headers())->assertOk();

        // Le bien est disposed → nouvelle demande refusée
        $this->postJson("/api/assets/{$asset->id}/disposals", $this->validPayload(), $this->headers())
            ->assertStatus(422);
    }
}
