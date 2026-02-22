<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

/**
 * Request DTO for general ledger posting operations.
 *
 * Used to post subledger transactions to the general ledger
 * for a specific period and subledger type.
 *
 * @since 1.0.0
 */
final readonly class GLPostingRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for posting
     * @param string $subledgerType Subledger type: 'receivable', 'payable', 'asset'
     * @param array<string, mixed> $options Additional posting options
     * @param bool $validateOnly Validate without posting
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $subledgerType,
        public array $options = [],
        public bool $validateOnly = false,
    ) {}
}
