<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use App\Models\Rfq;
use Carbon\CarbonImmutable;
use Nexus\Sourcing\ValueObjects\RfqBulkAction;
use Nexus\SourcingOperations\Contracts\RfqLifecyclePersistPortInterface;
use Nexus\SourcingOperations\DTOs\DuplicateRfqCommand;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;
use Nexus\SourcingOperations\DTOs\SaveRfqDraftCommand;
use Nexus\SourcingOperations\DTOs\TransitionRfqStatusCommand;

final readonly class AtomyRfqLifecyclePersist implements RfqLifecyclePersistPortInterface
{
    /**
     * @param array<int, mixed> $lineItems
     */
    public function createDuplicate(RfqLifecycleRecord $sourceRfq, DuplicateRfqCommand $command, array $lineItems): RfqLifecycleRecord
    {
        $source = $this->findModel($command->tenantId, $sourceRfq->rfqId);

        $rfq = new Rfq();
        $rfq->tenant_id = $command->tenantId;
        $rfq->owner_id = $source->owner_id;
        $rfq->rfq_number = $this->nextRfqNumber($command->tenantId);
        $rfq->title = $source->title;
        $rfq->description = $source->description;
        $rfq->category = $source->category;
        $rfq->department = $source->department;
        $rfq->status = 'draft';
        $rfq->project_id = $source->project_id;
        $rfq->estimated_value = $source->estimated_value;
        $rfq->savings_percentage = $source->savings_percentage;
        $rfq->submission_deadline = $source->submission_deadline;
        $rfq->closing_date = $source->closing_date;
        $rfq->expected_award_at = $source->expected_award_at;
        $rfq->technical_review_due_at = $source->technical_review_due_at;
        $rfq->financial_review_due_at = $source->financial_review_due_at;
        $rfq->payment_terms = $source->payment_terms;
        $rfq->evaluation_method = $source->evaluation_method;
        $rfq->save();

        return $this->toRecord($rfq);
    }

    public function saveDraft(RfqLifecycleRecord $rfq, SaveRfqDraftCommand $command): RfqLifecycleRecord
    {
        $model = $this->findModel($command->tenantId, $rfq->rfqId);

        $model->title = $rfq->title;
        $model->description = $rfq->description;
        $model->project_id = $rfq->projectId;
        $model->estimated_value = $rfq->estimatedValue;
        $model->savings_percentage = $rfq->savingsPercentage;
        $model->submission_deadline = $this->parseDate($rfq->submissionDeadline);
        $model->closing_date = $this->parseDate($rfq->closingDate);
        $model->expected_award_at = $this->parseDate($rfq->expectedAwardAt);
        $model->technical_review_due_at = $this->parseDate($rfq->technicalReviewDueAt);
        $model->financial_review_due_at = $this->parseDate($rfq->financialReviewDueAt);
        $model->payment_terms = $rfq->paymentTerms;
        $model->evaluation_method = $rfq->evaluationMethod;
        $model->save();

        return $this->toRecord($model);
    }

    public function transitionStatus(RfqLifecycleRecord $rfq, TransitionRfqStatusCommand $command): RfqLifecycleRecord
    {
        $model = $this->findModel($command->tenantId, $rfq->rfqId);
        $model->status = $command->status;
        $model->save();

        return $this->toRecord($model);
    }

    /**
     * @param array<int, string> $rfqIds
     */
    public function applyBulkAction(string $tenantId, RfqBulkAction $action, array $rfqIds): int
    {
        $status = match ($action->value()) {
            'close' => 'closed',
            'cancel' => 'cancelled',
            default => null,
        };

        if ($status === null) {
            return 0;
        }

        return Rfq::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $rfqIds)
            ->update(['status' => $status]);
    }

    private function findModel(string $tenantId, string $rfqId): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $rfqId)
            ->firstOrFail();

        return $rfq;
    }

    private function nextRfqNumber(string $tenantId): string
    {
        $nextSeq = (int) Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_number', 'like', 'RFQ-%')
            ->count() + 1;

        return sprintf('RFQ-%s-%04d', date('Y'), $nextSeq);
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return CarbonImmutable::parse($value);
    }

    private function toRecord(Rfq $rfq): RfqLifecycleRecord
    {
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
