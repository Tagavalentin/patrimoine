<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Assignment;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Affectations des biens : affecter, transférer, restituer.
 * Règle d'or : au plus UNE affectation active (end_date NULL) par bien.
 */
class AssignmentService
{
    public function __construct(private readonly AssetService $assets) {}

    /**
     * Affecte un bien acquis à un service. Le statut passe à 'assigned'.
     *
     * @param  array<string, mixed>  $data
     */
    public function assign(Asset $asset, array $data, ?int $actorId): Assignment
    {
        if ($asset->status !== AssetStatus::Acquired) {
            throw new UnprocessableEntityHttpException(
                'Seul un bien au statut « acquis » peut être affecté.'
            );
        }

        $this->assertNoActiveAssignment($asset);
        $department = $this->activeDepartment($data['department_id']);

        return DB::transaction(function () use ($asset, $data, $department, $actorId): Assignment {
            /** @var Assignment $assignment */
            $assignment = Assignment::create([
                'asset_id' => $asset->id,
                'department_id' => $department->id,
                'responsible_user_id' => $data['responsible_user_id'] ?? null,
                'responsible_name' => $data['responsible_name'] ?? null,
                'responsible_email' => $data['responsible_email'] ?? null,
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => null,
                'reason' => $data['reason'] ?? null,
                'created_by' => $actorId,
            ]);

            $this->assets->changeStatus(
                $asset,
                AssetStatus::Assigned,
                'Affectation : '.$department->name,
                $actorId,
            );

            return $assignment->load(['asset.category', 'asset.location', 'department']);
        });
    }

    /**
     * Transfère un bien déjà affecté vers un autre service (ou un
     * autre responsable) : clôt l'affectation courante et en crée une
     * nouvelle — l'historique complet est conservé.
     *
     * @param  array<string, mixed>  $data
     */
    public function transfer(Asset $asset, array $data, ?int $actorId): Assignment
    {
        /** @var Assignment|null $current */
        $current = Assignment::activeFor($asset->id);

        if ($current === null) {
            throw new UnprocessableEntityHttpException(
                'Ce bien n\'a aucune affectation active : utilisez « affecter ».'
            );
        }

        $department = $this->activeDepartment($data['department_id']);

        return DB::transaction(function () use ($asset, $data, $current, $department, $actorId): Assignment {
            $this->close($current, $data['end_date'] ?? now()->toDateString());

            /** @var Assignment $assignment */
            $assignment = Assignment::create([
                'asset_id' => $asset->id,
                'department_id' => $department->id,
                'responsible_user_id' => $data['responsible_user_id'] ?? null,
                'responsible_name' => $data['responsible_name'] ?? null,
                'responsible_email' => $data['responsible_email'] ?? null,
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => null,
                'reason' => $data['reason'] ?? $current->reason,
                'created_by' => $actorId,
            ]);

            return $assignment->load(['asset.category', 'asset.location', 'department']);
        });
    }

    /**
     * Restitue un bien : clôt l'affectation active et repasse le
     * statut à 'acquired'.
     */
    public function returnToPool(Asset $asset, ?string $endDate, ?string $reason, ?int $actorId): Assignment
    {
        /** @var Assignment|null $current */
        $current = Assignment::activeFor($asset->id);

        if ($current === null) {
            throw new UnprocessableEntityHttpException(
                'Ce bien n\'a aucune affectation active à restituer.'
            );
        }

        return DB::transaction(function () use ($asset, $current, $endDate, $reason, $actorId): Assignment {
            $this->close($current, $endDate ?? now()->toDateString());

            if ($reason !== null) {
                $current->update(['reason' => trim(($current->reason ?? '').' | Restitution : '.$reason)]);
            }

            $this->assets->changeStatus(
                $asset,
                AssetStatus::Acquired,
                'Restitution au parc',
                $actorId,
            );

            return $current->fresh(['asset.category', 'asset.location', 'department']);
        });
    }

    /**
     * @return LengthAwarePaginator<int, Assignment>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Assignment::query()
            ->with(['asset:id,code,designation,status', 'department:id,name,code'])
            ->when($filters['asset_id'] ?? null, fn ($q, int $id) => $q->where('asset_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, int $id) => $q->where('department_id', $id))
            ->when($filters['responsible_user_id'] ?? null, fn ($q, int $id) => $q->where('responsible_user_id', $id))
            ->when(
                array_key_exists('active', $filters) && $filters['active'] !== null,
                fn ($q) => $filters['active']
                    ? $q->whereNull('end_date')
                    : $q->whereNotNull('end_date'),
            )
            ->latest('start_date')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    /** @return Collection<int, Assignment> */
    public function historyFor(Asset $asset)
    {
        return Assignment::where('asset_id', $asset->id)
            ->with('department:id,name,code')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    private function assertNoActiveAssignment(Asset $asset): void
    {
        if (Assignment::activeFor($asset->id) !== null) {
            throw new UnprocessableEntityHttpException(
                'Ce bien a déjà une affectation active : utilisez « transférer ».'
            );
        }
    }

    private function activeDepartment(int $id): Department
    {
        /** @var Department|null $department */
        $department = Department::where('id', $id)->where('is_active', true)->first();

        if ($department === null) {
            throw new UnprocessableEntityHttpException('Service destinataire inexistant ou inactif.');
        }

        return $department;
    }

    private function close(Assignment $assignment, string $endDate): void
    {
        if ($assignment->start_date->toDateString() > $endDate) {
            throw new UnprocessableEntityHttpException(
                'La date de fin ne peut pas être antérieure à la date de début.'
            );
        }

        $assignment->update(['end_date' => $endDate]);
    }
}
