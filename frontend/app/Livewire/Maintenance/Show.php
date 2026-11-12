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
 * Fiche demande de maintenance + actions du workflow
 * (planifier, démarrer, terminer, ajouter une intervention).
 * Réservé aux rôles admin / asset_manager — les autres rôles
 * ne voient que la consultation.
 */
class Show extends Component
{
    public int $requestId;

    /** @var array<string, mixed>|null */
    public ?array $request = null;

    public ?string $error = null;

    public ?string $notice = null;

    // --- Formulaires d'action ---
    public string $scheduledDate = '';

    public string $technicianName = '';

    public string $interventionDescription = '';

    public string $interventionCost = '';

    public string $performedAt = '';

    public function mount(string $id, GatewayClient $gateway): void
    {
        $this->requestId = (int) $id;
        $this->load($gateway);
    }

    private function load(GatewayClient $gateway): void
    {
        try {
            $this->request = $gateway->get("/api/maintenance/requests/{$this->requestId}")['data'] ?? null;
            $this->error = $this->request === null ? 'Demande introuvable.' : null;
        } catch (HttpException $e) {
            $this->error = $e->getStatusCode() === 404
                ? 'Demande introuvable.'
                : 'Service de maintenance indisponible.';
            Log::warning('Maintenance\\Show : chargement impossible', ['id' => $this->requestId, 'status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Impossible de charger la demande.';
            Log::warning('Maintenance\\Show : erreur', ['id' => $this->requestId, 'error' => $e->getMessage()]);
        }
    }

    /** Rôle courant issu de la session (posé au login). */
    private function canManage(): bool
    {
        return in_array(session('jwt_user.role'), ['admin', 'asset_manager'], true);
    }

    public function schedule(): void
    {
        $this->validate([
            'scheduledDate' => ['required', 'date', 'after_or_equal:today'],
            'technicianName' => ['nullable', 'string', 'max:255'],
        ], [
            'scheduledDate.required' => 'La date planifiée est obligatoire.',
            'scheduledDate.after_or_equal' => 'La date doit être aujourd\'hui ou ultérieure.',
        ]);

        $this->performAction('schedule', [
            'scheduled_date' => $this->scheduledDate,
            'technician_name' => $this->technicianName !== '' ? $this->technicianName : null,
        ]);
    }

    public function start(): void
    {
        $this->performAction('start');
    }

    public function complete(): void
    {
        $this->performAction('complete');
    }

    public function addIntervention(): void
    {
        $this->validate([
            'interventionDescription' => ['required', 'string', 'min:5', 'max:2000'],
            'interventionCost' => ['nullable', 'numeric', 'min:0'],
            'performedAt' => ['required', 'date', 'before_or_equal:today'],
            'technicianName' => ['nullable', 'string', 'max:255'],
        ], [
            'interventionDescription.required' => 'Décrivez l\'intervention effectuée.',
            'interventionDescription.min' => 'Décrivez l\'intervention en au moins 5 caractères.',
            'performedAt.required' => 'La date de l\'intervention est obligatoire.',
        ]);

        $this->performAction('interventions', [
            'description' => $this->interventionDescription,
            'cost' => $this->interventionCost !== '' ? (float) $this->interventionCost : 0,
            'performed_at' => $this->performedAt,
            'technician_name' => $this->technicianName !== '' ? $this->technicianName : null,
        ]);

        if ($this->error === null) {
            $this->reset(['interventionDescription', 'interventionCost', 'performedAt']);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function performAction(string $action, array $payload = []): void
    {
        if (! $this->canManage()) {
            $this->error = 'Votre rôle ne permet pas cette action.';

            return;
        }

        try {
            app(GatewayClient::class)->post("/api/maintenance/requests/{$this->requestId}/{$action}", $payload);
            $this->notice = 'Action effectuée avec succès.';
            $this->error = null;
            $this->load(app(GatewayClient::class));
        } catch (HttpException $e) {
            $body = $e->getMessage();
            $this->error = "L'action a été refusée ({$e->getStatusCode()}).";
            Log::warning('Maintenance\\Show : action refusée', ['id' => $this->requestId, 'action' => $action, 'status' => $e->getStatusCode(), 'body' => $body]);
        } catch (\Throwable $e) {
            $this->error = 'Service de maintenance indisponible.';
            Log::warning('Maintenance\\Show : action impossible', ['id' => $this->requestId, 'action' => $action, 'error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.maintenance.show', [
            'canManage' => $this->canManage(),
            'status' => $this->request['status'] ?? null,
            'presenter' => StatusPresenter::class,
            'priorityPresenter' => PriorityPresenter::class,
        ])->extends('layouts.app');
    }
}
