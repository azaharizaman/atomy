<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Exceptions;

use Throwable;

final class DuplicateRfqNumberException extends SourcingOperationsException
{
    public static function fromStorageFailure(string $tenantId, string $sourceRfqId, Throwable $previous): self
    {
        return new self(
            sprintf(
                'Unable to allocate a unique duplicate RFQ number for source RFQ "%s" in tenant "%s".',
                trim($sourceRfqId),
                trim($tenantId),
            ),
            previous: $previous,
        );
    }

    public static function afterRetries(string $tenantId, string $sourceRfqId, ?Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'Unable to create a duplicate RFQ for source RFQ "%s" in tenant "%s" after retrying RFQ number allocation.',
                trim($sourceRfqId),
                trim($tenantId),
            ),
            previous: $previous,
        );
    }

    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
