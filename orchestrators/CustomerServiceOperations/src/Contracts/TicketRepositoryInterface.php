<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

interface TicketRepositoryInterface
{
    public function findById(string $id): ?TicketInterface;

    public function save(TicketInterface $ticket): void;

    /**
     * @return TicketInterface[]
     */
    public function findByCustomer(string $customerId): array;

    /**
     * @return TicketInterface[]
     */
    public function findUnassigned(): array;
}
