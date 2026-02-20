<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use Nexus\Treasury\Enums\TreasuryStatus;

/**
 * Treasury Policy Interface
 */
interface TreasuryPolicyInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getName(): string;

    public function getDescription(): ?string;

    public function getStatus(): TreasuryStatus;

    public function getMinimumCashBalance(): float;

    public function getMinimumCashBalanceCurrency(): string;

    public function getMaximumSingleTransaction(): float;

    public function getMaximumSingleTransactionCurrency(): string;

    public function isApprovalRequired(): bool;

    public function getApprovalThreshold(): float;

    public function getApprovalThresholdCurrency(): string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;

    public function withStatus(TreasuryStatus $status): self;
}
