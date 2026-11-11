<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Intervention;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Intervention */
class InterventionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'maintenance_request_id' => $this->maintenance_request_id,
            'technician_name' => $this->technician_name,
            'description' => $this->description,
            'cost' => (float) $this->cost,
            'performed_at' => $this->performed_at?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
