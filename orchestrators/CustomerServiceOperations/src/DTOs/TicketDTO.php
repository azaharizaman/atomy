<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\DTOs;

use Nexus\CustomerServiceOperations\Enums\TicketStatus;
use Nexus\CustomerServiceOperations\Enums\TicketPriority;

readonly class TicketDTO
{
    public function __construct(
        public string $id,
        public string $subject,
        public string $description,
        public TicketStatus $status,
        public TicketPriority $priority,
        public string $customerId,
        public ?string $agentId = null,
    ) {}
}
