<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Support\Jwt\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferentialsTest extends TestCase
{
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return app(JwtService::class)->issue([
            'sub' => 1,
            'email' => 'test@patrimoine.local',
            'role' => $role,
        ]);
    }

    private function headers(string $role = 'admin'): array
    {
        return ['Authorization' => 'Bearer '.$this->token($role)];
    }

    // ------------------------------------------------ authentification

    public function test_endpoints_require_a_token(): void
    {
        $this->getJson('/api/categories')->assertUnauthorized();
        $this->getJson('/api/locations')->assertUnauthorized();
        $this->getJson('/api/departments')->assertUnauthorized();
    }

    // ------------------------------------------------ catégories

    public function test_admin_can_create_a_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Fournitures',
            'code' => 'FOU',
            'description' => 'Fournitures de bureau',
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.code', 'FOU')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_non_admin_cannot_create_a_category(): void
    {
        $this->postJson('/api/categories', [
            'name' => 'Test', 'code' => 'TST',
        ], $this->headers('asset_manager'))->assertForbidden();
    }

    public function test_any_authenticated_role_can_list_categories(): void
    {
        Category::factory()->count(3)->create();

        $this->getJson('/api/categories', $this->headers('auditor'))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_category_code_must_be_unique(): void
    {
        Category::factory()->create(['code' => 'DUP']);

        $this->postJson('/api/categories', [
            'name' => 'Doublon', 'code' => 'DUP',
        ], $this->headers())->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_category_can_be_soft_deleted(): void
    {
        $category = Category::factory()->create();

        $this->deleteJson("/api/categories/{$category->id}", [], $this->headers())
            ->assertNoContent();

        $this->assertSoftDeleted($category);
        // La ligne existe toujours en base (soft delete), mais est exclue du listing
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->getJson('/api/categories', $this->headers())->assertJsonCount(0, 'data');
    }

    public function test_categories_can_be_searched_and_filtered(): void
    {
        Category::factory()->create(['name' => 'Informatique', 'code' => 'INF', 'is_active' => true]);
        Category::factory()->inactive()->create(['name' => 'Véhicule', 'code' => 'VEH']);

        $this->getJson('/api/categories?search=info', $this->headers())
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'INF');

        $this->getJson('/api/categories?is_active=0', $this->headers())
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'VEH');
    }

    // ------------------------------------------------ localisations

    public function test_admin_can_create_a_location(): void
    {
        $this->postJson('/api/locations', [
            'name' => 'Bâtiment C',
            'code' => 'HQ-C',
            'type' => 'bâtiment',
            'city' => 'Dakar',
        ], $this->headers())->assertCreated()
            ->assertJsonPath('data.code', 'HQ-C');
    }

    public function test_locations_can_be_filtered_by_type_and_city(): void
    {
        Location::factory()->create(['type' => 'site', 'city' => 'Dakar']);
        Location::factory()->create(['type' => 'salle', 'city' => 'Thiès']);

        $this->getJson('/api/locations?type=site', $this->headers())
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/locations?city=Thiès', $this->headers())
            ->assertJsonCount(1, 'data');
    }

    // ------------------------------------------------ départements

    public function test_department_hierarchy_is_exposed(): void
    {
        $ministry = Department::factory()->create(['name' => 'Ministère Test', 'code' => 'MIN']);
        $service = Department::factory()->childOf($ministry)->create(['name' => 'Service Test']);

        $this->getJson("/api/departments/{$ministry->id}", $this->headers())
            ->assertOk()
            ->assertJsonPath('data.children.0.id', $service->id);

        $this->getJson("/api/departments/{$service->id}", $this->headers())
            ->assertOk()
            ->assertJsonPath('data.parent.code', 'MIN');
    }

    public function test_department_cannot_be_its_own_parent(): void
    {
        $department = Department::factory()->create();

        $this->putJson("/api/departments/{$department->id}", [
            'parent_id' => $department->id,
        ], $this->headers())->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_non_admin_cannot_delete_a_department(): void
    {
        $department = Department::factory()->create();

        $this->deleteJson("/api/departments/{$department->id}", [], $this->headers('service_head'))
            ->assertForbidden();
    }
}
