<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    protected $fillable = [
        'asset_id',
        'department_id',
        'responsible_user_id',
        'responsible_name',
        'responsible_email',
        'start_date',
        'end_date',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isActive(): bool
    {
        return $this->end_date === null;
    }

    /**
     * Affectation active (end_date IS NULL) d'un bien.
     */
    public static function activeFor(int $assetId): ?self
    {
        return static::where('asset_id', $assetId)
            ->whereNull('end_date')
            ->latest('start_date')
            ->first();
    }
}
