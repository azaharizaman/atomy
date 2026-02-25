<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

interface TicketLifecycleWorkflowInterface
{
    public function start(string $ticketId): void;
}
