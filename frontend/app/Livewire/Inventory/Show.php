<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Support\GatewayClient;
use App\Support\StatusPresenter;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Fiche campagne d'inventaire : synthèse, lignes à contrôler,
 * actions start/complete (gestionnaires) et check des lignes
 * (gestionnaires + auditeur).
 */
class Show extends Component
{
    public int $campaignId;

    /** @var array<string, mixed>|null */
    public ?array $campaign = null;

    /** @var array<string, mixed>|null */
    public ?array $summary = null;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public string $checkStatusFilter = '';

    public int $page = 1;

    public int $lastPage = 1;

    public int $total = 0;

    public ?string $error = null;

    public ?string $notice = null;

    // État du formulaire de contrôle de ligne
    public ?int $checkingLineId = null;

    public string $lineCheckStatus = 'found';

    public string $lineNotes = '';

    public function mount(string $id, GatewayClient $gateway): void
    {
        $this->campaignId = (int) $id;
        $this->loadCampaign($gateway);
        $this->loadLines($gateway);
    }

    private function loadCampaign(GatewayClient $gateway): void
    {
        try {
            $this->campaign = $gateway->get("/api/inventory/campaigns/{$this->campaignId}")['data'] ?? null;
            $this->error = $this->campaign === null ? 'Campagne introuvable.' : null;
        } catch (\Throwable $e) {
            $this->error = 'Campagne introuvable ou service indisponible.';
            Log::warning('Inventory\\Show : campagne', ['id' => $this->campaignId, 'error' => $e->getMessage()]);

            return;
        }

        try {
            $this->summary = $gateway->get("/api/inventory/campaigns/{$this->campaignId}/summary")['data'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('Inventory\\Show : summary indisponible', ['id' => $this->campaignId]);
        }
    }

    private function loadLines(GatewayClient $gateway): void
    {
        $params = ['page' => $this->page, 'per_page' => 20];
        if ($this->checkStatusFilter !== '') {
            $params['check_status'] = $this->checkStatusFilter;
        }

        try {
            $response = $gateway->get("/api/inventory/campaigns/{$this->campaignId}/lines", $params);
            $this->lines = $response['data'] ?? [];
            $this->total = (int) ($response['meta']['total'] ?? 0);
            $this->lastPage = max(1, (int) ($response['meta']['last_page'] ?? 1));
        } catch (\Throwable $e) {
            Log::warning('Inventory\\Show : lignes indisponibles', ['id' => $this->campaignId, 'error' => $e->getMessage()]);
        }
    }

    private function canManage(): bool
    {
        return in_array(session('jwt_user.role'), ['admin', 'asset_manager'], true);
    }

    private function canCheck(): bool
    {
        return in_array(session('jwt_user.role'), ['admin', 'asset_manager', 'auditor'], true);
    }

    public function updatedCheckStatusFilter(): void
    {
        $this->page = 1;
        $this->loadLines(app(GatewayClient::class));
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, min($page, $this->lastPage));
        $this->loadLines(app(GatewayClient::class));
    }

    public function start(): void
    {
        $this->performAction('start');
    }

    public function complete(): void
    {
        $this->performAction('complete');
    }

    public function beginCheck(int $lineId): void
    {
        $this->checkingLineId = $lineId;
        $this->lineCheckStatus = 'found';
        $this->lineNotes = '';
    }

    public function cancelCheck(): void
    {
        $this->checkingLineId = null;
    }

    public function saveCheck(): void
    {
        if ($this->checkingLineId === null || ! $this->canCheck()) {
            return;
        }

        try {
            app(GatewayClient::class)->patch(
                "/api/inventory/campaigns/{$this->campaignId}/lines/{$this->checkingLineId}",
                [
                    'check_status' => $this->lineCheckStatus,
                    'notes' => $this->lineNotes !== '' ? $this->lineNotes : null,
                ],
            );

            $this->notice = 'Ligne mise à jour.';
            $this->error = null;
            $this->checkingLineId = null;
            $this->loadLines(app(GatewayClient::class));
            $this->loadCampaign(app(GatewayClient::class));
        } catch (HttpException $e) {
            $this->error = "Contrôle refusé ({$e->getStatusCode()}).";
            Log::warning('Inventory\\Show : check refusé', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Service d\'inventaire indisponible.';
            Log::warning('Inventory\\Show : check impossible', ['error' => $e->getMessage()]);
        }
    }

    private function performAction(string $action): void
    {
        if (! $this->canManage()) {
            $this->error = 'Votre rôle ne permet pas cette action.';

            return;
        }

        try {
            app(GatewayClient::class)->post("/api/inventory/campaigns/{$this->campaignId}/{$action}");
            $this->notice = 'Action effectuée avec succès.';
            $this->error = null;
            $this->loadCampaign(app(GatewayClient::class));
            $this->loadLines(app(GatewayClient::class));
        } catch (HttpException $e) {
            $this->error = "L'action a été refusée ({$e->getStatusCode()}).";
            Log::warning('Inventory\\Show : action refusée', ['action' => $action, 'status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Service d\'inventaire indisponible.';
            Log::warning('Inventory\\Show : action impossible', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.inventory.show', [
            'canManage' => $this->canManage(),
            'canCheck' => $this->canCheck(),
            'status' => $this->campaign['status'] ?? null,
            'presenter' => StatusPresenter::class,
        ])->extends('layouts.app');
    }
}
