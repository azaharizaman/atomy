<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\MemoReason;
use Nexus\ProcurementOperations\Enums\MemoStatus;
use Nexus\ProcurementOperations\Enums\MemoType;

/**
 * Entity interface for credit/debit memos.
 */
interface MemoInterface
{
    public function getId(): string;

    public function getNumber(): string;

    public function getType(): MemoType;

    public function getReason(): MemoReason;

    public function getStatus(): MemoStatus;

    public function getVendorId(): string;

    public function getAmount(): Money;

    public function getAppliedAmount(): Money;

    public function getRemainingAmount(): Money;

    public function getDescription(): string;

    public function getInvoiceId(): ?string;

    public function getPurchaseOrderId(): ?string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getApprovedAt(): ?\DateTimeImmutable;

    public function getApprovedBy(): ?string;

    public function isFullyApplied(): bool;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
