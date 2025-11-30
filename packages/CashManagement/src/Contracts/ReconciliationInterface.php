<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use DateTimeImmutable;
use Nexus\CashManagement\Enums\MatchingConfidence;
use Nexus\CashManagement\Enums\ReconciliationStatus;
use Nexus\CashManagement\ValueObjects\AIModelVersion;

/**
 * Reconciliation Interface
 *
 * Represents a reconciliation match between bank transaction and ERP record.
 */
interface ReconciliationInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getBankTransactionId(): string;

    public function getMatchedEntityType(): string;

    public function getMatchedEntityId(): string;

    public function getStatus(): ReconciliationStatus;

    public function getMatchingConfidence(): MatchingConfidence;

    public function getAiModelVersion(): ?AIModelVersion;

    public function getAmountVariance(): string;

    public function getReconciledAt(): ?DateTimeImmutable;

    public function getReconciledBy(): ?string;

    public function getNotes(): ?string;

    /**
     * Check if this is a high-confidence match
     */
    public function isHighConfidence(): bool;

    /**
     * Check if reconciliation is final
     */
    public function isFinal(): bool;
}
