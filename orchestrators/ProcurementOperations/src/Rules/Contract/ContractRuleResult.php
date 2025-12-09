<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Contract;

use Nexus\ProcurementOperations\DTOs\ContractSpendContext;

/**
 * Rule result for contract validation rules.
 */
final readonly class ContractRuleResult
{
    private function __construct(
        private bool $passed,
        private string $message,
        private array $metadata = [],
    ) {}

    public static function pass(string $message = 'Validation passed'): self
    {
        return new self(true, $message);
    }

    public static function fail(string $message, array $metadata = []): self
    {
        return new self(false, $message, $metadata);
    }

    public function passed(): bool
    {
        return $this->passed;
    }

    public function failed(): bool
    {
        return !$this->passed;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
