<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Maintenance\Create;
use App\Livewire\Maintenance\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MaintenanceScreensTest extends TestCase
{
    use RefreshDatabase;

    public function authSession(array $role = ['role' => 'admin', 'role_name' => 'Administrateur']): array
    {
        return ['jwt_token' => 'jwt-test-token', 'jwt_user' => $role + ['id' => 1, 'name' => 'Admin', 'email' => 'a@x.sn']];
    }

    public function test_maintenance_index_lists_requests(): void
    {
        Http::fake([
            '*/api/maintenance/requests*' => Http::response([
                'data' => [[
                    'id' => 3,
                    'reference' => 'MNT-2026-0003',
                    'asset' => ['id' => 5, 'code' => 'AST-2026-0005', 'designation' => 'Portable Dell', 'status' => 'in_maintenance'],
                    'priority' => 'high',
                    'status' => 'in_progress',
                    'requested_by' => ['id' => 1, 'name' => 'Admin', 'email' => 'a@x.sn'],
                    'created_at' => '2026-11-01T10:00:00+00:00',
                ]],
                'meta' => ['total' => 1, 'current_page' => 1, 'last_page' => 1],
            ], 200),
        ]);

        $this->withSession($this->authSession())
            ->get('/maintenance')
            ->assertOk()
            ->assertSee('MNT-2026-0003')
            ->assertSee('Portable Dell')
            ->assertSee('En cours')
            ->assertSee('Haute');
    }

    public function test_maintenance_show_displays_detail_and_actions_for_manager(): void
    {
        Http::fake([
            '*/api/maintenance/requests/3' => Http::response([
                'data' => [
                    'id' => 3,
                    'reference' => 'MNT-2026-0003',
                    'asset' => ['id' => 5, 'code' => 'AST-2026-0005', 'designation' => 'Portable Dell', 'status' => 'in_maintenance'],
                    'description' => 'Écran tactile ne répond plus',
                    'priority' => 'high',
                    'status' => 'in_progress',
                    'requested_by' => ['id' => 1, 'name' => 'Admin', 'email' => 'a@x.sn'],
                    'interventions' => [[
                        'id' => 1,
                        'technician_name' => 'Tech X',
                        'description' => 'Dalle tactile remplacée',
                        'cost' => 150000,
                        'performed_at' => '2026-11-05',
                    ]],
                    'total_cost' => 150000,
                    'created_at' => '2026-11-01T10:00:00+00:00',
                ],
            ], 200),
        ]);

        $this->withSession($this->authSession())
            ->get('/maintenance/3')
            ->assertOk()
            ->assertSee('MNT-2026-0003')
            ->assertSee('Écran tactile ne répond plus')
            ->assertSee('Dalle tactile remplacée')
            ->assertSee('Marquer comme terminée');
    }

    public function test_maintenance_show_hides_actions_for_auditor(): void
    {
        Http::fake([
            '*/api/maintenance/requests/3' => Http::response([
                'data' => [
                    'id' => 3,
                    'reference' => 'MNT-2026-0003',
                    'asset' => ['id' => 5, 'code' => 'AST', 'designation' => 'PC', 'status' => 'acquired'],
                    'description' => 'Problème',
                    'priority' => 'normal',
                    'status' => 'requested',
                    'requested_by' => ['id' => 1, 'name' => 'X', 'email' => 'x@x.sn'],
                    'interventions' => [],
                ],
            ], 200),
        ]);

        $this->withSession($this->authSession(['role' => 'auditor', 'role_name' => 'Auditeur']))
            ->get('/maintenance/3')
            ->assertOk()
            ->assertSee('Consultation seule')
            ->assertDontSee('Planifier l\'intervention');
    }

    public function test_schedule_action_calls_gateway(): void
    {
        Http::fake([
            '*/api/maintenance/requests/3' => Http::response([
                'data' => [
                    'id' => 3, 'reference' => 'MNT-2026-0003',
                    'asset' => ['id' => 5, 'code' => 'AST', 'designation' => 'PC', 'status' => 'acquired'],
                    'description' => 'Problème', 'priority' => 'normal', 'status' => 'requested',
                    'requested_by' => ['id' => 1, 'name' => 'X', 'email' => 'x@x.sn'], 'interventions' => [],
                ],
            ], 200),
            '*/api/maintenance/requests/3/schedule' => Http::response(['data' => ['status' => 'scheduled']], 200),
        ]);

        $this->withSession($this->authSession());

        Livewire::test(Show::class, ['id' => '3'])
            ->set('scheduledDate', now()->addWeek()->toDateString())
            ->set('technicianName', 'Tech X')
            ->call('schedule')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/schedule')
            && $request['scheduled_date'] !== null);
    }

    public function test_create_validates_and_submits(): void
    {
        Http::fake([
            '*/api/assets/assets*' => Http::response([
                'data' => [['id' => 5, 'code' => 'AST-2026-0005', 'designation' => 'Portable Dell']],
                'meta' => ['total' => 1, 'last_page' => 1],
            ], 200),
            '*/api/maintenance/requests' => Http::response(['data' => ['id' => 9]], 200),
        ]);

        Livewire::test(Create::class)
            ->set('assetId', 5)
            ->set('description', 'Écran cassé suite à une chute')
            ->set('priority', 'high')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/maintenance/requests')
            && $request['asset_id'] === 5
            && $request['priority'] === 'high');
    }

    public function test_maintenance_pages_require_authentication(): void
    {
        $this->get('/maintenance')->assertRedirect(route('login'));
        $this->get('/maintenance/nouvelle')->assertRedirect(route('login'));
        $this->get('/maintenance/3')->assertRedirect(route('login'));
    }
}
