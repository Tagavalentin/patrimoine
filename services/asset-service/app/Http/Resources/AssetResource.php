<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Asset */
class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'designation' => $this->designation,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'code' => $this->category->code,
            ]),
            'location' => $this->whenLoaded('location', fn () => [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'code' => $this->location->code,
            ]),
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code,
            ] : null),
            'acquisition_date' => $this->acquisition_date?->toDateString(),
            'acquisition_value' => $this->acquisition_value,
            'useful_life_years' => $this->useful_life_years,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'description' => $this->description,
            'attachments' => $this->attachments,
            'status_histories' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories
                ->map(fn ($h) => [
                    'from_status' => $h->from_status?->value,
                    'to_status' => $h->to_status->value,
                    'reason' => $h->reason,
                    'changed_by' => $h->changed_by,
                    'created_at' => $h->created_at?->toIso8601String(),
                ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
