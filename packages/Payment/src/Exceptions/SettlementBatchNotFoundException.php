<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when a settlement batch is not found.
 */
class SettlementBatchNotFoundException extends PaymentException
{
    public function __construct(
        public readonly string $batchId,
        ?string $message = null
    ) {
        $message ??= sprintf('Settlement batch not found: %s', $batchId);
        parent::__construct($message);
    }
}
