<?php

declare(strict_types=1);

namespace Nexus\Budget\Exceptions;

/**
 * Simulation Not Editable Exception
 * 
 * Thrown when attempting to modify a simulation budget in an invalid way.
 */
final class SimulationNotEditableException extends BudgetException
{
    public function __construct(
        private readonly string $simulationId,
        private readonly string $attemptedOperation,
        string $message = '',
        int $code = 400
    ) {
        $message = $message ?: sprintf(
            'Cannot %s simulation budget %s (simulations are read-only for certain operations)',
            $attemptedOperation,
            $simulationId
        );
        parent::__construct($message, $code);
    }

    public function getSimulationId(): string
    {
        return $this->simulationId;
    }

    public function getAttemptedOperation(): string
    {
        return $this->attemptedOperation;
    }
}
