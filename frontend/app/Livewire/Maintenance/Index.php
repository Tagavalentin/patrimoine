<?php

declare(strict_types=1);

namespace App\Livewire\Maintenance;

use App\Support\GatewayClient;
use App\Support\PriorityPresenter;
use App\Support\StatusPresenter;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Liste des demandes de maintenance — GET /api/maintenance/requests.
 */
class Index extends Component
{
    public string $status = '';

    public string $priority = '';

    public int $page = 1;

    /** @var array<int, array<string, mixed>> */
    public array $requests = [];

    public int $total = 0;

    public int $lastPage = 1;

    public ?string $error = null;

    public function mount(GatewayClient $gateway): void
    {
        $this->load($gateway);
    }

    public function updatedStatus(): void
    {
        $this->reload();
    }

    public function updatedPriority(): void
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

        if ($this->status !== '') {
            $params['status'] = $this->status;
        }
        if ($this->priority !== '') {
            $params['priority'] = $this->priority;
        }

        try {
            $response = $gateway->get('/api/maintenance/requests', $params);
            $this->requests = $response['data'] ?? [];
            $this->total = (int) ($response['meta']['total'] ?? 0);
            $this->lastPage = max(1, (int) ($response['meta']['last_page'] ?? 1));
            $this->error = null;
        } catch (HttpException $e) {
            $this->error = 'Service de maintenance indisponible.';
            Log::warning('Maintenance\\Index : indisponible', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Impossible de charger les demandes.';
            Log::warning('Maintenance\\Index : erreur', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.maintenance.index', [
            'statuses' => [
                'requested' => 'Demandée',
                'scheduled' => 'Planifiée',
                'in_progress' => 'En cours',
                'completed' => 'Terminée',
                'cancelled' => 'Annulée',
            ],
            'priorities' => [
                'low' => 'Basse',
                'normal' => 'Normale',
                'high' => 'Haute',
                'urgent' => 'Urgente',
            ],
            'statusPresenter' => StatusPresenter::class,
            'priorityPresenter' => PriorityPresenter::class,
        ])->extends('layouts.app');
    }
}
