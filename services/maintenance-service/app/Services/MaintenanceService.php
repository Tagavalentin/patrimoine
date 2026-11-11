<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Models\Intervention;
use App\Models\MaintenanceRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Logique métier des demandes de maintenance.
 * Toute écriture qui impacte le statut du bien appelle
 * l'asset-service via AssetServiceClient avec le token propagé.
 */
class MaintenanceService
{
    public function __construct(private readonly AssetServiceClient $assets) {}

    /**
     * Crée une demande après vérification de l'existence du bien.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, string $token, ?int $actorId, ?string $actorName, ?string $actorEmail): MaintenanceRequest
    {
        $asset = $this->assets->findAsset((int) $data['asset_id'], $token);

        if ($asset === null) {
            throw new NotFoundHttpException('Bien introuvable dans le service des biens.');
        }

        return MaintenanceRequest::create([
            'reference' => $this->nextReference(),
            'asset_id' => (int) $data['asset_id'],
            'asset_code' => (string) ($asset['code'] ?? ''),
            'asset_designation' => (string) ($asset['designation'] ?? ''),
            'asset_status' => $asset['status'] ?? null,
            'description' => $data['description'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => MaintenanceStatus::Requested,
            'requested_by' => $actorId,
            'requested_by_name' => $actorName,
            'requested_by_email' => $actorEmail,
        ]);
    }

    /**
     * Planifie une demande : requested -> scheduled.
     */
    public function schedule(MaintenanceRequest $request, string $scheduledDate, ?int $actorId, ?string $technicianName): MaintenanceRequest
    {
        $this->transition($request, MaintenanceStatus::Scheduled);

        $request->update([
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_date' => $scheduledDate,
            'assigned_to_name' => $technicianName,
        ]);

        return $this->freshWithCost($request);
    }

    /**
     * Démarre l'intervention : scheduled -> in_progress.
     * Le bien passe à 'in_maintenance' côté asset-service ;
     * son statut précédent est mémorisé pour restauration.
     */
    public function start(MaintenanceRequest $request, string $token, ?int $actorId): MaintenanceRequest
    {
        $this->transition($request, MaintenanceStatus::InProgress);

        return DB::transaction(function () use ($request, $token): MaintenanceRequest {
            // Statut actuel du bien (source de vérité : asset-service)
            $asset = $this->assets->findAsset($request->asset_id, $token);
            $previous = $asset['status'] ?? 'acquired';

            // Passe le bien en maintenance — lève 503/422 si KO (rollback)
            $this->assets->setAssetStatus(
                $request->asset_id,
                'in_maintenance',
                'Maintenance '.$request->reference,
                $token,
            );

            $request->update([
                'status' => MaintenanceStatus::InProgress,
                'started_at' => now(),
                'asset_previous_status' => $previous,
            ]);

            return $this->freshWithCost($request);
        });
    }

    /**
     * Clôture la demande : in_progress -> completed.
     * Restaure le statut du bien et fige le coût total.
     */
    public function complete(MaintenanceRequest $request, string $token): MaintenanceRequest
    {
        $this->transition($request, MaintenanceStatus::Completed);

        return DB::transaction(function () use ($request, $token): MaintenanceRequest {
            // Restaure le statut d'avant maintenance
            $this->assets->setAssetStatus(
                $request->asset_id,
                $request->asset_previous_status ?? 'acquired',
                'Fin de maintenance '.$request->reference,
                $token,
            );

            $request->update([
                'status' => MaintenanceStatus::Completed,
                'completed_at' => now(),
            ]);

            return $this->freshWithCost($request);
        });
    }

    /**
     * Ajoute une intervention (coût) à une demande planifiée ou en cours.
     *
     * @param  array<string, mixed>  $data
     */
    public function addIntervention(MaintenanceRequest $request, array $data, ?int $actorId): Intervention
    {
        if (! in_array($request->status, [MaintenanceStatus::Scheduled, MaintenanceStatus::InProgress], true)) {
            throw new UnprocessableEntityHttpException(
                'Les interventions ne peuvent être ajoutées qu\'aux demandes planifiées ou en cours.'
            );
        }

        return $request->interventions()->create([
            'technician_name' => $data['technician_name'] ?? null,
            'description' => $data['description'],
            'cost' => $data['cost'] ?? 0,
            'performed_at' => $data['performed_at'],
            'created_by' => $actorId,
        ]);
    }

    /**
     * Synthèse maintenance d'un bien : nombre de demandes, coût cumulé.
     *
     * @return array<string, mixed>
     */
    public function summaryForAsset(int $assetId): array
    {
        $requests = MaintenanceRequest::where('asset_id', $assetId)->get();
        $interventionIds = $requests->pluck('id');

        $totalCost = Intervention::whereIn('maintenance_request_id', $interventionIds)->sum('cost');

        return [
            'asset_id' => $assetId,
            'requests_count' => $requests->count(),
            'completed_count' => $requests->where('status', MaintenanceStatus::Completed)->count(),
            'in_progress_count' => $requests->where('status', MaintenanceStatus::InProgress)->count(),
            'total_cost' => (float) $totalCost,
            'last_request_at' => $requests->max('created_at'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, MaintenanceRequest>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return MaintenanceRequest::query()
            ->with('interventions')
            ->withSum('interventions', 'cost')
            ->when($filters['status'] ?? null, function ($q, string $status): void {
                if (in_array($status, MaintenanceStatus::values(), true)) {
                    $q->where('status', $status);
                }
            })
            ->when($filters['asset_id'] ?? null, fn ($q, int $id) => $q->where('asset_id', $id))
            ->when($filters['priority'] ?? null, fn ($q, string $p) => $q->where('priority', $p))
            ->when($filters['mine'] ?? null, function ($q) use ($filters): void {
                $q->where('requested_by', (int) $filters['mine']);
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function find(int $id): ?MaintenanceRequest
    {
        return MaintenanceRequest::with('interventions')->withSum('interventions', 'cost')->find($id);
    }

    /**
     * Recharge la demande avec ses interventions et le coût total.
     */
    private function freshWithCost(MaintenanceRequest $request): MaintenanceRequest
    {
        /** @var MaintenanceRequest $fresh */
        $fresh = $request->fresh(['interventions']);
        $fresh->loadSum('interventions', 'cost');

        return $fresh;
    }

    private function transition(MaintenanceRequest $request, MaintenanceStatus $target): void
    {
        $current = $request->status instanceof MaintenanceStatus
            ? $request->status
            : MaintenanceStatus::from((string) $request->status);

        if (! $current->canTransitionTo($target)) {
            throw new UnprocessableEntityHttpException(
                "Transition impossible : « {$current->label()} » → « {$target->label()} »."
            );
        }
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $count = MaintenanceRequest::whereYear('created_at', $year)->count() + 1;

        return sprintf('MNT-%d-%04d', $year, $count);
    }
}
