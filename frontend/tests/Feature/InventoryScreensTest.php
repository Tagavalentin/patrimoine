<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Inventory\Create;
use App\Livewire\Inventory\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryScreensTest extends TestCase
{
    use RefreshDatabase;

    private function authSession(array $overrides = []): array
    {
        return ['jwt_token' => 'jwt-test-token', 'jwt_user' => $overrides + [
            'id' => 1, 'name' => 'Admin', 'email' => 'a@x.sn', 'role' => 'admin', 'role_name' => 'Administrateur',
        ]];
    }

    private function fakeCampaign(): void
    {
        Http::fake([
            '*/api/inventory/campaigns/7' => Http::response([
                'data' => [
                    'id' => 7, 'reference' => 'INV-2026-0007', 'name' => 'Inventaire annuel',
                    'scope' => ['department' => null, 'location' => null],
                    'status' => 'in_progress',
                    'created_by' => ['id' => 1, 'name' => 'Admin', 'email' => 'a@x.sn'],
                    'created_at' => '2026-11-01T10:00:00+00:00',
                ],
            ], 200),
            '*/api/inventory/campaigns/7/summary*' => Http::response([
                'data' => [
                    'reference' => 'INV-2026-0007', 'lines_total' => 10, 'found' => 6,
                    'missing' => 2, 'damaged' => 1, 'pending' => 1,
                    'discrepancies' => 3, 'discrepancy_rate' => 30.0,
                ],
            ], 200),
            '*/api/inventory/campaigns/7/lines*' => Http::response([
                'data' => [[
                    'id' => 11,
                    'asset' => ['id' => 5, 'code' => 'AST-2026-0005', 'designation' => 'Portable Dell',
                        'status' => 'assigned', 'department_name' => 'DSI', 'location_name' => 'Siège'],
                    'check_status' => 'pending',
                    'is_discrepancy' => false,
                    'notes' => null, 'checked_by_name' => null, 'checked_at' => null,
                ]],
                'meta' => ['total' => 1, 'current_page' => 1, 'last_page' => 1],
            ], 200),
        ]);
    }

    public function test_inventory_index_lists_campaigns(): void
    {
        Http::fake([
            '*/api/inventory/campaigns*' => Http::response([
                'data' => [[
                    'id' => 7, 'reference' => 'INV-2026-0007', 'name' => 'Inventaire annuel',
                    'scope' => ['department' => ['id' => 1, 'name' => 'DSI'], 'location' => null],
                    'status' => 'in_progress',
                    'created_by' => ['id' => 1, 'name' => 'Admin', 'email' => 'a@x.sn'],
                    'created_at' => '2026-11-01T10:00:00+00:00',
                ]],
                'meta' => ['total' => 1, 'current_page' => 1, 'last_page' => 1],
            ], 200),
        ]);

        $this->withSession($this->authSession())
            ->get('/inventaires')
            ->assertOk()
            ->assertSee('INV-2026-0007')
            ->assertSee('Inventaire annuel')
            ->assertSee('En cours');
    }

    public function test_inventory_show_displays_summary_and_lines(): void
    {
        $this->fakeCampaign();

        $this->withSession($this->authSession())
            ->get('/inventaires/7')
            ->assertOk()
            ->assertSee('Inventaire annuel')
            ->assertSee('Portable Dell')
            ->assertSee('30 %')
            ->assertSee('Clôturer')
            ->assertSee('Contrôler');
    }

    public function test_inventory_show_hides_check_for_service_head(): void
    {
        $this->fakeCampaign();

        $this->withSession($this->authSession(['role' => 'service_head', 'role_name' => 'Chef de service']))
            ->get('/inventaires/7')
            ->assertOk()
            ->assertSee('Portable Dell')
            ->assertDontSee('Contrôler');
    }

    public function test_check_line_calls_gateway_with_patch(): void
    {
        $this->withSession($this->authSession(['role' => 'auditor', 'role_name' => 'Auditeur']));
        $this->fakeCampaign();
        Http::fake([
            '*/api/inventory/campaigns/7/lines/11' => Http::response(['data' => ['check_status' => 'found']], 200),
        ]);

        Livewire::test(Show::class, ['id' => '7'])
            ->call('beginCheck', 11)
            ->set('lineCheckStatus', 'found')
            ->set('lineNotes', 'Bien retrouvé au bureau')
            ->call('saveCheck')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request) => $request->method() === 'PATCH'
            && str_contains($request->url(), '/lines/11')
            && $request['check_status'] === 'found');
    }

    public function test_create_campaign_submits_payload(): void
    {
        Http::fake([
            '*/api/assets/departments*' => Http::response(['data' => [['id' => 1, 'name' => 'DSI']]], 200),
            '*/api/assets/locations*' => Http::response(['data' => []], 200),
            '*/api/inventory/campaigns' => Http::response(['data' => ['id' => 8]], 200),
        ]);

        $this->withSession($this->authSession());

        Livewire::test(Create::class)
            ->set('name', 'Inventaire annuel 2026')
            ->set('departmentId', 1)
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/inventory/campaigns')
            && $request['name'] === 'Inventaire annuel 2026'
            && $request['department_id'] === 1
            && $request['department_name'] === 'DSI');
    }

    public function test_inventory_pages_require_authentication(): void
    {
        $this->get('/inventaires')->assertRedirect(route('login'));
        $this->get('/inventaires/7')->assertRedirect(route('login'));
    }
}
