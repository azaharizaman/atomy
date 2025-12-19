<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

use Nexus\Payment\Enums\SettlementBatchStatus;

/**
 * Thrown when an invalid settlement batch status transition is attempted.
 */
class InvalidSettlementBatchStatusException extends PaymentException
{
    public function __construct(
        public readonly SettlementBatchStatus $currentStatus,
        public readonly SettlementBatchStatus $attemptedStatus,
        ?string $message = null
    ) {
        $message ??= sprintf(
            'Cannot transition settlement batch from %s to %s',
            $currentStatus->value,
            $attemptedStatus->value
        );

        parent::__construct($message);
    }

    /**
     * Get valid transitions from the current status.
     *
     * @return array<SettlementBatchStatus>
     */
    public function getValidTransitions(): array
    {
        return $this->currentStatus->getValidTransitions();
    }
}
