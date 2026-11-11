<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisposalStatus;
use App\Enums\DisposalType;
use Database\Factories\DisposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disposal extends Model
{
    /** @use HasFactory<DisposalFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_id',
        'reference',
        'disposal_type',
        'reason',
        'estimated_value',
        'status',
        'disposal_date',
        'sale_value',
        'requested_by',
        'requested_by_name',
        'requested_by_email',
        'approved_by',
        'approved_at',
        'decision_reason',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'disposal_type' => DisposalType::class,
            'status' => DisposalStatus::class,
            'estimated_value' => 'decimal:2',
            'sale_value' => 'decimal:2',
            'disposal_date' => 'date',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
