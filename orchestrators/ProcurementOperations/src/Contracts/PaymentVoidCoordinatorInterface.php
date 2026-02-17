<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\PaymentVoidRequest;
use Nexus\ProcurementOperations\DTOs\PaymentResult;

/**
 * Contract for payment reversal and voiding coordination.
 */
interface PaymentVoidCoordinatorInterface
{
    /**
     * Void an existing payment and optionally reverse accounting.
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PaymentException
     */
    public function voidPayment(PaymentVoidRequest $request): PaymentResult;
}
