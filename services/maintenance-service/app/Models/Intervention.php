<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intervention extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'technician_name',
        'description',
        'cost',
        'performed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'performed_at' => 'date',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }
}
