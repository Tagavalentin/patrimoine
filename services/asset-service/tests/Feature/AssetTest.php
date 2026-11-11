<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetStatusHistory;
use App\Models\Category;
use App\Models\Location;
use App\Support\Jwt\JwtService;
use Database\Seeders\ReferentialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReferentialSeeder::class);
    }

    private function headers(string $role = 'admin'): array
    {
        $token = app(JwtService::class)->issue([
            'sub' => 1,
            'email' => 'test@patrimoine.local',
            'role' => $role,
        ]);

        return ['Authorization' => 'Bearer '.$token];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'designation' => 'Ordinateur portable de test',
            'category_id' => Category::where('code', 'INF')->value('id'),
            'location_id' => Location::where('code', 'HQ-A')->value('id'),
            'acquisition_date' => '2025-01-15',
            'acquisition_value' => 850000,
            'useful_life_years' => 5,
        ], $overrides);
    }

    // ------------------------------------------------ accès

    public function test_assets_index_requires_a_token(): void
    {
        $this->getJson('/api/assets')->assertUnauthorized();
    }

    public function test_any_authenticated_role_can_list_assets(): void
    {
        Asset::factory()->count(2)->create();

        $this->getJson('/api/assets', $this->headers('auditor'))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_service_head_cannot_create_an_asset(): void
    {
        $this->postJson('/api/assets', $this->validPayload(), $this->headers('service_head'))
            ->assertForbidden();
    }

    // ------------------------------------------------ création

    public function test_asset_manager_can_create_an_asset_with_generated_code(): void
    {
        $response = $this->postJson('/api/assets', $this->validPayload(), $this->headers('asset_manager'));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'acquired')
            ->assertJsonPath('data.status_label', 'Acquis');

        $code = $response->json('data.code');
        $this->assertMatchesRegularExpression('/^AST-\d{4}-\d{4}$/', $code);
    }

    public function test_sequential_codes_are_generated(): void
    {
        $first = $this->postJson('/api/assets', $this->validPayload(), $this->headers())->json('data.code');
        $second = $this->postJson('/api/assets', $this->validPayload(['designation' => 'Autre bien']), $this->headers())->json('data.code');

        $this->assertSame(1, ((int) substr($second, -4)) - ((int) substr($first, -4)));
    }

    public function test_creation_is_validated(): void
    {
        $this->postJson('/api/assets', ['designation' => 'Sans référentiels'], $this->headers())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'location_id', 'acquisition_date', 'acquisition_value', 'useful_life_years']);
    }

    public function test_creation_records_initial_status_history(): void
    {
        $assetId = $this->postJson('/api/assets', $this->validPayload(), $this->headers())->json('data.id');

        $this->assertDatabaseHas('asset_status_histories', [
            'asset_id' => $assetId,
            'from_status' => null,
            'to_status' => 'acquired',
            'changed_by' => 1,
        ]);
    }

    // ------------------------------------------------ lecture / filtres

    public function test_assets_can_be_filtered_by_status_and_category(): void
    {
        $category = Category::where('code', 'INF')->first();
        Asset::factory()->create(['status' => 'assigned', 'category_id' => $category->id]);
        Asset::factory()->create(['status' => 'acquired']);
        Asset::factory()->create(['status' => 'acquired']);

        $this->getJson('/api/assets?status=assigned', $this->headers())
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/assets?category_id={$category->id}", $this->headers())
            ->assertJsonCount(1, 'data');
    }

    public function test_assets_can_be_searched_and_sorted(): void
    {
        Asset::factory()->create(['designation' => 'Projecteur laser unique', 'code' => 'AST-2026-0001']);
        Asset::factory()->create(['designation' => 'Simple ordinateur', 'code' => 'AST-2026-0002']);

        $this->getJson('/api/assets?search=projecteur', $this->headers())
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.designation', 'Projecteur laser unique');

        $this->getJson('/api/assets?sort=designation&direction=desc', $this->headers())
            ->assertJsonPath('data.0.designation', 'Simple ordinateur');
    }

    // ------------------------------------------------ modification

    public function test_asset_manager_can_update_an_asset(): void
    {
        $asset = Asset::factory()->create();

        $this->putJson("/api/assets/{$asset->id}", [
            'designation' => 'Nouvelle désignation',
            'acquisition_value' => 999999,
        ], $this->headers('asset_manager'))
            ->assertOk()
            ->assertJsonPath('data.designation', 'Nouvelle désignation')
            ->assertJsonPath('data.acquisition_value', '999999.00');
    }

    public function test_accountant_cannot_update_an_asset(): void
    {
        $asset = Asset::factory()->create();

        $this->putJson("/api/assets/{$asset->id}", ['designation' => 'X'], $this->headers('accountant'))
            ->assertForbidden();
    }

    // ------------------------------------------------ machine à états

    public function test_valid_status_transition_is_applied_and_traced(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);

        $this->postJson("/api/assets/{$asset->id}/status", [
            'status' => 'assigned',
            'reason' => 'Affectation au service informatique',
        ], $this->headers('asset_manager'))
            ->assertOk()
            ->assertJsonPath('data.status', 'assigned');

        $this->assertDatabaseHas('asset_status_histories', [
            'asset_id' => $asset->id,
            'from_status' => 'acquired',
            'to_status' => 'assigned',
            'changed_by' => 1,
        ]);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        // acquired -> decommissioned est autorisé, mais on teste
        // un cas impossible : on force disposed (terminal) puis on tente acquired
        $asset = Asset::factory()->disposed()->create();

        $this->postJson("/api/assets/{$asset->id}/status", ['status' => 'acquired'], $this->headers())
            ->assertUnprocessable();
    }

    public function test_status_must_be_a_known_value(): void
    {
        $asset = Asset::factory()->create();

        $this->postJson("/api/assets/{$asset->id}/status", ['status' => 'inconnu'], $this->headers())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_full_lifecycle_acquired_to_disposed(): void
    {
        $asset = Asset::factory()->create(['status' => 'acquired']);

        foreach (['assigned', 'in_maintenance', 'decommissioned', 'disposed'] as $target) {
            $this->postJson("/api/assets/{$asset->id}/status", ['status' => $target], $this->headers())
                ->assertOk();
        }

        $this->assertSame('disposed', $asset->fresh()->status->value);
        // 4 transitions tracées (le factory ne crée pas d'historique initial)
        $this->assertSame(4, AssetStatusHistory::where('asset_id', $asset->id)->count());
    }

    // ------------------------------------------------ suppression

    public function test_disposed_asset_cannot_be_deleted(): void
    {
        $asset = Asset::factory()->disposed()->create();

        $this->deleteJson("/api/assets/{$asset->id}", [], $this->headers('asset_manager'))
            ->assertUnprocessable();
    }

    public function test_active_asset_can_be_soft_deleted(): void
    {
        $asset = Asset::factory()->create();

        $this->deleteJson("/api/assets/{$asset->id}", [], $this->headers('asset_manager'))
            ->assertNoContent();

        $this->assertSoftDeleted($asset);
    }
}
