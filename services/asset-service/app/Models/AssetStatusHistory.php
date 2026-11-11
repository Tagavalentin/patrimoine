<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetStatusHistory extends Model
{
    protected $fillable = [
        'asset_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => AssetStatus::class,
            'to_status' => AssetStatus::class,
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
