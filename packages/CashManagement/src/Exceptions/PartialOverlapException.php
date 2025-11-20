<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Exceptions;

use RuntimeException;

/**
 * Partial Overlap Exception
 *
 * Thrown when a statement has partial overlap with existing statements.
 */
class PartialOverlapException extends RuntimeException
{
    /**
     * @param array<string> $overlappingStatementIds
     */
    public function __construct(
        string $message = 'Statement has partial overlap with existing statements',
        private readonly array $overlappingStatementIds = [],
        private readonly ?string $overlapStartDate = null,
        private readonly ?string $overlapEndDate = null
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string>
     */
    public function getOverlappingStatementIds(): array
    {
        return $this->overlappingStatementIds;
    }

    public function getOverlapStartDate(): ?string
    {
        return $this->overlapStartDate;
    }

    public function getOverlapEndDate(): ?string
    {
        return $this->overlapEndDate;
    }

    /**
     * @param array<string> $statementIds
     */
    public static function withDetails(array $statementIds, string $startDate, string $endDate): self
    {
        return new self(
            message: sprintf(
                'Statement period overlaps with %d existing statement(s) from %s to %s',
                count($statementIds),
                $startDate,
                $endDate
            ),
            overlappingStatementIds: $statementIds,
            overlapStartDate: $startDate,
            overlapEndDate: $endDate
        );
    }
}
