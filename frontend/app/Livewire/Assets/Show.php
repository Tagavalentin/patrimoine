<?php

declare(strict_types=1);

namespace App\Livewire\Assets;

use App\Support\GatewayClient;
use App\Support\StatusPresenter;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Fiche bien — GET /api/assets/assets/{id}, /depreciation, /assignments.
 */
class Show extends Component
{
    public int $assetId;

    /** @var array<string, mixed>|null */
    public ?array $assetData = null;

    /** @var array<string, mixed>|null */
    public ?array $depreciation = null;

    /** @var array<int, array<string, mixed>> */
    public array $assignments = [];

    public ?string $error = null;

    public function mount(string $asset, GatewayClient $gateway): void
    {
        $this->assetId = (int) $asset;

        try {
            $this->assetData = $gateway->get("/api/assets/assets/{$asset}")['data'] ?? null;
        } catch (\Throwable $e) {
            $this->error = 'Bien introuvable ou service indisponible.';
            Log::warning('Assets\\Show : asset introuvable', ['id' => $asset, 'error' => $e->getMessage()]);

            return;
        }

        // Données complémentaires — tolérantes aux pannes partielles
        try {
            $this->depreciation = $gateway->get("/api/assets/assets/{$asset}/depreciation")['data'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('Assets\\Show : depreciation indisponible', ['id' => $asset]);
        }

        try {
            $history = $gateway->get("/api/assets/assets/{$asset}/assignments");
            $this->assignments = $history['data'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('Assets\\Show : assignments indisponible', ['id' => $asset]);
        }
    }

    public function render()
    {
        return view('livewire.assets.show', [
            'presenter' => StatusPresenter::class,
        ])->extends('layouts.app');
    }
}
