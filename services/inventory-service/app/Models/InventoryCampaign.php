<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use Database\Factories\InventoryCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryCampaign extends Model
{
    /** @use HasFactory<InventoryCampaignFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'name',
        'department_id',
        'department_name',
        'location_id',
        'location_name',
        'status',
        'started_at',
        'completed_at',
        'created_by',
        'created_by_name',
        'created_by_email',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryLine::class);
    }
}
