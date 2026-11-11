<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use App\Support\Jwt\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepreciationTest extends TestCase
{
    use RefreshDatabase;

    private function headers(string $role = 'admin'): array
    {
        $token = app(JwtService::class)->issue([
            'sub' => 1,
            'email' => 'test@patrimoine.local',
            'role' => $role,
        ]);

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_valuation_requires_a_token(): void
    {
        $asset = Asset::factory()->create();
        $this->getJson("/api/assets/{$asset->id}/depreciation")->assertUnauthorized();
    }

    public function test_linear_valuation_at_acquisition_is_gross_value(): void
    {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->toDateString(),
            'acquisition_value' => 1200000,
            'useful_life_years' => 5,
        ]);

        $data = $this->getJson("/api/assets/{$asset->id}/depreciation", $this->headers())
            ->assertOk()->json('data');

        $this->assertSame(1200000.0, (float) $data['acquisition_value']);
        $this->assertEquals(0.0, (float) $data['accumulated_depreciation']);
        $this->assertEquals(1200000.0, (float) $data['net_book_value']);
    }

    public function test_valuation_after_30_months(): void
    {
        // 1 200 000 F, 5 ans → 20 000 F/mois ; 30 mois écoulés
        $acquisition = now()->subMonths(30)->toDateString();
        $asset = Asset::factory()->create([
            'acquisition_date' => $acquisition,
            'acquisition_value' => 1200000,
            'useful_life_years' => 5,
        ]);

        $data = $this->getJson("/api/assets/{$asset->id}/depreciation", $this->headers())
            ->assertOk()->json('data');

        $this->assertEquals(30, $data['elapsed_months']);
        $this->assertEquals(600000.0, (float) $data['accumulated_depreciation']);
        $this->assertEquals(600000.0, (float) $data['net_book_value']);
    }

    public function test_fully_depreciated_asset_has_zero_net_value(): void
    {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subYears(10)->toDateString(),
            'acquisition_value' => 500000,
            'useful_life_years' => 5,
        ]);

        $data = $this->getJson("/api/assets/{$asset->id}/depreciation", $this->headers())
            ->assertOk()->json('data');

        $this->assertTrue($data['fully_depreciated']);
        $this->assertEquals(0.0, (float) $data['net_book_value']);
        $this->assertEquals(60, $data['elapsed_months']); // borné à la durée de vie
        $this->assertEquals(500000.0, (float) $data['accumulated_depreciation']);
    }

    public function test_schedule_covers_each_year(): void
    {
        $asset = Asset::factory()->create([
            'acquisition_date' => '2024-01-15',
            'acquisition_value' => 1000000,
            'useful_life_years' => 4,
        ]);

        $schedule = $this->getJson("/api/assets/{$asset->id}/depreciation/schedule", $this->headers())
            ->assertOk()->json('data');

        $this->assertCount(4, $schedule);
        $this->assertEquals(250000.0, (float) $schedule[0]['annual_depreciation']);
        $this->assertEquals(250000.0, (float) $schedule[0]['accumulated_depreciation']);
        $this->assertEquals(750000.0, (float) $schedule[0]['net_book_value']);
        $this->assertEquals(0.0, (float) $schedule[3]['net_book_value']);
    }

    public function test_portfolio_summary_excludes_disposed_assets(): void
    {
        Asset::factory()->create([
            'acquisition_date' => now()->subYear()->toDateString(),
            'acquisition_value' => 1200000,
            'useful_life_years' => 5,
        ]);
        Asset::factory()->disposed()->create([
            'acquisition_value' => 999999,
        ]);

        $data = $this->getJson('/api/depreciation/summary', $this->headers())
            ->assertOk()->json('data');

        $this->assertSame(1, $data['assets_count']);
        $this->assertEquals(1200000.0, (float) $data['gross_value']);
        $this->assertArrayHasKey('net_book_value', $data);
    }

    public function test_summary_accepts_historical_date(): void
    {
        Asset::factory()->create([
            'acquisition_date' => '2020-01-01',
            'acquisition_value' => 600000,
            'useful_life_years' => 5,
        ]);

        // À fin 2022 : 35 mois pleins écoulés → 350 000 cumulés
        $data = $this->getJson('/api/depreciation/summary?date=2022-12-31', $this->headers())
            ->assertOk()->json('data');

        $this->assertEquals('2022-12-31', $data['as_of']);
        $this->assertEquals(350000.0, (float) $data['accumulated_depreciation']);
        $this->assertEquals(250000.0, (float) $data['net_book_value']);
    }
}
