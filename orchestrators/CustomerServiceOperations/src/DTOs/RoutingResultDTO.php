<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\DTOs;

readonly class RoutingResultDTO
{
    public function __construct(
        public string $ticketId,
        public ?string $agentId,
        public bool $success,
        public string $reason = '',
    ) {}
}
