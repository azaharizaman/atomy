<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for GL reconciliation operations.
 */
final readonly class GLReconciliationRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $subledgerType,
        public array $options = [],
    ) {}
}
