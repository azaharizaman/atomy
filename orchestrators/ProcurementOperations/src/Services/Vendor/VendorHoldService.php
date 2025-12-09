<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Vendor;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Party\Contracts\VendorPersistInterface;
use Nexus\Party\Contracts\VendorQueryInterface;
use Nexus\ProcurementOperations\DTOs\VendorHoldRequest;
use Nexus\ProcurementOperations\Enums\VendorHoldReason;
use Nexus\ProcurementOperations\Events\VendorBlockedEvent;
use Nexus\ProcurementOperations\Events\VendorUnblockedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for managing vendor holds.
 *
 * Handles applying and removing holds on vendors, which block
 * PO creation and/or payment processing.
 */
final readonly class VendorHoldService
{
    public function __construct(
        private VendorQueryInterface $vendorQuery,
        private VendorPersistInterface $vendorPersist,
        private EventDispatcherInterface $eventDispatcher,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Apply a hold to a vendor.
     *
     * @throws \RuntimeException If vendor not found
     */
    public function applyHold(VendorHoldRequest $request): void
    {
        $vendor = $this->vendorQuery->findById($request->vendorId);

        if ($vendor === null) {
            throw new \RuntimeException("Vendor {$request->vendorId} not found");
        }

        $this->logger->info('Applying hold to vendor', [
            'vendor_id' => $request->vendorId,
            'reason' => $request->reason->value,
            'applied_by' => $request->appliedBy,
            'is_hard_block' => $request->isHardBlock(),
        ]);

        // Apply the hold
        $vendor->addHold(
            reason: $request->reason->value,
            appliedBy: $request->appliedBy,
            notes: $request->notes,
            effectiveUntil: $request->effectiveUntil
        );

        $this->vendorPersist->update($vendor);

        // Dispatch event
        $this->eventDispatcher->dispatch(new VendorBlockedEvent(
            tenantId: $request->tenantId,
            vendorId: $request->vendorId,
            reason: $request->reason,
            blockedBy: $request->appliedBy,
            blockedAt: new \DateTimeImmutable(),
            notes: $request->notes,
            effectiveUntil: $request->effectiveUntil
        ));

        // Audit log
        $this->auditLogger->log(
            entityId: $request->vendorId,
            action: 'vendor_hold_applied',
            description: sprintf(
                'Hold applied to vendor: %s (%s)',
                $request->reason->description(),
                $request->isHardBlock() ? 'HARD BLOCK' : 'SOFT BLOCK'
            ),
            metadata: [
                'reason' => $request->reason->value,
                'applied_by' => $request->appliedBy,
                'notes' => $request->notes,
                'effective_until' => $request->effectiveUntil?->format('Y-m-d'),
            ]
        );
    }

    /**
     * Remove a hold from a vendor.
     *
     * @throws \RuntimeException If vendor not found
     */
    public function removeHold(
        string $tenantId,
        string $vendorId,
        VendorHoldReason $reason,
        string $removedBy,
        ?string $notes = null
    ): void {
        $vendor = $this->vendorQuery->findById($vendorId);

        if ($vendor === null) {
            throw new \RuntimeException("Vendor {$vendorId} not found");
        }

        $this->logger->info('Removing hold from vendor', [
            'vendor_id' => $vendorId,
            'reason' => $reason->value,
            'removed_by' => $removedBy,
        ]);

        // Remove the hold
        $vendor->removeHold($reason->value, $removedBy, $notes);

        $this->vendorPersist->update($vendor);

        // Dispatch event
        $this->eventDispatcher->dispatch(new VendorUnblockedEvent(
            tenantId: $tenantId,
            vendorId: $vendorId,
            reason: $reason,
            unblockedBy: $removedBy,
            unblockedAt: new \DateTimeImmutable(),
            notes: $notes
        ));

        // Audit log
        $this->auditLogger->log(
            entityId: $vendorId,
            action: 'vendor_hold_removed',
            description: sprintf('Hold removed from vendor: %s', $reason->description()),
            metadata: [
                'reason' => $reason->value,
                'removed_by' => $removedBy,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Check if a vendor has a specific hold.
     */
    public function hasHold(string $vendorId, VendorHoldReason $reason): bool
    {
        $vendor = $this->vendorQuery->findById($vendorId);

        if ($vendor === null) {
            return false;
        }

        $holds = $vendor->getActiveHolds();

        foreach ($holds as $hold) {
            if ($hold['reason'] === $reason->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all active holds for a vendor.
     *
     * @return array<VendorHoldReason>
     */
    public function getActiveHolds(string $vendorId): array
    {
        $vendor = $this->vendorQuery->findById($vendorId);

        if ($vendor === null) {
            return [];
        }

        $reasons = [];
        $holds = $vendor->getActiveHolds();

        foreach ($holds as $hold) {
            $reason = VendorHoldReason::tryFrom($hold['reason'] ?? '');
            if ($reason !== null) {
                $reasons[] = $reason;
            }
        }

        return $reasons;
    }

    /**
     * Clear all holds from a vendor.
     */
    public function clearAllHolds(
        string $tenantId,
        string $vendorId,
        string $clearedBy,
        ?string $notes = null
    ): int {
        $activeHolds = $this->getActiveHolds($vendorId);
        $clearedCount = 0;

        foreach ($activeHolds as $reason) {
            $this->removeHold($tenantId, $vendorId, $reason, $clearedBy, $notes);
            $clearedCount++;
        }

        return $clearedCount;
    }
}
