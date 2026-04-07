<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Transaction-aware failure used to roll back onboarding when the Layer 2 result is unsuccessful.
 */
final class CompanyOnboardingFailedException extends RuntimeException
{
    /**
     * @param array<int, array{rule: string, message: string}> $issues
     */
    public function __construct(
        private readonly array $issues,
        string $message = 'Company onboarding failed',
    ) {
        parent::__construct($message);
    }

    /**
     * @param array<int, array{rule: string, message: string}> $issues
     */
    public static function fromIssues(array $issues, ?string $message = null): self
    {
        return new self($issues, $message ?? 'Company onboarding failed');
    }

    /**
     * @return array<int, array{rule: string, message: string}>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }
}
