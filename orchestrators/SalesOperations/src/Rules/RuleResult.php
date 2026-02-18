<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules;

final readonly class RuleResult
{
    private function __construct(
        public bool $passed,
        public string $ruleName,
        public ?string $message = null,
        public array $context = [],
    ) {}

    public static function pass(string $ruleName, ?string $message = null, array $context = []): self
    {
        return new self(
            passed: true,
            ruleName: $ruleName,
            message: $message,
            context: $context,
        );
    }

    public static function fail(string $ruleName, string $message, array $context = []): self
    {
        return new self(
            passed: false,
            ruleName: $ruleName,
            message: $message,
            context: $context,
        );
    }

    public function failed(): bool
    {
        return !$this->passed;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
