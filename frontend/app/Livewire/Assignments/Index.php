<?php

declare(strict_types=1);

namespace App\Livewire\Assignments;

use App\Support\GatewayClient;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Liste des affectations — GET /api/assets/assignments.
 * Actions transfert/restitution en ligne pour les gestionnaires.
 */
class Index extends Component
{
    public string $activeFilter = '';

    public ?int $departmentId = null;

    public int $page = 1;

    /** @var array<int, array<string, mixed>> */
    public array $assignments = [];

    /** @var array<int, array<string, mixed>> */
    public array $departments = [];

    public int $total = 0;

    public int $lastPage = 1;

    public ?string $error = null;

    public ?string $notice = null;

    // Panneau d'action en ligne
    public ?int $actionAssignmentId = null;

    public string $actionType = '';

    public ?int $targetDepartmentId = null;

    public string $responsibleName = '';

    public string $responsibleEmail = '';

    public string $reason = '';

    public function mount(GatewayClient $gateway): void
    {
        try {
            $this->departments = $gateway->get('/api/assets/departments', ['per_page' => 100])['data'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('Assignments\\Index : départements indisponibles', ['error' => $e->getMessage()]);
        }

        $this->load($gateway);
    }

    public function updatedActiveFilter(): void
    {
        $this->reload();
    }

    public function updatedDepartmentId(): void
    {
        $this->reload();
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, min($page, $this->lastPage));
        $this->load(app(GatewayClient::class));
    }

    private function reload(): void
    {
        $this->page = 1;
        $this->load(app(GatewayClient::class));
    }

    private function load(GatewayClient $gateway): void
    {
        $params = ['page' => $this->page, 'per_page' => 15];

        if ($this->activeFilter === 'active') {
            $params['active'] = 1;
        } elseif ($this->activeFilter === 'closed') {
            $params['active'] = 0;
        }
        if ($this->departmentId) {
            $params['department_id'] = $this->departmentId;
        }

        try {
            $response = $gateway->get('/api/assets/assignments', $params);
            $this->assignments = $response['data'] ?? [];
            $this->total = (int) ($response['meta']['total'] ?? 0);
            $this->lastPage = max(1, (int) ($response['meta']['last_page'] ?? 1));
            $this->error = null;
        } catch (HttpException $e) {
            $this->error = 'Service des biens indisponible.';
            Log::warning('Assignments\\Index : indisponible', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Impossible de charger les affectations.';
            Log::warning('Assignments\\Index : erreur', ['error' => $e->getMessage()]);
        }
    }

    private function canManage(): bool
    {
        return in_array(session('jwt_user.role'), ['admin', 'asset_manager'], true);
    }

    public function beginAction(int $assignmentId, string $type, ?int $currentDepartmentId = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->actionAssignmentId = $assignmentId;
        $this->actionType = $type;
        $this->targetDepartmentId = $currentDepartmentId;
        $this->responsibleName = '';
        $this->responsibleEmail = '';
        $this->reason = '';
    }

    public function cancelAction(): void
    {
        $this->actionAssignmentId = null;
        $this->actionType = '';
    }

    public function submitAction(): void
    {
        if ($this->actionAssignmentId === null || $this->actionType === '') {
            return;
        }

        $assignment = collect($this->assignments)->firstWhere('id', $this->actionAssignmentId);
        $assetId = $assignment['asset']['id'] ?? null;

        if (! $assetId) {
            $this->error = 'Bien introuvable pour cette affectation.';

            return;
        }

        $gateway = app(GatewayClient::class);
        $endpoint = match ($this->actionType) {
            'transfer' => 'transfer',
            'return' => 'return',
            default => null,
        };

        if ($endpoint === null) {
            return;
        }

        $payload = [];
        if ($this->actionType === 'transfer') {
            $this->validate([
                'targetDepartmentId' => ['required', 'integer', 'min:1'],
                'responsibleName' => ['nullable', 'string', 'max:255'],
                'reason' => ['nullable', 'string', 'max:1000'],
            ], ['targetDepartmentId.required' => 'Le service de destination est obligatoire.']);

            $payload = [
                'department_id' => $this->targetDepartmentId,
                'reason' => $this->reason !== '' ? $this->reason : null,
            ];
            if ($this->responsibleName !== '') {
                $payload['responsible_name'] = $this->responsibleName;
            }
        } else {
            $this->validate(['reason' => ['nullable', 'string', 'max:1000']]);
            $payload = ['reason' => $this->reason !== '' ? $this->reason : null];
        }

        try {
            $gateway->post("/api/assets/assets/{$assetId}/{$endpoint}", $payload);
            $this->notice = $this->actionType === 'transfer' ? 'Transfert effectué.' : 'Bien restitué au parc.';
            $this->error = null;
            $this->cancelAction();
            $this->load($gateway);
        } catch (HttpException $e) {
            $this->error = "Action refusée ({$e->getStatusCode()}).";
            Log::warning('Assignments\\Index : action refusée', ['type' => $this->actionType, 'status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Service des biens indisponible.';
            Log::warning('Assignments\\Index : action impossible', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.assignments.index', [
            'canManage' => $this->canManage(),
            'departmentsForFilter' => $this->departments,
        ])->extends('layouts.app');
    }
}
