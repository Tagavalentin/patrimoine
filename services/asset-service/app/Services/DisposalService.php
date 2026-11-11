<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\DisposalStatus;
use App\Enums\DisposalType;
use App\Models\Asset;
use App\Models\Assignment;
use App\Models\Disposal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Workflow de sortie de patrimoine :
 * requested -> approved -> completed (bien -> disposed)
 * requested -> rejected.
 */
class DisposalService
{
    public function __construct(private readonly AssetService $assets) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function request(Asset $asset, array $data, ?int $actorId, ?string $actorName, ?string $actorEmail): Disposal
    {
        $status = $asset->status instanceof AssetStatus
            ? $asset->status
            : AssetStatus::from((string) $asset->status);

        if ($status === AssetStatus::Disposed) {
            throw new UnprocessableEntityHttpException('Ce bien est déjà sorti du patrimoine.');
        }

        if (Assignment::activeFor($asset->id) !== null) {
            throw new UnprocessableEntityHttpException(
                'Le bien est encore affecté : restituez-le avant de demander sa sortie.'
            );
        }

        $active = Disposal::where('asset_id', $asset->id)
            ->whereIn('status', [DisposalStatus::Requested->value, DisposalStatus::Approved->value])
            ->exists();

        if ($active) {
            throw new UnprocessableEntityHttpException('Une demande de sortie est déjà en cours pour ce bien.');
        }

        return Disposal::create([
            'asset_id' => $asset->id,
            'reference' => $this->nextReference(),
            'disposal_type' => $data['disposal_type'],
            'reason' => $data['reason'],
            'estimated_value' => $data['estimated_value'] ?? null,
            'status' => DisposalStatus::Requested,
            'requested_by' => $actorId,
            'requested_by_name' => $actorName,
            'requested_by_email' => $actorEmail,
        ]);
    }

    /**
     * Approuve la demande : le bien passe « réformé » (decommissioned).
     */
    public function approve(Disposal $disposal, ?int $actorId, ?string $decisionReason): Disposal
    {
        $this->transition($disposal, DisposalStatus::Approved);

        return DB::transaction(function () use ($disposal, $actorId, $decisionReason): Disposal {
            $asset = Asset::findOrFail($disposal->asset_id);

            // Si le bien n'est pas déjà réformé, on le réformre (traçabilité)
            $assetStatus = $asset->status instanceof AssetStatus
                ? $asset->status
                : AssetStatus::from((string) $asset->status);

            if ($assetStatus !== AssetStatus::Decommissioned) {
                $this->assets->changeStatus(
                    $asset,
                    AssetStatus::Decommissioned,
                    'Réforme préalable à la sortie '.$disposal->reference,
                    $actorId,
                );
            }

            $disposal->update([
                'status' => DisposalStatus::Approved,
                'approved_by' => $actorId,
                'approved_at' => now(),
                'decision_reason' => $decisionReason,
            ]);

            return $disposal->fresh('asset');
        });
    }

    /**
     * Rejette la demande (motif obligatoire).
     */
    public function reject(Disposal $disposal, ?int $actorId, string $decisionReason): Disposal
    {
        $this->transition($disposal, DisposalStatus::Rejected);

        $disposal->update([
            'status' => DisposalStatus::Rejected,
            'approved_by' => $actorId,
            'approved_at' => now(),
            'decision_reason' => $decisionReason,
        ]);

        return $disposal->fresh('asset');
    }

    /**
     * Réalise la sortie : le bien passe « disposed » (terminal).
     */
    public function complete(Disposal $disposal, array $data, ?int $actorId): Disposal
    {
        $this->transition($disposal, DisposalStatus::Completed);

        return DB::transaction(function () use ($disposal, $data, $actorId): Disposal {
            $asset = Asset::findOrFail($disposal->asset_id);

            $this->assets->changeStatus(
                $asset,
                AssetStatus::Disposed,
                sprintf(
                    'Sortie du patrimoine (%s) — %s',
                    $disposal->disposal_type->label(),
                    $disposal->reference,
                ),
                $actorId,
            );

            $disposal->update([
                'status' => DisposalStatus::Completed,
                'disposal_date' => $data['disposal_date'] ?? now()->toDateString(),
                'sale_value' => $data['sale_value'] ?? null,
                'completed_by' => $actorId,
                'completed_at' => now(),
            ]);

            return $disposal->fresh('asset');
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Disposal>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Disposal::query()
            ->with('asset:id,code,designation,status')
            ->when($filters['status'] ?? null, function ($q, string $status): void {
                if (in_array($status, DisposalStatus::values(), true)) {
                    $q->where('status', $status);
                }
            })
            ->when($filters['asset_id'] ?? null, fn ($q, int $id) => $q->where('asset_id', $id))
            ->when($filters['disposal_type'] ?? null, function ($q, string $type): void {
                if (in_array($type, DisposalType::values(), true)) {
                    $q->where('disposal_type', $type);
                }
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function historyFor(Asset $asset)
    {
        return $asset->disposals()->latest()->get();
    }

    private function transition(Disposal $disposal, DisposalStatus $target): void
    {
        $current = $disposal->status instanceof DisposalStatus
            ? $disposal->status
            : DisposalStatus::from((string) $disposal->status);

        if (! $current->canTransitionTo($target)) {
            throw new UnprocessableEntityHttpException(
                "Transition impossible : « {$current->label()} » → « {$target->label()} »."
            );
        }
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $count = Disposal::whereYear('created_at', $year)->count() + 1;

        return sprintf('DSP-%d-%04d', $year, $count);
    }
}
