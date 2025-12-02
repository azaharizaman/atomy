<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\ValueObjects;

use Nexus\AccountPeriodClose\Enums\ValidationSeverity;

/**
 * A validation issue found during close readiness check.
 */
final readonly class CloseValidationIssue
{
    public function __construct(
        private string $ruleId,
        private string $message,
        private ValidationSeverity $severity,
        private ?string $resolution = null,
    ) {}

    public function getRuleId(): string
    {
        return $this->ruleId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSeverity(): ValidationSeverity
    {
        return $this->severity;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function isBlocking(): bool
    {
        return $this->severity === ValidationSeverity::ERROR
            || $this->severity === ValidationSeverity::CRITICAL;
    }
}
