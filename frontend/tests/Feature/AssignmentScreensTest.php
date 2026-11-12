<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Assignments\Create;
use App\Livewire\Assignments\Index;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AssignmentScreensTest extends TestCase
{
    use RefreshDatabase;

    private function authSession(array $overrides = []): array
    {
        return ['jwt_token' => 'jwt-test-token', 'jwt_user' => $overrides + [
            'id' => 1, 'name' => 'Admin', 'email' => 'a@x.sn', 'role' => 'admin', 'role_name' => 'Administrateur',
        ]];
    }

    private function fakeList(): void
    {
        Http::fake([
            '*/api/assets/departments*' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'DSI'],
                    ['id' => 2, 'name' => 'Finances'],
                ],
                'meta' => ['total' => 2, 'last_page' => 1],
            ], 200),
            '*/api/assets/assignments*' => Http::response([
                'data' => [[
                    'id' => 10,
                    'asset' => ['id' => 5, 'code' => 'AST-2026-0005', 'designation' => 'Portable Dell', 'status' => 'assigned'],
                    'department' => ['id' => 1, 'name' => 'DSI'],
                    'responsible' => ['user_id' => 2, 'name' => 'M. Ndiaye', 'email' => 'n@x.sn'],
                    'start_date' => '2024-02-01',
                    'end_date' => null,
                    'is_active' => true,
                    'reason' => 'Affectation initiale',
                ]],
                'meta' => ['total' => 1, 'current_page' => 1, 'last_page' => 1],
            ], 200),
        ]);
    }

    public function test_assignments_index_lists_active_assignments(): void
    {
        $this->fakeList();

        $this->withSession($this->authSession())
            ->get('/affectations')
            ->assertOk()
            ->assertSee('Portable Dell')
            ->assertSee('DSI')
            ->assertSee('M. Ndiaye')
            ->assertSee('Active')
            ->assertSee('Transférer')
            ->assertSee('Restituer');
    }

    public function test_assignments_index_hides_actions_for_auditor(): void
    {
        $this->fakeList();

        $this->withSession($this->authSession(['role' => 'auditor', 'role_name' => 'Auditeur']))
            ->get('/affectations')
            ->assertOk()
            ->assertSee('Portable Dell')
            ->assertDontSee('Transférer');
    }

    public function test_transfer_action_calls_gateway(): void
    {
        $this->withSession($this->authSession());
        $this->fakeList();
        Http::fake([
            '*/api/assets/assets/5/transfer' => Http::response(['data' => ['id' => 11]], 200),
        ]);

        Livewire::test(Index::class)
            ->call('beginAction', 10, 'transfer', 1)
            ->set('targetDepartmentId', 2)
            ->set('responsibleName', 'Mme Fall')
            ->set('reason', 'Mutation')
            ->call('submitAction')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/assets/5/transfer')
            && $request['department_id'] === 2
            && $request['responsible_name'] === 'Mme Fall');
    }

    public function test_return_action_calls_gateway(): void
    {
        $this->withSession($this->authSession());
        $this->fakeList();
        Http::fake([
            '*/api/assets/assets/5/return' => Http::response(['data' => ['id' => 10]], 200),
        ]);

        Livewire::test(Index::class)
            ->call('beginAction', 10, 'return')
            ->set('reason', 'Fin de mission')
            ->call('submitAction')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/assets/5/return'));
    }

    public function test_create_assignment_submits_payload(): void
    {
        Http::fake([
            '*/api/assets/assets*' => Http::response([
                'data' => [['id' => 5, 'code' => 'AST-2026-0005', 'designation' => 'Portable Dell']],
                'meta' => ['total' => 1, 'last_page' => 1],
            ], 200),
            '*/api/assets/departments*' => Http::response([
                'data' => [['id' => 2, 'name' => 'Finances']],
                'meta' => ['total' => 1, 'last_page' => 1],
            ], 200),
            '*/api/assets/assets/5/assign' => Http::response(['data' => ['id' => 12]], 201),
        ]);

        $this->withSession($this->authSession());

        Livewire::test(Create::class)
            ->set('assetId', 5)
            ->set('departmentId', 2)
            ->set('responsibleName', 'M. Sarr')
            ->set('reason', 'Affectation service financier')
            ->call('save')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/assets/5/assign')
            && $request['department_id'] === 2
            && $request['responsible_name'] === 'M. Sarr');
    }

    public function test_assignment_pages_require_authentication(): void
    {
        $this->get('/affectations')->assertRedirect(route('login'));
        $this->get('/affectations/nouvelle')->assertRedirect(route('login'));
    }
}
