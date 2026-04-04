<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use App\Models\Rfq;
use Nexus\SourcingOperations\Contracts\RfqLifecycleQueryPortInterface;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;

final readonly class AtomyRfqLifecycleQuery implements RfqLifecycleQueryPortInterface
{
    public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord
    {
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(static function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return null;
        }

        return new RfqLifecycleRecord(
            tenantId: (string) $rfq->tenant_id,
            rfqId: (string) $rfq->id,
            status: (string) $rfq->status,
            title: $rfq->title,
            projectId: $rfq->project_id,
            description: $rfq->description,
            estimatedValue: $rfq->estimated_value !== null ? (float) $rfq->estimated_value : null,
            savingsPercentage: $rfq->savings_percentage !== null ? (float) $rfq->savings_percentage : null,
            submissionDeadline: $rfq->submission_deadline?->toAtomString(),
            closingDate: $rfq->closing_date?->toAtomString(),
            expectedAwardAt: $rfq->expected_award_at?->toAtomString(),
            technicalReviewDueAt: $rfq->technical_review_due_at?->toAtomString(),
            financialReviewDueAt: $rfq->financial_review_due_at?->toAtomString(),
            paymentTerms: $rfq->payment_terms,
            evaluationMethod: $rfq->evaluation_method,
        );
    }
}
