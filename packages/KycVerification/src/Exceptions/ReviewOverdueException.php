<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Exceptions;

/**
 * Exception thrown when a review is overdue.
 */
class ReviewOverdueException extends KycVerificationException
{
    private string $partyId;

    private \DateTimeImmutable $dueDate;

    private int $daysOverdue;

    public function __construct(
        string $partyId,
        \DateTimeImmutable $dueDate,
        ?\Throwable $previous = null
    ) {
        $this->partyId = $partyId;
        $this->dueDate = $dueDate;

        $now = new \DateTimeImmutable();
        $this->daysOverdue = (int) $now->diff($dueDate)->days;

        parent::__construct(
            sprintf(
                'KYC review for party %s is %d days overdue (due: %s)',
                $partyId,
                $this->daysOverdue,
                $dueDate->format('Y-m-d')
            ),
            0,
            $previous
        );
    }

    public function getPartyId(): string
    {
        return $this->partyId;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getDaysOverdue(): int
    {
        return $this->daysOverdue;
    }

    /**
     * Check if review is critically overdue (>30 days)
     */
    public function isCriticallyOverdue(): bool
    {
        return $this->daysOverdue > 30;
    }
}
