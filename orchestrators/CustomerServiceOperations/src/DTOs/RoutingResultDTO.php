<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\DTOs;

readonly class RoutingResultDTO
{
    public function __construct(
        public string $ticketId,
        public bool $success,
        public ?string $agentId = null,
        public string $reason = '',
    ) {}
}
