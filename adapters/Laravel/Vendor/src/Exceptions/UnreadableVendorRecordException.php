<?php

declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Exceptions;

use DomainException;

final class UnreadableVendorRecordException extends DomainException
{
    public static function missingField(string $field, string $vendorId): self
    {
        return new self(sprintf('Unreadable vendor record %s: missing %s.', $vendorId, $field));
    }

    public static function invalidStatus(string $status, string $vendorId): self
    {
        return new self(sprintf('Unreadable vendor record %s: invalid status %s.', $vendorId, $status));
    }

    public static function invalidField(string $field, string $vendorId): self
    {
        return new self(sprintf('Unreadable vendor record %s: invalid %s.', $vendorId, $field));
    }
}
