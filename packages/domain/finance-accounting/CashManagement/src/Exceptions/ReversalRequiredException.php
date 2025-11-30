<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Exceptions;

use RuntimeException;

/**
 * Reversal Required Exception
 *
 * Thrown when a finance reversal workflow is required.
 */
class ReversalRequiredException extends RuntimeException
{
    public function __construct(
        string $message = 'Finance reversal workflow required',
        private readonly ?string $paymentApplicationId = null,
        private readonly ?string $reconciliationId = null
    ) {
        parent::__construct($message);
    }

    public function getPaymentApplicationId(): ?string
    {
        return $this->paymentApplicationId;
    }

    public function getReconciliationId(): ?string
    {
        return $this->reconciliationId;
    }

    public static function forPaymentApplication(string $paymentApplicationId, string $reconciliationId): self
    {
        return new self(
            message: sprintf(
                'Payment application "%s" requires reversal via finance workflow (Reconciliation: %s)',
                $paymentApplicationId,
                $reconciliationId
            ),
            paymentApplicationId: $paymentApplicationId,
            reconciliationId: $reconciliationId
        );
    }
}
