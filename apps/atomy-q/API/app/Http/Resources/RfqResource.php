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
            'description' => $rfq->description,
            'status' => $rfq->status,
            'project_id' => $rfq->project_id,
            'project_name' => $rfq->relationLoaded('project') ? $rfq->project?->name : null,
            'estimated_value' => $rfq->estimated_value,
            'savings_percentage' => $rfq->savings_percentage,
            'submission_deadline' => $rfq->submission_deadline?->toAtomString(),
            'closing_date' => $rfq->closing_date?->toAtomString(),
            'expected_award_at' => $rfq->expected_award_at?->toAtomString(),
            'technical_review_due_at' => $rfq->technical_review_due_at?->toAtomString(),
            'financial_review_due_at' => $rfq->financial_review_due_at?->toAtomString(),
            'payment_terms' => $rfq->payment_terms,
            'evaluation_method' => $rfq->evaluation_method,
            'created_at' => $rfq->created_at?->toAtomString(),
            'updated_at' => $rfq->updated_at?->toAtomString(),
        ];
    }
}
