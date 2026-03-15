<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Adapters;

use Nexus\ProjectManagementOperations\Contracts\ReceivablePersistInterface;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\Common\ValueObjects\Money;

/**
 * Implements orchestrator ReceivablePersistInterface using Nexus\Receivable.
 */
final readonly class ReceivablePersistAdapter implements ReceivablePersistInterface
{
    public function __construct(
        private ReceivableManagerInterface $receivableManager,
    ) {
    }

    public function createDraftInvoice(
        string $tenantId,
        string $customerId,
        array $lines
    ): string {
        $invoiceData = [
            'customer_id' => $customerId,
            'lines' => [],
        ];
        foreach ($lines as $line) {
            $amount = $line['amount'] ?? null;
            if (!$amount instanceof Money) {
                throw new \InvalidArgumentException(
                    'Each invoice line must have an "amount" key with a Nexus\Common\ValueObjects\Money instance.'
                );
            }
            $invoiceData['lines'][] = [
                'description' => $line['description'] ?? '',
                'amount' => $amount->getAmountInMinorUnits(),
                'currency' => $amount->getCurrency(),
            ];
        }
        $invoice = $this->receivableManager->createInvoice($tenantId, $invoiceData);
        return $invoice->getId();
    }
}
