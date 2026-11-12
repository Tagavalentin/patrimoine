<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReportAuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapports consolidés : agrégation des services internes
 * (asset, maintenance, inventory) + exports CSV + journal d'audit.
 */
class ReportService
{
    private const ASSET_STATUSES = ['acquired', 'assigned', 'in_maintenance', 'decommissioned', 'disposed'];

    private const MAINTENANCE_STATUSES = ['requested', 'scheduled', 'in_progress', 'completed'];

    public function __construct(private readonly ServiceClient $client) {}

    /**
     * Tableau de bord consolidé pour la direction.
     *
     * @return array<string, mixed>
     */
    public function dashboard(string $token): array
    {
        // --- Patrimoine : volumes par statut + valorisation
        $assetsByStatus = [];
        $assetsTotal = 0;
        foreach (self::ASSET_STATUSES as $status) {
            $body = $this->client->get('asset', '/api/assets', [
                'status' => $status,
                'per_page' => 1,
            ], $token);
            $count = (int) ($body['meta']['total'] ?? 0);
            $assetsByStatus[$status] = $count;
            $assetsTotal += $count;
        }

        $depreciation = $this->client->get('asset', '/api/depreciation/summary', [], $token);
        $valuation = $depreciation['data'] ?? [];

        // --- Maintenance : volumes par statut
        $maintenanceByStatus = [];
        $maintenanceTotal = 0;
        foreach (self::MAINTENANCE_STATUSES as $status) {
            $body = $this->client->get('maintenance', '/api/requests', [
                'status' => $status,
                'per_page' => 1,
            ], $token);
            $count = (int) ($body['meta']['total'] ?? 0);
            $maintenanceByStatus[$status] = $count;
            $maintenanceTotal += $count;
        }

        // --- Inventaire : dernière campagne + synthèse
        $campaigns = $this->client->get('inventory', '/api/campaigns', ['per_page' => 1], $token);
        $latest = $campaigns['data'][0] ?? null;
        $inventory = [
            'latest_campaign' => $latest['reference'] ?? null,
            'latest_status' => $latest['status'] ?? null,
        ];
        if ($latest !== null) {
            $summary = $this->client->get('inventory', "/api/campaigns/{$latest['id']}/summary", [], $token);
            $inventory['latest_summary'] = $summary['data'] ?? null;
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'assets' => [
                'total' => $assetsTotal,
                'by_status' => $assetsByStatus,
                'net_book_value' => $valuation['net_book_value'] ?? null,
                'accumulated_depreciation' => $valuation['accumulated_depreciation'] ?? null,
            ],
            'maintenance' => [
                'total' => $maintenanceTotal,
                'by_status' => $maintenanceByStatus,
                'open' => ($maintenanceByStatus['requested'] ?? 0)
                    + ($maintenanceByStatus['scheduled'] ?? 0)
                    + ($maintenanceByStatus['in_progress'] ?? 0),
            ],
            'inventory' => $inventory,
        ];
    }

    /**
     * Export CSV des biens (toutes pages de l'asset-service).
     *
     * @param  array<string, mixed>  $filters
     */
    public function assetsCsv(array $filters, string $token, ?int $actorId, ?string $actorName, ?string $actorEmail, ?string $ip): StreamedResponse
    {
        $rows = $this->client->getAllPages('asset', '/api/assets', $filters, $token);
        $this->audit('assets_csv', 'csv', $filters, count($rows), $actorId, $actorName, $actorEmail, $ip);

        return $this->csvResponse('biens', [
            'Code', 'Désignation', 'Statut', 'Catégorie', 'Valeur acquisition',
            'Date acquisition', 'Durée de vie (années)',
        ], array_map(fn (array $a) => [
            $a['code'] ?? '',
            $a['designation'] ?? '',
            $a['status'] ?? '',
            is_array($a['category'] ?? null) ? ($a['category']['name'] ?? '') : '',
            $a['acquisition_value'] ?? '',
            $a['acquisition_date'] ?? '',
            $a['useful_life_years'] ?? '',
        ], $rows));
    }

    /**
     * Export CSV des demandes de maintenance.
     *
     * @param  array<string, mixed>  $filters
     */
    public function maintenanceCsv(array $filters, string $token, ?int $actorId, ?string $actorName, ?string $actorEmail, ?string $ip): StreamedResponse
    {
        $rows = $this->client->getAllPages('maintenance', '/api/requests', $filters, $token);
        $this->audit('maintenance_csv', 'csv', $filters, count($rows), $actorId, $actorName, $actorEmail, $ip);

        return $this->csvResponse('demandes-maintenance', [
            'Référence', 'Bien', 'Description', 'Priorité', 'Statut',
            'Coût total', 'Demandé par', 'Créée le',
        ], array_map(fn (array $r) => [
            $r['reference'] ?? '',
            ($r['asset']['code'] ?? '').' - '.($r['asset']['designation'] ?? ''),
            $r['description'] ?? '',
            $r['priority'] ?? '',
            $r['status'] ?? '',
            $r['total_cost'] ?? 0,
            $r['requested_by']['name'] ?? $r['requested_by']['email'] ?? '',
            $r['created_at'] ?? '',
        ], $rows));
    }

    /**
     * Export CSV des campagnes d'inventaire.
     *
     * @param  array<string, mixed>  $filters
     */
    public function inventoryCsv(array $filters, string $token, ?int $actorId, ?string $actorName, ?string $actorEmail, ?string $ip): StreamedResponse
    {
        $rows = $this->client->getAllPages('inventory', '/api/campaigns', $filters, $token);
        $this->audit('inventory_csv', 'csv', $filters, count($rows), $actorId, $actorName, $actorEmail, $ip);

        return $this->csvResponse('campagnes-inventaire', [
            'Référence', 'Nom', 'Statut', 'Nb lignes', 'Début', 'Clôture', 'Créée par',
        ], array_map(fn (array $c) => [
            $c['reference'] ?? '',
            $c['name'] ?? '',
            $c['status'] ?? '',
            $c['lines_count'] ?? '',
            $c['started_at'] ?? '',
            $c['completed_at'] ?? '',
            $c['created_by']['name'] ?? $c['created_by']['email'] ?? '',
        ], $rows));
    }

    /**
     * Journal d'audit des rapports générés.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ReportAuditLog>
     */
    public function auditLogs(array $filters): LengthAwarePaginator
    {
        return ReportAuditLog::query()
            ->when($filters['report_type'] ?? null, fn ($q, string $t) => $q->where('report_type', $t))
            ->when($filters['generated_by'] ?? null, fn ($q, int $id) => $q->where('generated_by', $id))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    /**
     * @param  array<int, string>  $header
     * @param  list<array<int, string|int|float>>  $rows
     */
    private function csvResponse(string $slug, array $header, array $rows): StreamedResponse
    {
        $filename = "rapport-{$slug}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ';');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    private function audit(
        string $type,
        string $format,
        ?array $filters,
        int $rows,
        ?int $actorId,
        ?string $actorName,
        ?string $actorEmail,
        ?string $ip,
    ): void {
        ReportAuditLog::create([
            'report_type' => $type,
            'format' => $format,
            'filters' => $filters,
            'rows_count' => $rows,
            'generated_by' => $actorId,
            'generated_by_name' => $actorName,
            'generated_by_email' => $actorEmail,
            'ip_address' => $ip,
        ]);
    }
}
