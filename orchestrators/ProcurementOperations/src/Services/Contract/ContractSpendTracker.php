<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Contract;

use Nexus\ProcurementOperations\Contracts\ContractSpendTrackerInterface;
use Nexus\ProcurementOperations\DTOs\ContractSpendContext;
use Nexus\Procurement\Contracts\BlanketPurchaseOrderQueryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for tracking cumulative spend against blanket POs/contracts.
 *
 * This service wraps the BlanketPurchaseOrderQueryInterface from the atomic
 * Procurement package and provides spend tracking functionality.
 */
final readonly class ContractSpendTracker implements ContractSpendTrackerInterface
{
    public function __construct(
        private BlanketPurchaseOrderQueryInterface $blanketPoQuery,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getSpendContext(string $blanketPoId): ?ContractSpendContext
    {
        $blanketPo = $this->blanketPoQuery->findById($blanketPoId);
        
        if ($blanketPo === null) {
            $this->logger->warning('Blanket PO not found', ['blanket_po_id' => $blanketPoId]);
            return null;
        }

        return new ContractSpendContext(
            blanketPoId: $blanketPo->getId(),
            blanketPoNumber: $blanketPo->getNumber(),
            vendorId: $blanketPo->getVendorId(),
            maxAmountCents: $blanketPo->getMaxAmountCents(),
            currentSpendCents: $blanketPo->getCurrentSpendCents(),
            pendingAmountCents: $blanketPo->getPendingAmountCents(),
            currency: $blanketPo->getCurrency(),
            effectiveFrom: $blanketPo->getEffectiveFrom(),
            effectiveTo: $blanketPo->getEffectiveTo(),
            status: $blanketPo->getStatus(),
            minOrderAmountCents: $blanketPo->getMinOrderAmountCents(),
            warningThresholdPercent: $blanketPo->getWarningThresholdPercent() ?? 80,
            allowedCategoryIds: $blanketPo->getAllowedCategoryIds(),
            releaseOrderCount: $blanketPo->getReleaseOrderCount(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function recordSpend(string $blanketPoId, int $amountCents, string $releaseOrderId): int
    {
        // This method updates cumulative spend via the repository
        // The actual implementation would call a persist interface
        $context = $this->getSpendContext($blanketPoId);
        
        if ($context === null) {
            throw new \RuntimeException("Blanket PO {$blanketPoId} not found");
        }

        $newSpend = $context->currentSpendCents + $amountCents;

        $this->logger->info('Recording spend against blanket PO', [
            'blanket_po_id' => $blanketPoId,
            'release_order_id' => $releaseOrderId,
            'amount_cents' => $amountCents,
            'new_cumulative_spend_cents' => $newSpend,
        ]);

        return $newSpend;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseSpend(string $blanketPoId, int $amountCents, string $releaseOrderId): int
    {
        $context = $this->getSpendContext($blanketPoId);
        
        if ($context === null) {
            throw new \RuntimeException("Blanket PO {$blanketPoId} not found");
        }

        $newSpend = max(0, $context->currentSpendCents - $amountCents);

        $this->logger->info('Reversing spend on blanket PO', [
            'blanket_po_id' => $blanketPoId,
            'release_order_id' => $releaseOrderId,
            'amount_cents' => $amountCents,
            'new_cumulative_spend_cents' => $newSpend,
        ]);

        return $newSpend;
    }

    /**
     * {@inheritDoc}
     */
    public function getApproachingLimit(string $tenantId, int $warningThresholdPercent = 80): array
    {
        $blanketPos = $this->blanketPoQuery->findByTenant($tenantId, status: 'ACTIVE');
        $approaching = [];

        foreach ($blanketPos as $blanketPo) {
            $percent = $blanketPo->getMaxAmountCents() > 0
                ? (int) (($blanketPo->getCurrentSpendCents() * 100) / $blanketPo->getMaxAmountCents())
                : 0;

            if ($percent >= $warningThresholdPercent) {
                $approaching[] = new ContractSpendContext(
                    blanketPoId: $blanketPo->getId(),
                    blanketPoNumber: $blanketPo->getNumber(),
                    vendorId: $blanketPo->getVendorId(),
                    maxAmountCents: $blanketPo->getMaxAmountCents(),
                    currentSpendCents: $blanketPo->getCurrentSpendCents(),
                    pendingAmountCents: $blanketPo->getPendingAmountCents(),
                    currency: $blanketPo->getCurrency(),
                    effectiveFrom: $blanketPo->getEffectiveFrom(),
                    effectiveTo: $blanketPo->getEffectiveTo(),
                    status: $blanketPo->getStatus(),
                );
            }
        }

        return $approaching;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiringSoon(string $tenantId, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $blanketPos = $this->blanketPoQuery->findExpiring($tenantId, $from, $to);
        $expiring = [];

        foreach ($blanketPos as $blanketPo) {
            $expiring[] = new ContractSpendContext(
                blanketPoId: $blanketPo->getId(),
                blanketPoNumber: $blanketPo->getNumber(),
                vendorId: $blanketPo->getVendorId(),
                maxAmountCents: $blanketPo->getMaxAmountCents(),
                currentSpendCents: $blanketPo->getCurrentSpendCents(),
                pendingAmountCents: $blanketPo->getPendingAmountCents(),
                currency: $blanketPo->getCurrency(),
                effectiveFrom: $blanketPo->getEffectiveFrom(),
                effectiveTo: $blanketPo->getEffectiveTo(),
                status: $blanketPo->getStatus(),
            );
        }

        return $expiring;
    }
}
