<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Exceptions;

/**
 * Exception when period cannot be reopened.
 */
final class ReopenNotAllowedException extends PeriodCloseException
{
    public function __construct(
        string $periodId,
        string $reason,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            "Period '{$periodId}' cannot be reopened: {$reason}",
            $code,
            $previous
        );
    }
}
