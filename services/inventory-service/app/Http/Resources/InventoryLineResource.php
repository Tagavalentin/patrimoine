<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InventoryLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryLine */
class InventoryLineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset' => [
                'id' => $this->asset_id,
                'code' => $this->asset_code,
                'designation' => $this->asset_designation,
                'status' => $this->asset_status,
                'department_name' => $this->department_name,
                'location_name' => $this->location_name,
            ],
            'check_status' => $this->check_status?->value ?? $this->check_status,
            'is_discrepancy' => $this->check_status?->isDiscrepancy() ?? false,
            'notes' => $this->notes,
            'checked_by_name' => $this->checked_by_name,
            'checked_at' => $this->checked_at?->toIso8601String(),
        ];
    }
}
