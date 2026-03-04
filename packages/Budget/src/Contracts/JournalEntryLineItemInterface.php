<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Common\ValueObjects\Money;

interface JournalEntryLineItemInterface
{
    public function getAccountId(): string;

    public function getAmount(): Money;
}
