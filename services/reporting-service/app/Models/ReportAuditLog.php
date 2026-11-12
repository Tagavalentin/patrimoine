<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportAuditLog extends Model
{
    public const UPDATED_AT = null; // journal en ajout seul

    protected $fillable = [
        'report_type',
        'format',
        'filters',
        'rows_count',
        'generated_by',
        'generated_by_name',
        'generated_by_email',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'rows_count' => 'integer',
        ];
    }
}
