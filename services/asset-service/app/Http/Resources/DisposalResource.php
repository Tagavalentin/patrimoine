<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Disposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Disposal */
class DisposalResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'asset' => $this->whenLoaded('asset', fn () => [
                'id' => $this->asset->id,
                'code' => $this->asset->code,
                'designation' => $this->asset->designation,
                'status' => $this->asset->status?->value ?? $this->asset->status,
            ]),
            'asset_id' => $this->asset_id,
            'disposal_type' => $this->disposal_type?->value ?? $this->disposal_type,
            'disposal_type_label' => $this->disposal_type?->label(),
            'reason' => $this->reason,
            'estimated_value' => $this->estimated_value !== null ? (float) $this->estimated_value : null,
            'status' => $this->status?->value ?? $this->status,
            'disposal_date' => $this->disposal_date?->toDateString(),
            'sale_value' => $this->sale_value !== null ? (float) $this->sale_value : null,
            'requested_by' => [
                'id' => $this->requested_by,
                'name' => $this->requested_by_name,
                'email' => $this->requested_by_email,
            ],
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'decision_reason' => $this->decision_reason,
            'completed_by' => $this->completed_by,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
