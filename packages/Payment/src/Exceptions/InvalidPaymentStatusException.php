<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

use Nexus\Payment\Enums\PaymentStatus;

/**
 * Thrown when a payment status transition is invalid.
 */
final class InvalidPaymentStatusException extends PaymentException
{
    public function __construct(
        private readonly PaymentStatus $currentStatus,
        private readonly PaymentStatus $targetStatus,
        ?string $paymentId = null,
        ?\Throwable $previous = null,
    ) {
        $message = sprintf(
            'Cannot transition payment from status "%s" to "%s"',
            $this->currentStatus->value,
            $this->targetStatus->value,
        );

        if ($paymentId !== null) {
            $message = sprintf('[Payment: %s] %s', $paymentId, $message);
        }

        parent::__construct(
            $message,
            400,
            $previous,
            [
                'payment_id' => $paymentId,
                'current_status' => $this->currentStatus->value,
                'target_status' => $this->targetStatus->value,
                'allowed_transitions' => array_map(
                    fn (PaymentStatus $s) => $s->value,
                    $this->currentStatus->allowedTransitions(),
                ),
            ],
        );
    }

    /**
     * Get the current status.
     */
    public function getCurrentStatus(): PaymentStatus
    {
        return $this->currentStatus;
    }

    /**
     * Get the target status that was attempted.
     */
    public function getTargetStatus(): PaymentStatus
    {
        return $this->targetStatus;
    }
}
