<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Exceptions;

/**
 * Exception when period is not ready to be closed.
 */
final class PeriodNotReadyException extends PeriodCloseException
{
    public function __construct(
        string $periodId,
        array $issues = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $issueCount = count($issues);
        $message = "Period '{$periodId}' is not ready to close. {$issueCount} issue(s) found.";

        parent::__construct($message, $code, $previous);
    }
}
