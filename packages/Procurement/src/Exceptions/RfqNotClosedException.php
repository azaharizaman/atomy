<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

final class RfqNotClosedException extends ProcurementException
{
    public static function closingDateNotReached(string $requisitionId, \DateTimeImmutable $closingDate): self
    {
        return new self(sprintf(
            "Cannot start comparison run for requisition '%s': RFQ closing date '%s' has not been reached.",
            $requisitionId,
            $closingDate->format('Y-m-d H:i:s'),
        ));
    }

    public static function noClosingDateSet(string $requisitionId): self
    {
        return new self(sprintf(
            "Requisition '%s' has no closing date set; comparison runs require a defined closing boundary.",
            $requisitionId,
        ));
    }
}
