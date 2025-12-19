<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

use Nexus\Payment\Enums\DisbursementStatus;

/**
 * Thrown when a disbursement status transition is invalid.
 */
final class InvalidDisbursementStatusException extends PaymentException
{
    public function __construct(
        private readonly DisbursementStatus $currentStatus,
        private readonly DisbursementStatus $targetStatus,
        ?string $disbursementId = null,
        ?\Throwable $previous = null,
    ) {
        $message = sprintf(
            'Cannot transition disbursement from status "%s" to "%s"',
            $this->currentStatus->value,
            $this->targetStatus->value,
        );

        if ($disbursementId !== null) {
            $message = sprintf('[Disbursement: %s] %s', $disbursementId, $message);
        }

        parent::__construct(
            $message,
            400,
            $previous,
            [
                'disbursement_id' => $disbursementId,
                'current_status' => $this->currentStatus->value,
                'target_status' => $this->targetStatus->value,
                'allowed_transitions' => array_map(
                    fn (DisbursementStatus $s) => $s->value,
                    $this->currentStatus->getValidTransitions(),
                ),
            ],
        );
    }

    /**
     * Get the current status.
     */
    public function getCurrentStatus(): DisbursementStatus
    {
        return $this->currentStatus;
    }

    /**
     * Get the target status that was attempted.
     */
    public function getTargetStatus(): DisbursementStatus
    {
        return $this->targetStatus;
    }
}
