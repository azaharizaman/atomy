<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\Common\ValueObjects\Money;

/**
 * Request to apply a credit memo to an invoice.
 */
final readonly class MemoApplicationRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $memoId Credit memo ID
     * @param string $invoiceId Invoice to apply memo to
     * @param Money $amount Amount to apply
     * @param string $appliedBy User applying the memo
     * @param string|null $notes Application notes
     */
    public function __construct(
        public string $tenantId,
        public string $memoId,
        public string $invoiceId,
        public Money $amount,
        public string $appliedBy,
        public ?string $notes = null,
    ) {}
}
