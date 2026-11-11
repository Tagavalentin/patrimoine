<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InventoryCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryCampaign */
class CampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'name' => $this->name,
            'scope' => [
                'department' => $this->department_id ? [
                    'id' => $this->department_id,
                    'name' => $this->department_name,
                ] : null,
                'location' => $this->location_id ? [
                    'id' => $this->location_id,
                    'name' => $this->location_name,
                ] : null,
            ],
            'status' => $this->status?->value ?? $this->status,
            'lines_count' => $this->whenCounted('lines'),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_by' => [
                'id' => $this->created_by,
                'name' => $this->created_by_name,
                'email' => $this->created_by_email,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
