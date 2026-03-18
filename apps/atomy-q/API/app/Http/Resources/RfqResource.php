<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Rfq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Rfq
 */
final class RfqResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Rfq $rfq */
        $rfq = $this->resource;

        return [
            'id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
            'rfq_number' => $rfq->rfq_number,
            'title' => $rfq->title,
            'status' => $rfq->status,
            'project_id' => $rfq->project_id,
            'project_name' => $rfq->relationLoaded('project') ? $rfq->project?->name : null,
            'created_at' => $rfq->created_at?->toAtomString(),
            'updated_at' => $rfq->updated_at?->toAtomString(),
        ];
    }
}

