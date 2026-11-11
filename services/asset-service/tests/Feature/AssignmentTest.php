<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Assignment;
use App\Models\Department;
use App\Support\Jwt\JwtService;
use Database\Seeders\ReferentialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Department $deptA;

    private Department $deptB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReferentialSeeder::class);
        $this->deptA = Department::where('code', 'MEF')->firstOrFail();
        $this->deptB = Department::where('code', 'MINT')->firstOrFail();
    }

    private function headers(string $role = 'asset_manager'): array
    {
        $token = app(JwtService::class)->issue([
            'sub' => 7,
            'email' => 'test@patrimoine.local',
            'role' => $role,
        ]);

        return ['Authorization' => 'Bearer '.$token];
    }

    private function assignPayload(array $overrides = []): array
    {
        return array_merge([
            'department_id' => $this->deptA->id,
            'responsible_name' => 'M. Responsable',
            'responsible_email' => 'resp@patrimoine.local',
            'reason' => 'Affectation initiale',
        ], $overrides);
    }

    // ------------------------------------------------ affectation

    public function test_asset_manager_can_assign_an_acquired_asset(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);

        $response = $this->postJson("/api/assets/{$asset->id}/assign", $this->assignPayload(), $this->headers())
            ->assertCreated()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.department.id', $this->deptA->id);

        $this->assertSame('assigned', $asset->fresh()->status->value);
        $this->assertSame(7, $response->json('data.created_by'));
    }

    public function test_cannot_assign_an_already_assigned_asset(): void
    {
        $asset = Asset::factory()->assigned()->create();
        Assignment::create([
            'asset_id' => $asset->id,
            'department_id' => $this->deptA->id,
            'start_date' => now()->toDateString(),
            'end_date' => null,
        ]);

        $this->postJson("/api/assets/{$asset->id}/assign", $this->assignPayload(), $this->headers())
            ->assertUnprocessable();
    }

    public function test_cannot_assign_a_non_acquired_asset(): void
    {
        $asset = Asset::factory()->inMaintenance()->create();

        $this->postJson("/api/assets/{$asset->id}/assign", $this->assignPayload(), $this->headers())
            ->assertUnprocessable();
    }

    public function test_assign_to_inactive_department_is_rejected(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);
        $inactive = Department::factory()->create(['is_active' => false]);

        $this->postJson("/api/assets/{$asset->id}/assign", $this->assignPayload(['department_id' => $inactive->id]), $this->headers())
            ->assertUnprocessable();
    }

    public function test_service_head_cannot_assign(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);

        $this->postJson("/api/assets/{$asset->id}/assign", $this->assignPayload(), $this->headers('service_head'))
            ->assertForbidden();
    }

    // ------------------------------------------------ transfert

    public function test_transfer_closes_current_and_creates_new_assignment(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);
        $this->postJson("/api/assets/{$asset->id}/assign", $this->assignPayload(), $this->headers())->assertCreated();

        $response = $this->postJson("/api/assets/{$asset->id}/transfer", $this->assignPayload([
            'department_id' => $this->deptB->id,
            'reason' => 'Réorganisation',
        ]), $this->headers())->assertCreated()
            ->assertJsonPath('data.department.id', $this->deptB->id)
            ->assertJsonPath('data.is_active', true);

        // Ancienne affectation clôturée, nouvelle active
        $this->assertSame(2, Assignment::where('asset_id', $asset->id)->count());
        $this->assertSame(1, Assignment::where('asset_id', $asset->id)->whereNull('end_date')->count());
        $this->assertNotNull(Assignment::where('asset_id', $asset->id)->where('department_id', $this->deptA->id)->value('end_date'));

        // Le statut reste 'assigned'
        $this->assertSame('assigned', $asset->fresh()->status->value);
    }

    public function test_transfer_requires_an_active_assignment(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);

        $this->postJson("/api/assets/{$asset->id}/transfer", $this->assignPayload(), $this->headers())
            ->assertUnprocessable();
    }

    // ------------------------------------------------ restitution

    public function test_return_closes_assignment_and_frees_the_asset(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);
        $this->postJson("/api/assets/{$asset->id}/assign", $this->assignPayload(), $this->headers())->assertCreated();

        $this->postJson("/api/assets/{$asset->id}/return", [
            'reason' => 'Fin de mission',
        ], $this->headers())->assertOk();

        $this->assertSame('acquired', $asset->fresh()->status->value);
        $this->assertSame(0, Assignment::where('asset_id', $asset->id)->whereNull('end_date')->count());
    }

    public function test_return_without_active_assignment_is_rejected(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);

        $this->postJson("/api/assets/{$asset->id}/return", [], $this->headers())
            ->assertUnprocessable();
    }

    // ------------------------------------------------ historique / liste

    public function test_history_endpoint_returns_full_assignment_history(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);
        $this->postJson("/api/assets/{$asset->id}/assign", $this->assignPayload(), $this->headers())->assertCreated();
        $this->postJson("/api/assets/{$asset->id}/transfer", $this->assignPayload(['department_id' => $this->deptB->id]), $this->headers())->assertCreated();
        $this->postJson("/api/assets/{$asset->id}/return", [], $this->headers())->assertOk();

        $response = $this->getJson("/api/assets/{$asset->id}/assignments", $this->headers('auditor'))
            ->assertOk();

        // 2 lignes : affectation initiale + transfert (la restitution clôt sans créer de ligne)
        $this->assertCount(2, $response->json('data'));
    }

    public function test_assignments_index_can_filter_active_only(): void
    {
        $assetA = Asset::factory()->create(['status' => 'acquired']);
        $assetB = Asset::factory()->create(['status' => 'acquired']);

        $this->postJson("/api/assets/{$assetA->id}/assign", $this->assignPayload(), $this->headers())->assertCreated();
        $this->postJson("/api/assets/{$assetB->id}/assign", $this->assignPayload(), $this->headers())->assertCreated();
        $this->postJson("/api/assets/{$assetB->id}/return", [], $this->headers())->assertOk();

        $this->getJson('/api/assignments?active=1', $this->headers('service_head'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
