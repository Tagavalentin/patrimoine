<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Support\GatewayClient;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Création d'une campagne d'inventaire — périmètre optionnel
 * par département ou localisation (libellé envoyé avec l'id).
 */
class Create extends Component
{
    public string $name = '';

    public ?int $departmentId = null;

    public ?int $locationId = null;

    /** @var array<int, array<string, mixed>> */
    public array $departments = [];

    /** @var array<int, array<string, mixed>> */
    public array $locations = [];

    public ?string $error = null;

    public function mount(GatewayClient $gateway): void
    {
        try {
            $this->departments = $gateway->get('/api/assets/departments', ['per_page' => 100])['data'] ?? [];
            $this->locations = $gateway->get('/api/assets/locations', ['per_page' => 100])['data'] ?? [];
        } catch (\Throwable $e) {
            $this->error = 'Impossible de charger les référentiels.';
            Log::warning('Inventory\\Create : référentiels indisponibles', ['error' => $e->getMessage()]);
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'min:5', 'max:255'],
            'departmentId' => ['nullable', 'integer', 'min:1'],
            'locationId' => ['nullable', 'integer', 'min:1'],
        ], [
            'name.required' => 'Le nom de la campagne est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 5 caractères.',
        ]);

        $payload = ['name' => $this->name];

        if ($this->departmentId) {
            $dept = collect($this->departments)->firstWhere('id', $this->departmentId);
            $payload['department_id'] = $this->departmentId;
            $payload['department_name'] = $dept['name'] ?? null;
        }
        if ($this->locationId) {
            $loc = collect($this->locations)->firstWhere('id', $this->locationId);
            $payload['location_id'] = $this->locationId;
            $payload['location_name'] = $loc['name'] ?? null;
        }

        try {
            $response = app(GatewayClient::class)->post('/api/inventory/campaigns', $payload);
            $id = $response['data']['id'] ?? null;
            $this->redirect($id ? route('inventory.show', $id) : route('inventory.index'));
        } catch (HttpException $e) {
            $this->error = "La campagne a été refusée ({$e->getStatusCode()}).";
            Log::warning('Inventory\\Create : refusé', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Service d\'inventaire indisponible.';
            Log::warning('Inventory\\Create : erreur', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.inventory.create')->extends('layouts.app');
    }
}
