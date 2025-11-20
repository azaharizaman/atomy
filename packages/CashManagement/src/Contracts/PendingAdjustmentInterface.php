<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use DateTimeImmutable;
use Nexus\CashManagement\ValueObjects\AIModelVersion;

/**
 * Pending Adjustment Interface
 *
 * Represents an unmatched bank transaction requiring GL account classification.
 */
interface PendingAdjustmentInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getBankTransactionId(): string;

    public function getSuggestedGlAccount(): ?string;

    public function getGlAccount(): ?string;

    public function getAmount(): string;

    public function getDescription(): string;

    public function getAiModelVersion(): ?AIModelVersion;

    public function getCorrectionRecordedAt(): ?DateTimeImmutable;

    public function getWorkflowInstanceId(): ?string;

    public function getJournalEntryId(): ?string;

    public function isPosted(): bool;

    public function getPostedAt(): ?DateTimeImmutable;

    public function getPostedBy(): ?string;

    /**
     * Check if user override the AI suggestion
     */
    public function hasUserCorrection(): bool;
}
