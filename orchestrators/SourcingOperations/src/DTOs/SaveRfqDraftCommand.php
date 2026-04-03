<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

use Nexus\SourcingOperations\Exceptions\CommandValidationException;

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
            throw new CommandValidationException('Tenant id cannot be empty.');
        }

        if (trim($rfqId) === '') {
            throw new CommandValidationException('RFQ id cannot be empty.');
        }

        $this->tenantId = trim($tenantId);
        $this->rfqId = trim($rfqId);
        
        $this->title = ($title !== null && trim($title) !== '') ? trim($title) : null;
        $this->description = ($description !== null && trim($description) !== '') ? trim($description) : null;
        $this->projectId = ($projectId !== null && trim($projectId) !== '') ? trim($projectId) : null;
        $this->estimatedValue = $estimatedValue;
        $this->savingsPercentage = $savingsPercentage;
        $this->submissionDeadline = ($submissionDeadline !== null && trim($submissionDeadline) !== '') ? trim($submissionDeadline) : null;
        $this->closingDate = ($closingDate !== null && trim($closingDate) !== '') ? trim($closingDate) : null;
        $this->expectedAwardAt = ($expectedAwardAt !== null && trim($expectedAwardAt) !== '') ? trim($expectedAwardAt) : null;
        $this->technicalReviewDueAt = ($technicalReviewDueAt !== null && trim($technicalReviewDueAt) !== '') ? trim($technicalReviewDueAt) : null;
        $this->financialReviewDueAt = ($financialReviewDueAt !== null && trim($financialReviewDueAt) !== '') ? trim($financialReviewDueAt) : null;
        $this->paymentTerms = ($paymentTerms !== null && trim($paymentTerms) !== '') ? trim($paymentTerms) : null;
        $this->evaluationMethod = ($evaluationMethod !== null && trim($evaluationMethod) !== '') ? trim($evaluationMethod) : null;
    }
}
