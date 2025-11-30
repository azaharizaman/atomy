<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Exceptions;

final class DuplicateAccountCodeException extends FinanceException
{
    public static function forCode(string $code): self
    {
        return new self("Account with code {$code} already exists");
    }
}
