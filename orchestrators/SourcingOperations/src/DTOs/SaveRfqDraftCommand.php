<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

final readonly class SaveRfqDraftCommand
{
    public string $tenantId;
    public string $rfqId;
    public ?string $title;
    public ?string $description;
    public ?string $projectId;
    public ?float $estimatedValue;
    public ?float $savingsPercentage;
    public ?string $submissionDeadline;
    public ?string $closingDate;
    public ?string $expectedAwardAt;
    public ?string $technicalReviewDueAt;
    public ?string $financialReviewDueAt;
    public ?string $paymentTerms;
    public ?string $evaluationMethod;

    public function __construct(
        string $tenantId,
        string $rfqId,
        ?string $title = null,
        ?string $description = null,
        ?string $projectId = null,
        ?float $estimatedValue = null,
        ?float $savingsPercentage = null,
        ?string $submissionDeadline = null,
        ?string $closingDate = null,
        ?string $expectedAwardAt = null,
        ?string $technicalReviewDueAt = null,
        ?string $financialReviewDueAt = null,
        ?string $paymentTerms = null,
        ?string $evaluationMethod = null,
    ) {
        if (trim($tenantId) === '') {
            throw new \InvalidArgumentException('Tenant id cannot be empty.');
        }

        if (trim($rfqId) === '') {
            throw new \InvalidArgumentException('RFQ id cannot be empty.');
        }

        $this->tenantId = trim($tenantId);
        $this->rfqId = trim($rfqId);
        $this->title = $title !== null ? trim($title) : null;
        $this->description = $description !== null ? trim($description) : null;
        $this->projectId = $projectId !== null ? trim($projectId) : null;
        $this->estimatedValue = $estimatedValue;
        $this->savingsPercentage = $savingsPercentage;
        $this->submissionDeadline = $submissionDeadline !== null ? trim($submissionDeadline) : null;
        $this->closingDate = $closingDate !== null ? trim($closingDate) : null;
        $this->expectedAwardAt = $expectedAwardAt !== null ? trim($expectedAwardAt) : null;
        $this->technicalReviewDueAt = $technicalReviewDueAt !== null ? trim($technicalReviewDueAt) : null;
        $this->financialReviewDueAt = $financialReviewDueAt !== null ? trim($financialReviewDueAt) : null;
        $this->paymentTerms = $paymentTerms !== null ? trim($paymentTerms) : null;
        $this->evaluationMethod = $evaluationMethod !== null ? trim($evaluationMethod) : null;
    }
}
