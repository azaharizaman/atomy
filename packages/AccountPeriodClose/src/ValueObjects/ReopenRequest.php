<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\ValueObjects;

use Nexus\AccountPeriodClose\Enums\ReopenReason;

/**
 * Request to reopen a closed period.
 */
final readonly class ReopenRequest
{
    public function __construct(
        private string $periodId,
        private string $requestedBy,
        private ReopenReason $reason,
        private string $justification,
        private ?\DateTimeImmutable $reopenUntil = null,
    ) {}

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getRequestedBy(): string
    {
        return $this->requestedBy;
    }

    public function getReason(): ReopenReason
    {
        return $this->reason;
    }

    public function getJustification(): string
    {
        return $this->justification;
    }

    public function getReopenUntil(): ?\DateTimeImmutable
    {
        return $this->reopenUntil;
    }
}
