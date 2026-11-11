<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MaintenanceStatus;
use Database\Factories\MaintenanceRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRequest extends Model
{
    /** @use HasFactory<MaintenanceRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'asset_id',
        'asset_code',
        'asset_designation',
        'asset_status',
        'asset_previous_status',
        'description',
        'priority',
        'status',
        'scheduled_date',
        'started_at',
        'completed_at',
        'requested_by',
        'requested_by_name',
        'requested_by_email',
        'assigned_to',
        'assigned_to_name',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => MaintenanceStatus::class,
        ];
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }
}
