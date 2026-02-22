<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

use Nexus\FinanceOperations\Enums\SubledgerType;

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
     * @param array<SubledgerType> $subledgerTypes Subledger types to check
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $subledgerTypes = [SubledgerType::RECEIVABLE, SubledgerType::PAYABLE, SubledgerType::ASSET],
    ) {}
}
