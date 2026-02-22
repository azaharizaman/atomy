<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Event dispatched when GL reconciliation is completed.
 *
 * @since 1.0.0
 */
final readonly class GLReconciliationCompletedEvent
{
    /**
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @param string $subledgerType The subledger type (e.g., 'bank', 'accounts_payable')
     * @param bool $isReconciled Whether the reconciliation was successful
     * @param array<int, array<string, mixed>> $discrepancies List of discrepancies found
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $subledgerType,
        public bool $isReconciled,
        public array $discrepancies = [],
    ) {}
}
