<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CheckStatus;
use Database\Factories\InventoryLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLine extends Model
{
    /** @use HasFactory<InventoryLineFactory> */
    use HasFactory;

    protected $fillable = [
        'inventory_campaign_id',
        'asset_id',
        'asset_code',
        'asset_designation',
        'asset_status',
        'department_name',
        'location_name',
        'check_status',
        'notes',
        'checked_by',
        'checked_by_name',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'check_status' => CheckStatus::class,
            'checked_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(InventoryCampaign::class, 'inventory_campaign_id');
    }
}
