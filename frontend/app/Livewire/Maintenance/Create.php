<?php

declare(strict_types=1);

namespace App\Livewire\Maintenance;

use App\Support\GatewayClient;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Création d'une demande de maintenance.
 * Le bien est choisi dans une liste déroulante alimentée par
 * l'asset-service via la gateway.
 */
class Create extends Component
{
    public ?int $assetId = null;

    public string $description = '';

    public string $priority = 'normal';

    /** @var array<int, array<string, mixed>> */
    public array $assets = [];

    public ?string $error = null;

    public function mount(GatewayClient $gateway): void
    {
        try {
            $response = $gateway->get('/api/assets/assets', ['per_page' => 100, 'sort' => 'code']);
            $this->assets = $response['data'] ?? [];
        } catch (\Throwable $e) {
            $this->error = 'Impossible de charger la liste des biens.';
            Log::warning('Maintenance\\Create : biens indisponibles', ['error' => $e->getMessage()]);
        }
    }

    public function save(): void
    {
        $this->validate([
            'assetId' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ], [
            'assetId.required' => 'Le bien concerné est obligatoire.',
            'description.min' => 'Décrivez le problème en au moins 10 caractères.',
        ]);

        try {
            $response = app(GatewayClient::class)->post('/api/maintenance/requests', [
                'asset_id' => $this->assetId,
                'description' => $this->description,
                'priority' => $this->priority,
            ]);

            $id = $response['data']['id'] ?? null;
            $this->redirect($id ? route('maintenance.show', $id) : route('maintenance.index'));
        } catch (HttpException $e) {
            $this->error = "La demande a été refusée ({$e->getStatusCode()}).";
            Log::warning('Maintenance\\Create : refusé', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Service de maintenance indisponible.';
            Log::warning('Maintenance\\Create : erreur', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.maintenance.create', [
            'priorities' => [
                'low' => 'Basse',
                'normal' => 'Normale',
                'high' => 'Haute',
                'urgent' => 'Urgente',
            ],
        ])->extends('layouts.app');
    }
}
