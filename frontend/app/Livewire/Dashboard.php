<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Support\GatewayClient;
use App\Support\StatusPresenter;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tableau de bord — agrégation temps réel via /api/reports/dashboard.
 */
class Dashboard extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $me = null;

    /** @var array<string, mixed>|null */
    public ?array $data = null;

    public ?string $error = null;

    public function mount(GatewayClient $gateway): void
    {
        $this->me = $gateway->get('/api/auth/me')['data'] ?? null;

        try {
            $this->data = $gateway->get('/api/reports/dashboard')['data'] ?? null;
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 403) {
                $this->error = 'Votre rôle ne donne pas accès au tableau de bord consolidé.';
            } else {
                $this->error = 'Service de reporting indisponible.';
            }
            Log::warning('Dashboard : reporting inaccessible', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Service de reporting indisponible.';
            Log::warning('Dashboard : reporting injoignable', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'presenter' => StatusPresenter::class,
        ])->extends('layouts.app');
    }
}
