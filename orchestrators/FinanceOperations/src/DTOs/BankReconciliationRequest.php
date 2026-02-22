<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for bank reconciliation operations.
 */
final readonly class BankReconciliationRequest
{
    public function __construct(
        public string $tenantId,
        public string $bankAccountId,
        public string $periodId,
        public array $options = [],
    ) {}
}
