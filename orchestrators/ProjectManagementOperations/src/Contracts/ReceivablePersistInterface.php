<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

interface ReceivablePersistInterface
{
    /**
     * Create a draft invoice
     * @param array<array{description: string, amount: \Nexus\Common\ValueObjects\Money}> $lines
     */
    public function createDraftInvoice(
        string $tenantId,
        string $customerId,
        array $lines
    ): string;
}
