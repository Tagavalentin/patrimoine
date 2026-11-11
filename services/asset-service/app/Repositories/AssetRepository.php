<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Accès aux données des biens : requêtes de lecture filtrées
 * et persistances simples. La logique métier reste dans AssetService.
 */
class AssetRepository
{
    private const SORTABLE = [
        'code', 'designation', 'acquisition_date',
        'acquisition_value', 'status', 'created_at',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Asset::query()
            ->with([
                'category:id,name,code',
                'location:id,name,code',
                'department:id,name,code',
            ])
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $q->where(fn (Builder $w) => $w
                    ->where('designation', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($filters['category_id'] ?? null, fn (Builder $q, int $id) => $q->where('category_id', $id))
            ->when($filters['location_id'] ?? null, fn (Builder $q, int $id) => $q->where('location_id', $id))
            ->when($filters['department_id'] ?? null, fn (Builder $q, int $id) => $q->where('department_id', $id))
            ->when($filters['status'] ?? null, function (Builder $q, string $status): void {
                if (in_array($status, AssetStatus::values(), true)) {
                    $q->where('status', $status);
                }
            })
            ->when($filters['acquisition_date_from'] ?? null, fn (Builder $q, string $d) => $q->whereDate('acquisition_date', '>=', $d))
            ->when($filters['acquisition_date_to'] ?? null, fn (Builder $q, string $d) => $q->whereDate('acquisition_date', '<=', $d))
            ->when($filters['value_min'] ?? null, fn (Builder $q, float $v) => $q->where('acquisition_value', '>=', $v))
            ->when($filters['value_max'] ?? null, fn (Builder $q, float $v) => $q->where('acquisition_value', '<=', $v));

        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true)
            ? $filters['sort']
            : 'code';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function find(int $id): ?Asset
    {
        return Asset::with(['category', 'location', 'department', 'statusHistories'])
            ->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Asset
    {
        return Asset::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Asset $asset, array $data): Asset
    {
        $asset->update($data);

        return $asset->fresh(['category', 'location', 'department']);
    }
}
