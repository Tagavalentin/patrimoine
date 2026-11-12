<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Support\GatewayClient;
use Livewire\Component;

/**
 * Tableau de bord — socle de l'étape 13.
 * Les cartes métier seront branchées aux endpoints
 * /api/reports/dashboard et /api/assets/* aux étapes suivantes.
 */
class Dashboard extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $me = null;

    public function mount(GatewayClient $gateway): void
    {
        $this->me = $gateway->get('/api/auth/me')['data'] ?? null;
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }
}
