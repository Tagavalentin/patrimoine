<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Assignment */
class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset' => $this->whenLoaded('asset', fn () => [
                'id' => $this->asset->id,
                'code' => $this->asset->code,
                'designation' => $this->asset->designation,
                'status' => $this->asset->status?->value,
            ]),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code,
            ]),
            'responsible' => [
                'user_id' => $this->responsible_user_id,
                'name' => $this->responsible_name,
                'email' => $this->responsible_email,
            ],
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_active' => $this->isActive(),
            'reason' => $this->reason,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
