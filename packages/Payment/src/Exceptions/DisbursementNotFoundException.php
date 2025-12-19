<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when a disbursement cannot be found.
 */
final class DisbursementNotFoundException extends PaymentException
{
    public function __construct(
        string $disbursementId,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Disbursement with ID "%s" not found', $disbursementId),
            404,
            $previous,
            ['disbursement_id' => $disbursementId],
        );
    }
}
