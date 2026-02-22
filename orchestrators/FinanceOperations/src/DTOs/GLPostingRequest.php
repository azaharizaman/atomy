<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

use Nexus\FinanceOperations\Enums\SubledgerType;

/**
 * Request DTO for GL posting operations.
 */
final readonly class GLPostingRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public SubledgerType $subledgerType,
        public array $options = [],
    ) {}
}
