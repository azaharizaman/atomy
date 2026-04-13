<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\GrIrAccrualData;

/**
 * GR/IR Accrual Repository Interface
 * 
 * Defines the contract for persistence and retrieval of GR/IR accruals.
 */
interface GrIrAccrualRepositoryInterface
{
    /**
     * Get accrual by ID and tenant
     */
    public function getAccrual(string $accrualId, string $tenantId): ?GrIrAccrualData;

    /**
     * Save accrual
     */
    public function save(GrIrAccrualData $accrual): void;

    /**
     * Get unmatched accruals for a tenant
     * 
     * @return array<GrIrAccrualData>
     */
    public function findUnmatched(string $tenantId, ?\DateTimeImmutable $asOfDate = null): array;

    /**
     * Get aged accruals for a tenant
     * 
     * @return array<GrIrAccrualData>
     */
    public function findAged(string $tenantId, int $agingThresholdDays = 30, ?\DateTimeImmutable $asOfDate = null): array;

    /**
     * Get accruals by vendor
     * 
     * @return array<GrIrAccrualData>
     */
    public function findByVendor(string $tenantId, string $vendorId, bool $unmatchedOnly = true): array;

    /**
     * Get accruals by purchase order
     * 
     * @return array<GrIrAccrualData>
     */
    public function findByPurchaseOrder(string $tenantId, string $purchaseOrderId): array;

    /**
     * Get total accrual balance for a tenant
     */
    public function getTotalBalance(string $tenantId, ?\DateTimeImmutable $asOfDate = null): Money;

    /**
     * Find potential matches for an accrual
     * 
     * @return array<array{invoice_id: string, invoice_number: string, amount: Money, score: float}>
     */
    public function suggestMatches(string $accrualId, float $tolerancePercent = 5.0): array;
}
