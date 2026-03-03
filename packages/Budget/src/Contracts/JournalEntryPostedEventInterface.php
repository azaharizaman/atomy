<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Common\ValueObjects\Money;

interface JournalEntryPostedEventInterface
{
    public function getJournalEntryId(): string;

    /**
     * @return array<int, array{account_id:string, amount:Money}>
     */
    public function getLineItems(): array;
}
