<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\ValueObjects;

/**
 * Result of close readiness validation.
 */
final readonly class CloseReadinessResult
{
    /**
     * @param bool $isReady
     * @param array<CloseValidationIssue> $issues
     * @param array<string> $warnings
     */
    public function __construct(
        private bool $isReady,
        private array $issues = [],
        private array $warnings = [],
    ) {}

    public function isReady(): bool
    {
        return $this->isReady;
    }

    /**
     * @return array<CloseValidationIssue>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasBlockingIssues(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->isBlocking()) {
                return true;
            }
        }
        return false;
    }
}
