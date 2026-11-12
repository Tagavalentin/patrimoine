<?php

declare(strict_types=1);

namespace App\Livewire\Assets;

use App\Support\GatewayClient;
use App\Support\StatusPresenter;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Liste des biens — consomme GET /api/assets/assets
 * (la gateway retire le préfixe /api/assets).
 */
class Index extends Component
{
    public string $search = '';

    public string $status = '';

    public string $categoryId = '';

    public int $page = 1;

    /** @var array<int, array<string, mixed>> */
    public array $assets = [];

    /** @var array<int, array<string, mixed>> */
    public array $categories = [];

    public int $total = 0;

    public int $lastPage = 1;

    public ?string $error = null;

    public function mount(GatewayClient $gateway): void
    {
        try {
            $categories = $gateway->get('/api/assets/categories', ['per_page' => 100]);
            $this->categories = $categories['data'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('Assets\\Index : catégories indisponibles', ['error' => $e->getMessage()]);
        }

        $this->load($gateway);
    }

    public function updatedSearch(): void
    {
        $this->reload();
    }

    public function updatedStatus(): void
    {
        $this->reload();
    }

    public function updatedCategoryId(): void
    {
        $this->reload();
    }

    private function reload(): void
    {
        $this->page = 1;
        $this->load(app(GatewayClient::class));
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, min($page, $this->lastPage));
        $this->load(app(GatewayClient::class));
    }

    private function load(GatewayClient $gateway): void
    {
        $params = ['page' => $this->page, 'per_page' => 15, 'sort' => 'code'];

        if ($this->search !== '') {
            $params['search'] = $this->search;
        }
        if ($this->status !== '') {
            $params['status'] = $this->status;
        }
        if ($this->categoryId !== '') {
            $params['category_id'] = (int) $this->categoryId;
        }

        try {
            $response = $gateway->get('/api/assets/assets', $params);
            $this->assets = $response['data'] ?? [];
            $this->total = (int) ($response['meta']['total'] ?? 0);
            $this->lastPage = max(1, (int) ($response['meta']['last_page'] ?? 1));
            $this->error = null;
        } catch (HttpException $e) {
            $this->error = 'Impossible de charger les biens (service indisponible).';
            Log::warning('Assets\\Index : asset-service inaccessible', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Impossible de charger les biens.';
            Log::warning('Assets\\Index : erreur', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.assets.index', [
            'statuses' => [
                'acquired' => 'Acquis',
                'assigned' => 'Affecté',
                'in_maintenance' => 'En maintenance',
                'decommissioned' => 'Déclassé',
                'disposed' => 'Cédé',
            ],
            'presenter' => StatusPresenter::class,
        ])->extends('layouts.app');
    }
}
