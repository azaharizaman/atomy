<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

/**
 * Request DTO for GL consistency check operations.
 *
 * Used to verify consistency between subledgers and
 * general ledger control accounts.
 *
 * @since 1.0.0
 */
final readonly class ConsistencyCheckRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period to check
     * @param array<string> $subledgerTypes Subledger types to check: 'receivable', 'payable', 'asset'
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $subledgerTypes = ['receivable', 'payable', 'asset'],
    ) {}
}
