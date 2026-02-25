<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\DTOs;

use DateTimeImmutable;
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
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?string $agentId = null,
    ) {}
}
