<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'city',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
