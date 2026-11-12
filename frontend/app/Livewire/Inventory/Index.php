<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Support\GatewayClient;
use App\Support\StatusPresenter;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Liste des campagnes d'inventaire — GET /api/inventory/campaigns.
 */
class Index extends Component
{
    public string $status = '';

    public string $search = '';

    public int $page = 1;

    /** @var array<int, array<string, mixed>> */
    public array $campaigns = [];

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

    public function updatedSearch(): void
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
        if ($this->search !== '') {
            $params['search'] = $this->search;
        }

        try {
            $response = $gateway->get('/api/inventory/campaigns', $params);
            $this->campaigns = $response['data'] ?? [];
            $this->total = (int) ($response['meta']['total'] ?? 0);
            $this->lastPage = max(1, (int) ($response['meta']['last_page'] ?? 1));
            $this->error = null;
        } catch (HttpException $e) {
            $this->error = 'Service d\'inventaire indisponible.';
            Log::warning('Inventory\\Index : indisponible', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Impossible de charger les campagnes.';
            Log::warning('Inventory\\Index : erreur', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.inventory.index', [
            'statuses' => [
                'draft' => 'Brouillon',
                'in_progress' => 'En cours',
                'completed' => 'Terminée',
            ],
            'presenter' => StatusPresenter::class,
        ])->extends('layouts.app');
    }
}
