<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Exceptions;

/**
 * Exception for elimination processing errors.
 */
final class EliminationException extends ConsolidationException
{
    public function __construct(
        string $ruleId,
        string $reason,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct("Elimination error in rule '{$ruleId}': {$reason}", $code, $previous);
    }
}
