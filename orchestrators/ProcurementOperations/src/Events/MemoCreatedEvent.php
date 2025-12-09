<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\MemoReason;
use Nexus\ProcurementOperations\Enums\MemoType;

/**
 * Event published when a credit or debit memo is created.
 */
final readonly class MemoCreatedEvent
{
    public function __construct(
        public string $tenantId,
        public string $memoId,
        public string $memoNumber,
        public string $vendorId,
        public MemoType $type,
        public MemoReason $reason,
        public Money $amount,
        public string $createdBy,
        public \DateTimeImmutable $createdAt,
        public ?string $invoiceId = null,
        public ?string $purchaseOrderId = null,
    ) {}

    /**
     * Check if this is a credit memo.
     */
    public function isCreditMemo(): bool
    {
        return $this->type === MemoType::CREDIT;
    }
}
