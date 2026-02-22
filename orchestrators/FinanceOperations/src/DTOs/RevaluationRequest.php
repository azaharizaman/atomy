<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for revaluation operations.
 */
final readonly class RevaluationRequest
{
    public function __construct(
        public string $tenantId,
        public string $assetId,
        public float $newValue,
        public array $options = [],
    ) {}
}
