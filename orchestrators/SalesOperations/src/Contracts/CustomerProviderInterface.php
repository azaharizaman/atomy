<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface CustomerProviderInterface
{
    public function findById(string $tenantId, string $customerId): ?CustomerInterface;

    public function findByCode(string $tenantId, string $code): ?CustomerInterface;

    public function updateCreditUsed(string $tenantId, string $customerId, float $amount): void;
}
