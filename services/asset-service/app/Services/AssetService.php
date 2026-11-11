<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCounter;
use App\Models\AssetStatusHistory;
use App\Repositories\AssetRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Logique métier des biens : génération du code, transitions
 * d'état tracées, règles de suppression.
 */
class AssetService
{
    public function __construct(private readonly AssetRepository $assets) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId): Asset
    {
        return DB::transaction(function () use ($data, $actorId): Asset {
            $data['code'] = $this->nextCode();
            $data['status'] = AssetStatus::Acquired->value;

            /** @var Asset $asset */
            $asset = $this->assets->create($data);

            $this->recordStatus($asset, null, AssetStatus::Acquired, $actorId, 'Création du bien');

            return $asset->load(['category', 'location', 'department']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Asset $asset, array $data): Asset
    {
        // Le statut ne se modifie que via changeStatus()
        unset($data['status'], $data['code']);

        return $this->assets->update($asset, $data);
    }

    /**
     * Transition d'état validée par la machine à états + historisée.
     */
    public function changeStatus(Asset $asset, AssetStatus $target, ?string $reason, ?int $actorId): Asset
    {
        $current = $asset->status instanceof AssetStatus
            ? $asset->status
            : AssetStatus::from((string) $asset->status);

        if (! $current->canTransitionTo($target)) {
            throw new UnprocessableEntityHttpException(
                "Transition impossible : « {$current->label()} » → « {$target->label()} »."
            );
        }

        return DB::transaction(function () use ($asset, $current, $target, $reason, $actorId): Asset {
            $asset->update(['status' => $target->value]);

            $this->recordStatus($asset, $current, $target, $actorId, $reason);

            return $asset->fresh(['category', 'location', 'department', 'statusHistories']);
        });
    }

    /**
     * Suppression (soft delete) — refusée pour un bien sorti du
     * patrimoine (disposed) afin de préserver la traçabilité.
     */
    public function delete(Asset $asset): void
    {
        if ($asset->isDisposed()) {
            throw new UnprocessableEntityHttpException(
                'Un bien cédé ou sorti du patrimoine ne peut pas être supprimé.'
            );
        }

        $asset->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->assets->paginate($filters);
    }

    public function find(int $id): ?Asset
    {
        return $this->assets->find($id);
    }

    /**
     * Code métier unique : AST-<année>-<séquence sur 4 chiffres>,
     * généré sous verrou pour éviter les collisions.
     */
    private function nextCode(): string
    {
        $year = now()->year;

        /** @var AssetCounter|null $counter */
        $counter = AssetCounter::where('year', $year)->lockForUpdate()->first();

        $next = ($counter?->last_number ?? 0) + 1;

        AssetCounter::updateOrCreate(
            ['year' => $year],
            ['last_number' => $next],
        );

        return sprintf('AST-%d-%04d', $year, $next);
    }

    private function recordStatus(
        Asset $asset,
        ?AssetStatus $from,
        AssetStatus $to,
        ?int $actorId,
        ?string $reason,
    ): void {
        AssetStatusHistory::create([
            'asset_id' => $asset->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'reason' => $reason,
            'changed_by' => $actorId,
        ]);
    }
}
