<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\CheckStatus;
use App\Models\InventoryCampaign;
use App\Models\InventoryLine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Logique métier des campagnes d'inventaire physique.
 * Les lignes sont des instantanés des biens de l'asset-service,
 * chargés à la création de la campagne.
 */
class InventoryService
{
    public function __construct(private readonly AssetServiceClient $assets) {}

    /**
     * Crée une campagne brouillon et charge les biens de l'asset-service
     * comme lignes d'inventaire (instantané).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, string $token, ?int $actorId, ?string $actorName, ?string $actorEmail): InventoryCampaign
    {
        $filters = [];
        if (! empty($data['department_id'])) {
            $filters['department_id'] = (int) $data['department_id'];
        }
        if (! empty($data['location_id'])) {
            $filters['location_id'] = (int) $data['location_id'];
        }

        $assets = $this->assets->listAssets($filters, $token);

        return DB::transaction(function () use ($data, $assets, $actorId, $actorName, $actorEmail): InventoryCampaign {
            $campaign = InventoryCampaign::create([
                'reference' => $this->nextReference(),
                'name' => $data['name'],
                'department_id' => $data['department_id'] ?? null,
                'department_name' => $data['department_name'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'location_name' => $data['location_name'] ?? null,
                'status' => CampaignStatus::Draft,
                'created_by' => $actorId,
                'created_by_name' => $actorName,
                'created_by_email' => $actorEmail,
            ]);

            $rows = array_map(
                fn (array $a) => [
                    'inventory_campaign_id' => $campaign->id,
                    'asset_id' => (int) $a['id'],
                    'asset_code' => (string) ($a['code'] ?? ''),
                    'asset_designation' => (string) ($a['designation'] ?? ''),
                    'asset_status' => $a['status'] ?? null,
                    'department_name' => $a['department']['name'] ?? null,
                    'location_name' => $a['location']['name'] ?? null,
                    'check_status' => CheckStatus::Pending->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $assets,
            );

            foreach (array_chunk($rows, 500) as $chunk) {
                InventoryLine::insert($chunk);
            }

            return $campaign->fresh('lines');
        });
    }

    /**
     * Démarre la campagne : draft -> in_progress.
     */
    public function start(InventoryCampaign $campaign): InventoryCampaign
    {
        $this->transition($campaign, CampaignStatus::InProgress);

        $campaign->update([
            'status' => CampaignStatus::InProgress,
            'started_at' => now(),
        ]);

        return $campaign->fresh();
    }

    /**
     * Enregistre le résultat du contrôle d'une ligne.
     * Retourne la ligne à 'pending' pour corriger un contrôle erroné.
     */
    public function checkLine(InventoryLine $line, string $status, ?string $notes, ?int $actorId, ?string $actorName): InventoryLine
    {
        $campaign = $line->campaign;
        abort_unless(
            $campaign !== null && $campaign->status === CampaignStatus::InProgress,
            422,
            'Le contrôle n\'est possible que sur une campagne en cours.',
        );

        $check = CheckStatus::from($status);

        $line->update([
            'check_status' => $check,
            'notes' => $notes,
            'checked_by' => $check === CheckStatus::Pending ? null : $actorId,
            'checked_by_name' => $check === CheckStatus::Pending ? null : $actorName,
            'checked_at' => $check === CheckStatus::Pending ? null : now(),
        ]);

        return $line->fresh();
    }

    /**
     * Clôture la campagne : in_progress -> completed.
     * Les lignes encore 'pending' sont marquées 'missing'.
     */
    public function complete(InventoryCampaign $campaign): InventoryCampaign
    {
        $this->transition($campaign, CampaignStatus::Completed);

        return DB::transaction(function () use ($campaign): InventoryCampaign {
            // Tout bien non contrôlé est considéré manquant à la clôture
            $campaign->lines()
                ->where('check_status', CheckStatus::Pending->value)
                ->update([
                    'check_status' => CheckStatus::Missing->value,
                    'notes' => DB::raw("COALESCE(notes, 'Non contrôlé à la clôture')"),
                    'checked_at' => now(),
                ]);

            $campaign->update([
                'status' => CampaignStatus::Completed,
                'completed_at' => now(),
            ]);

            return $campaign->fresh();
        });
    }

    /**
     * Supprime une campagne brouillon (et ses lignes).
     */
    public function deleteDraft(InventoryCampaign $campaign): void
    {
        if ($campaign->status !== CampaignStatus::Draft) {
            throw new UnprocessableEntityHttpException('Seules les campagnes brouillon peuvent être supprimées.');
        }

        $campaign->delete(); // soft delete
    }

    /**
     * Synthèse d'une campagne : totaux par statut de contrôle + écarts.
     *
     * @return array<string, mixed>
     */
    public function summary(InventoryCampaign $campaign): array
    {
        $counts = $campaign->lines()
            ->selectRaw('check_status, COUNT(*) as total')
            ->groupBy('check_status')
            ->pluck('total', 'check_status')
            ->all();

        $total = array_sum($counts);
        $missing = (int) ($counts[CheckStatus::Missing->value] ?? 0);
        $damaged = (int) ($counts[CheckStatus::Damaged->value] ?? 0);

        return [
            'reference' => $campaign->reference,
            'name' => $campaign->name,
            'status' => $campaign->status->value,
            'lines_total' => $total,
            'found' => (int) ($counts[CheckStatus::Found->value] ?? 0),
            'missing' => $missing,
            'damaged' => $damaged,
            'pending' => (int) ($counts[CheckStatus::Pending->value] ?? 0),
            'discrepancies' => $missing + $damaged,
            'discrepancy_rate' => $total > 0 ? round(($missing + $damaged) / $total * 100, 2) : 0.0,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, InventoryCampaign>
     */
    public function paginateCampaigns(array $filters): LengthAwarePaginator
    {
        return InventoryCampaign::query()
            ->when($filters['status'] ?? null, function ($q, string $status): void {
                if (in_array($status, CampaignStatus::values(), true)) {
                    $q->where('status', $status);
                }
            })
            ->when($filters['search'] ?? null, function ($q, string $s): void {
                $q->where(function ($w) use ($s): void {
                    $w->where('name', 'ilike', "%{$s}%")
                        ->orWhere('reference', 'ilike', "%{$s}%");
                });
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, InventoryLine>
     */
    public function paginateLines(InventoryCampaign $campaign, array $filters): LengthAwarePaginator
    {
        return $campaign->lines()
            ->when($filters['check_status'] ?? null, function ($q, string $s): void {
                if (in_array($s, CheckStatus::values(), true)) {
                    $q->where('check_status', $s);
                }
            })
            ->when($filters['search'] ?? null, function ($q, string $s): void {
                $q->where(function ($w) use ($s): void {
                    $w->where('asset_code', 'ilike', "%{$s}%")
                        ->orWhere('asset_designation', 'ilike', "%{$s}%");
                });
            })
            ->orderBy('asset_code')
            ->paginate((int) ($filters['per_page'] ?? 50));
    }

    public function findCampaign(int $id): ?InventoryCampaign
    {
        return InventoryCampaign::withCount('lines')->find($id);
    }

    public function findLine(int $campaignId, int $lineId): ?InventoryLine
    {
        return InventoryLine::where('inventory_campaign_id', $campaignId)->find($lineId);
    }

    private function transition(InventoryCampaign $campaign, CampaignStatus $target): void
    {
        $current = $campaign->status instanceof CampaignStatus
            ? $campaign->status
            : CampaignStatus::from((string) $campaign->status);

        if (! $current->canTransitionTo($target)) {
            throw new UnprocessableEntityHttpException(
                "Transition impossible : « {$current->label()} » → « {$target->label()} »."
            );
        }
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $count = InventoryCampaign::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%d-%04d', $year, $count);
    }
}
