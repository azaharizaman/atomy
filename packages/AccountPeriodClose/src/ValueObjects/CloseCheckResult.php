<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\ValueObjects;

/**
 * Result of a close rule check.
 */
final readonly class CloseCheckResult
{
    public function __construct(
        private bool $passed,
        private ?string $message = null,
        private array $details = [],
    ) {}

    public function hasPassed(): bool
    {
        return $this->passed;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public static function pass(): self
    {
        return new self(passed: true);
    }

    public static function fail(string $message, array $details = []): self
    {
        return new self(
            passed: false,
            message: $message,
            details: $details
        );
    }
}
