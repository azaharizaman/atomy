<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Vendor;

/**
 * Result value object for vendor rule validation.
 */
final readonly class VendorRuleResult
{
    private function __construct(
        public bool $passed,
        public ?string $failureReason = null,
        public ?string $failureCode = null,
    ) {}

    /**
     * Create a passing result.
     */
    public static function pass(): self
    {
        return new self(passed: true);
    }

    /**
     * Create a failing result with reason.
     */
    public static function fail(string $reason, ?string $code = null): self
    {
        return new self(
            passed: false,
            failureReason: $reason,
            failureCode: $code
        );
    }

    /**
     * Check if the rule passed.
     */
    public function passed(): bool
    {
        return $this->passed;
    }

    /**
     * Check if the rule failed.
     */
    public function failed(): bool
    {
        return !$this->passed;
    }
}
