<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Support\GatewayClient;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Rapports : exports CSV (via /rapports/export/{type}) et journal
 * d'audit des exports (réservé admin/auditor).
 */
class Index extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $auditLogs = [];

    public int $total = 0;

    public int $lastPage = 1;

    public int $page = 1;

    public ?string $error = null;

    private bool $canViewAudit = false;

    public function mount(GatewayClient $gateway): void
    {
        $this->canViewAudit = in_array(session('jwt_user.role'), ['admin', 'auditor'], true);

        if ($this->canViewAudit) {
            $this->loadAuditLogs($gateway);
        }
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, min($page, $this->lastPage));
        $this->loadAuditLogs(app(GatewayClient::class));
    }

    private function loadAuditLogs(GatewayClient $gateway): void
    {
        try {
            $response = $gateway->get('/api/reports/audit-logs', ['page' => $this->page, 'per_page' => 15]);
            $this->auditLogs = $response['data']['data'] ?? $response['data'] ?? [];
            $this->total = (int) ($response['data']['meta']['total'] ?? $response['meta']['total'] ?? 0);
            $this->lastPage = max(1, (int) ($response['data']['meta']['last_page'] ?? $response['meta']['last_page'] ?? 1));
            $this->error = null;
        } catch (HttpException $e) {
            $this->error = 'Journal d\'audit indisponible.';
            Log::warning('Reports\\Index : audit-logs', ['status' => $e->getStatusCode()]);
        } catch (\Throwable $e) {
            $this->error = 'Impossible de charger le journal d\'audit.';
            Log::warning('Reports\\Index : erreur', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.reports.index', [
            'canViewAudit' => $this->canViewAudit,
        ])->extends('layouts.app');
    }
}
