<?php

declare(strict_types=1);

namespace Nexus\Treasury\Exceptions;

final class InvalidInvestmentStateException extends TreasuryException
{
    public static function forId(string $investmentId, string $state): self
    {
        return new self("Investment {$investmentId} is in invalid state: {$state}");
    }

    public static function notActive(string $investmentId): self
    {
        return new self("Investment {$investmentId} is not active");
    }

    public static function alreadyMatured(string $investmentId): self
    {
        return new self("Investment {$investmentId} has already matured");
    }
}
