<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Result of a rule check.
 */
final readonly class RuleCheckResult
{
    public function __construct(
        public string $ruleName,
        public bool $passed,
        public string $severity,
        public string $message,
        public ?array $details = null,
    ) {}
}
