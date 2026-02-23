<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FiscalPeriod;

/**
 * Result DTO for period validation.
 */
final class PeriodValidationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly bool $isOpen = false,
        public readonly bool $allowsAdjustment = false,
        public readonly bool $isLocked = false,
        public readonly ?string $error = null,
    ) {}

    public static function valid(bool $isOpen, bool $allowsAdjustment, bool $isLocked): self
    {
        return new self(
            valid: true,
            isOpen: $isOpen,
            allowsAdjustment: $allowsAdjustment,
            isLocked: $isLocked,
        );
    }

    public static function invalid(string $error): self
    {
        return new self(valid: false, error: $error);
    }
}
