<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FiscalPeriod;

/**
 * Result DTO for period generation.
 * 
 * @param array<string> $periodIds Generated period IDs
 */
final class PeriodGenerationResult
{
    /**
     * @param array<string> $periodIds
     */
    public function __construct(
        public readonly bool $success,
        public readonly array $periodIds = [],
        public readonly ?string $error = null,
    ) {}

    public static function success(array $periodIds): self
    {
        return new self(success: true, periodIds: $periodIds);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
