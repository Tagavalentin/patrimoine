<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetStatus;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'designation',
        'category_id',
        'location_id',
        'department_id',
        'acquisition_date',
        'acquisition_value',
        'useful_life_years',
        'status',
        'description',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_value' => 'decimal:2',
            'useful_life_years' => 'integer',
            'status' => AssetStatus::class,
            'attachments' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(AssetStatusHistory::class)->latest();
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(Disposal::class)->latest();
    }

    public function isDisposed(): bool
    {
        return $this->status === AssetStatus::Disposed;
    }
}
