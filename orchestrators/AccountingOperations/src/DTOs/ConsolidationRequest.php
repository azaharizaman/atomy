<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DTOs;

use Nexus\AccountConsolidation\Enums\ConsolidationMethod;
use Nexus\AccountConsolidation\Enums\TranslationMethod;

/**
 * Request DTO for consolidation operations.
 */
final readonly class ConsolidationRequest
{
    /**
     * @param array<string> $entityIds
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $parentEntityId,
        public array $entityIds,
        public ConsolidationMethod $method,
        public TranslationMethod $translationMethod,
        public string $reportingCurrency,
        public bool $eliminateIntercompany = true,
        public bool $calculateNci = true,
        public array $options = [],
    ) {}
}
