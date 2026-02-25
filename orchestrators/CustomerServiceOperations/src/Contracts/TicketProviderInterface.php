<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

interface TicketProviderInterface
{
    public function findById(string $id): ?TicketInterface;
    public function save(TicketInterface $ticket): void;
    public function findByCustomer(string $customerId): array;
    public function findUnassigned(): array;
}
