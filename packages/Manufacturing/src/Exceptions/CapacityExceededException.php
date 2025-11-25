<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when capacity is exceeded.
 */
class CapacityExceededException extends \RuntimeException
{
    public function __construct(
        string $message = 'Capacity exceeded',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function workCenterOverloaded(
        string $workCenterId,
        string $date,
        float $available,
        float $required
    ): self {
        $excess = $required - $available;
        return new self(
            "Work center '{$workCenterId}' is overloaded on {$date}. " .
            "Available: {$available} hrs, Required: {$required} hrs, Excess: {$excess} hrs"
        );
    }

    public static function periodOverloaded(
        string $workCenterId,
        string $periodStart,
        string $periodEnd,
        float $utilization
    ): self {
        return new self(
            "Work center '{$workCenterId}' is overloaded from {$periodStart} to {$periodEnd}. " .
            "Utilization: " . number_format($utilization, 1) . '%'
        );
    }

    public static function insufficientCapacity(
        string $workCenterId,
        float $required,
        float $available
    ): self {
        return new self(
            "Work center '{$workCenterId}' has insufficient capacity. " .
            "Required: {$required} hrs, Available: {$available} hrs"
        );
    }
}
