<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

interface CashManagementProviderInterface
{
    public function getBankAccountBalance(string $bankAccountId): array;

    public function getBankAccountIdsByTenant(string $tenantId): array;

    public function getTransactionsByDateRange(
        string $bankAccountId,
        string $startDate,
        string $endDate
    ): array;

    public function getCurrentBalance(string $bankAccountId): float;

    public function getCurrency(string $bankAccountId): string;
}
