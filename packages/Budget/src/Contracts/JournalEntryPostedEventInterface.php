<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface JournalEntryPostedEventInterface
{
    public function getJournalEntryId(): string;

    /**
     * @return array<int, JournalEntryLineItemInterface>
     */
    public function getLineItems(): array;
}
