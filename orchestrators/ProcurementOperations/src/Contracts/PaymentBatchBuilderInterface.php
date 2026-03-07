<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\PaymentBatchContext;
use Nexus\ProcurementOperations\DTOs\ProcessPaymentRequest;
use Nexus\ProcurementOperations\Exceptions\PaymentException;

/**
 * Contract for building payment batch contexts.
 */
interface PaymentBatchBuilderInterface
{
    /**
     * Build a payment batch context from a payment request.
     *
     * @throws PaymentException If batch cannot be built
     */
    public function buildBatch(ProcessPaymentRequest $request): PaymentBatchContext;
}
