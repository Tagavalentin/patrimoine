<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MaintenanceRequest */
class MaintenanceRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'asset' => [
                'id' => $this->asset_id,
                'code' => $this->asset_code,
                'designation' => $this->asset_designation,
                'status' => $this->asset_status,
            ],
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status?->value ?? $this->status,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'requested_by' => [
                'id' => $this->requested_by,
                'name' => $this->requested_by_name,
                'email' => $this->requested_by_email,
            ],
            'assigned_to_name' => $this->assigned_to_name,
            'interventions' => InterventionResource::collection($this->whenLoaded('interventions')),
            'total_cost' => (float) ($this->interventions_sum_cost ?? 0),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
