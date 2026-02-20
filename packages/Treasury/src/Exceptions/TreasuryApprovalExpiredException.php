<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class TreasuryApprovalExpiredException extends TreasuryException
{
    public static function forId(string $approvalId): self
    {
        return new self("Treasury approval has expired: {$approvalId}");
    }
}
