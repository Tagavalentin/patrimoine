<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Assets\Index;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ScreensTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAuth(): void
    {
        Http::fake([
            '*/api/auth/me' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'Admin',
                    'email' => 'admin@patrimoine.local',
                    'role' => ['id' => 1, 'name' => 'Administrateur', 'slug' => 'admin'],
                ],
            ], 200),
        ]);
    }

    public function test_dashboard_displays_consolidated_data(): void
    {
        $this->fakeAuth();
        Http::fake([
            '*/api/reports/dashboard' => Http::response([
                'data' => [
                    'generated_at' => '2026-11-11T10:00:00+00:00',
                    'assets' => [
                        'total' => 15,
                        'by_status' => ['acquired' => 5, 'assigned' => 7, 'in_maintenance' => 1, 'decommissioned' => 1, 'disposed' => 1],
                        'net_book_value' => 12500000,
                        'accumulated_depreciation' => 3000000,
                    ],
                    'maintenance' => [
                        'total' => 4,
                        'by_status' => ['requested' => 2, 'scheduled' => 1, 'in_progress' => 1, 'completed' => 0],
                        'open' => 4,
                    ],
                    'inventory' => [
                        'latest_campaign' => 'INV-2026-0001',
                        'latest_status' => 'completed',
                        'latest_summary' => [
                            'lines_total' => 10, 'found' => 8, 'missing' => 1, 'damaged' => 1,
                            'pending' => 0, 'discrepancies' => 2, 'discrepancy_rate' => 20.0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->withSession(['jwt_token' => 'jwt-test-token'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('15')
            ->assertSee('INV-2026-0001')
            ->assertSee('12 500 000');
    }

    public function test_assets_index_lists_assets_with_filters(): void
    {
        Http::fake([
            '*/api/assets/categories*' => Http::response([
                'data' => [['id' => 1, 'name' => 'Informatique', 'code' => 'INFO']],
                'meta' => ['total' => 1, 'current_page' => 1, 'last_page' => 1],
            ], 200),
            '*/api/assets/assets*' => Http::response([
                'data' => [[
                    'id' => 5,
                    'code' => 'AST-2026-0005',
                    'designation' => 'Portable Dell Latitude',
                    'category' => ['id' => 1, 'name' => 'Informatique'],
                    'location' => ['id' => 2, 'name' => 'Siège Dakar'],
                    'status' => 'assigned',
                    'acquisition_value' => 850000,
                ]],
                'meta' => ['total' => 1, 'current_page' => 1, 'last_page' => 1],
            ], 200),
        ]);

        $this->withSession(['jwt_token' => 'jwt-test-token'])
            ->get('/biens')
            ->assertOk()
            ->assertSee('AST-2026-0005')
            ->assertSee('Portable Dell Latitude')
            ->assertSee('Affecté');
    }

    public function test_asset_show_displays_detail_valuation_and_history(): void
    {
        Http::fake([
            '*/api/assets/assets/5' => Http::response([
                'data' => [
                    'id' => 5,
                    'code' => 'AST-2026-0005',
                    'designation' => 'Portable Dell Latitude',
                    'category' => ['id' => 1, 'name' => 'Informatique'],
                    'location' => ['id' => 2, 'name' => 'Siège Dakar'],
                    'department' => ['id' => 3, 'name' => 'DSI'],
                    'acquisition_date' => '2024-01-15',
                    'acquisition_value' => 850000,
                    'useful_life_years' => 3,
                    'status' => 'assigned',
                    'description' => 'Portable de travail',
                ],
            ], 200),
            '*/api/assets/assets/5/depreciation*' => Http::response([
                'data' => [
                    'net_book_value' => 566666.67,
                    'accumulated_depreciation' => 283333.33,
                    'monthly_depreciation' => 23611.11,
                    'elapsed_months' => 12,
                    'remaining_months' => 24,
                    'fully_depreciated' => false,
                ],
            ], 200),
            '*/api/assets/assets/5/assignments*' => Http::response([
                'data' => [[
                    'start_date' => '2024-02-01',
                    'end_date' => null,
                    'department' => ['id' => 3, 'name' => 'DSI'],
                    'responsible' => ['user_id' => 2, 'name' => 'M. Ndiaye', 'email' => 'n@x.sn'],
                    'is_active' => true,
                    'reason' => 'Affectation initiale',
                ]],
            ], 200),
        ]);

        $this->withSession(['jwt_token' => 'jwt-test-token'])
            ->get('/biens/5')
            ->assertOk()
            ->assertSee('Portable Dell Latitude')
            ->assertSee('566 667')
            ->assertSee('M. Ndiaye')
            ->assertSee('Active');
    }

    public function test_assets_index_propagates_search_filter(): void
    {
        Http::fake([
            '*/api/assets/categories*' => Http::response(['data' => [], 'meta' => ['total' => 0]], 200),
            '*/api/assets/assets*' => Http::response(['data' => [], 'meta' => ['total' => 0, 'last_page' => 1]], 200),
        ]);

        $this->withSession(['jwt_token' => 'jwt-test-token'])
            ->get('/biens')
            ->assertOk();

        // La recherche Livewire est testée via le composant
        Livewire::test(Index::class)
            ->set('search', 'portable');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'search=portable'));
    }

    public function test_pages_require_authentication(): void
    {
        $this->get('/biens')->assertRedirect(route('login'));
        $this->get('/biens/5')->assertRedirect(route('login'));
    }
}
