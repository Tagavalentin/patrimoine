<?php

declare(strict_types=1);

namespace App\Livewire\Assignments;

use App\Support\GatewayClient;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Nouvelle affectation — le bien doit être au statut « acquired ».
 * POST /api/assets/assets/{id}/assign via la gateway.
 */
class Create extends Component
{
    public ?int $assetId = null;

    public ?int $departmentId = null;

    public string $responsibleName = '';

    public string $responsibleEmail = '';

    public string $startDate = '';

    public string $reason = '';

    /** @var array<int, array<string, mixed>> */
    public array $assets = [];

    /** @var array<int, array<string, mixed>> */
    public array $departments = [];

    public ?string $error = null;

    public function mount(GatewayClient $gateway): void
    {
        try {
            $assets = $gateway->get('/api/assets/assets', ['per_page' => 100, 'status' => 'acquired', 'sort' => 'code']);
            $this->assets = $assets['data'] ?? [];
            $this->departments = $gateway->get('/api/assets/departments', ['per_page' => 100])['data'] ?? [];
        } catch (\Throwable $e) {
            $this->error = 'Impossible de charger les référentiels.';
            Log::warning('Assignments\\Create : référentiels indisponibles', ['error' => $e->getMessage()]);
        }
    }

    public function save(): void
    {
        $this->validate([
            'assetId' => ['required', 'integer', 'min:1'],
            'departmentId' => ['required', 'integer', 'min:1'],
            'responsibleName' => ['nullable', 'string', 'max:255'],
            'responsibleEmail' => ['nullable', 'email', 'max:255'],
            'startDate' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'assetId.required' => 'Le bien à affecter est obligatoire.',
            'departmentId.required' => 'Le service destinataire est obligatoire.',
        ]);

        $payload = ['department_id' => $this->departmentId];
        if ($this->responsibleName !== '') {
            $payload['responsible_name'] = $this->responsibleName;
        }
        if ($this->responsibleEmail !== '') {
            $payload['responsible_email'] = $this->responsibleEmail;
        }
        if ($this->startDate !== '') {
            $payload['start_date'] = $this->startDate;
        }
        if ($this->reason !== '') {
            $payload['reason'] = $this->reason;
        }

        try {
            app(GatewayClient::class)->post("/api/assets/assets/{$this->assetId}/assign", $payload);
            $this->redirect(route('assignments.index').'?notice=created');
        } catch (HttpException $e) {
            $this->error = "L'affectation a été refusée ({$e->getStatusCode()}).";
            Log::warning('Assignments\\Create : refusé', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Service des biens indisponible.';
            Log::warning('Assignments\\Create : erreur', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.assignments.create')->extends('layouts.app');
    }
}
