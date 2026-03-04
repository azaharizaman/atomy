<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface JournalEntryReversedEventInterface
{
    public function getJournalEntryId(): string;

    public function getOriginalJournalEntryId(): string;
}
