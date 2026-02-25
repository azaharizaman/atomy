<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

use Nexus\CustomerServiceOperations\Enums\TicketStatus;
use Nexus\CustomerServiceOperations\Enums\TicketPriority;

interface TicketInterface
{
    public function getId(): string;
    public function getSubject(): string;
    public function getDescription(): string;
    public function getStatus(): TicketStatus;
    public function getPriority(): TicketPriority;
    public function getCustomerId(): string;
    public function getAgentId(): ?string;
    public function getCreatedAt(): \DateTimeImmutable;
    public function getUpdatedAt(): \DateTimeImmutable;
}
