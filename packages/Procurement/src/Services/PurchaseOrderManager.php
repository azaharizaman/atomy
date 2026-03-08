<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderPersistInterface;
use Nexus\Procurement\Contracts\DatabaseTransactionInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use Nexus\Procurement\Exceptions\PurchaseOrderNotFoundException;
use Nexus\Procurement\Exceptions\InvalidPurchaseOrderDataException;
use Nexus\Procurement\Exceptions\BudgetExceededException;
use Nexus\Procurement\Exceptions\InvalidRequisitionStateException;
use Psr\Log\LoggerInterface;

/**
 * Manages purchase order lifecycle.
 * 
 * Enforces business rules:
 * - PO amount cannot exceed requisition by more than configured tolerance (BUS-PRO-0101)
 * - Blanket PO releases cannot exceed total committed value
 */
final readonly class PurchaseOrderManager
{
    private const DEFAULT_TOLERANCE_PERCENT = 10.0;

    public function __construct(
        private PurchaseOrderQueryInterface $query,
        private PurchaseOrderPersistInterface $persist,
        private RequisitionRepositoryInterface $requisitionRepository,
        private RequisitionManager $requisitionManager,
        private DatabaseTransactionInterface $transaction,
        private LoggerInterface $logger,
        private float $poTolerancePercent = self::DEFAULT_TOLERANCE_PERCENT
    ) {
    }

    /**
     * Create purchase order from approved requisition.
     *
     * @param string $tenantId
     * @param string $requisitionId
     * @param string $creatorId
     * @param array{
     *   number: string,
     *   vendor_id: string,
     *   lines: array<array{requisition_line_id: string, quantity: float, unit_price: float, unit: string, item_code: string, description: string}>,
     *   expected_delivery_date?: string,
     *   payment_terms?: string,
     *   notes?: string,
     *   metadata?: array
     * } $data
     * @return PurchaseOrderInterface
     * @throws InvalidRequisitionStateException
     * @throws BudgetExceededException
     * @throws InvalidPurchaseOrderDataException
     */
    public function createFromRequisition(
        string $tenantId,
        string $requisitionId,
        string $creatorId,
        array $data
    ): PurchaseOrderInterface {
        // Verify requisition is approved
        $requisition = $this->requisitionManager->getRequisition($tenantId, $requisitionId);

        if ($requisition->getStatus() !== 'approved') {
            throw InvalidRequisitionStateException::cannotConvertStatus(
                $requisitionId,
                $requisition->getStatus()
            );
        }

        if ($requisition->isConverted()) {
            throw InvalidRequisitionStateException::alreadyConverted($requisitionId);
        }

        // Auto-copy lines from requisition if not provided
        if (!isset($data['lines'])) {
            $data['lines'] = array_map(function ($line) {
                return [
                    'line_number' => $line->getLineNumber(),
                    'item_code' => $line->getItemCode(),
                    'description' => $line->getItemDescription(),
                    'quantity' => $line->getQuantity(),
                    'unit' => $line->getUom(),
                    'unit_price' => $line->getUnitPriceEstimate(),
                ];
            }, $requisition->getLines());
        }

        $this->validatePurchaseOrderData($data);

        // Calculate total and validate against requisition
        $poTotal = $this->calculateTotal($data['lines']);
        $reqTotal = $requisition->getTotalEstimate();

        $this->validatePoAgainstRequisition($data['number'], $poTotal, $reqTotal);

        $this->logger->info('Creating purchase order from requisition', [
            'tenant_id' => $tenantId,
            'requisition_id' => $requisitionId,
            'requisition_number' => $requisition->getRequisitionNumber(),
            'po_number' => $data['number'],
            'creator_id' => $creatorId,
            'vendor_id' => $data['vendor_id'],
            'total' => $poTotal,
        ]);

        return $this->transaction->transactional(function () use ($tenantId, $requisitionId, $creatorId, $data) {
            $purchaseOrder = $this->persist->create($tenantId, $requisitionId, $creatorId, $data);

            // Mark requisition as converted
            $this->requisitionManager->markAsConverted($tenantId, $requisitionId, $purchaseOrder);

            $this->logger->info('Purchase order created', [
                'tenant_id' => $tenantId,
                'po_id' => $purchaseOrder->getId(),
                'po_number' => $purchaseOrder->getPoNumber(),
                'status' => $purchaseOrder->getStatus(),
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Create blanket purchase order.
     *
     * @param string $tenantId
     * @param string $creatorId
     * @param array{
     *   number: string,
     *   vendor_id: string,
     *   total_committed_value: float,
     *   valid_from: string,
     *   valid_until: string,
     *   description: string,
     *   payment_terms?: string,
     *   notes?: string,
     *   metadata?: array
     * } $data
     * @return PurchaseOrderInterface
     * @throws InvalidPurchaseOrderDataException
     */
    public function createBlanketPo(string $tenantId, string $creatorId, array $data): PurchaseOrderInterface
    {
        $this->validateBlanketPoData($data);

        $this->logger->info('Creating blanket purchase order', [
            'tenant_id' => $tenantId,
            'creator_id' => $creatorId,
            'po_number' => $data['number'],
            'vendor_id' => $data['vendor_id'],
            'total_committed_value' => $data['total_committed_value'],
        ]);

        $blanketPo = $this->persist->createBlanket($tenantId, $creatorId, $data);

        $this->logger->info('Blanket PO created', [
            'tenant_id' => $tenantId,
            'po_id' => $blanketPo->getId(),
            'po_number' => $blanketPo->getPoNumber(),
        ]);

        return $blanketPo;
    }

    /**
     * Create release against blanket PO.
     *
     * @param string $blanketPoId
     * @param string $creatorId
     * @param array{
     *   release_number: string,
     *   lines: array<array{item_code: string, description: string, quantity: float, unit: string, unit_price: float}>,
     *   expected_delivery_date?: string,
     *   notes?: string
     * } $data
     * @return PurchaseOrderInterface Release PO
     * @throws PurchaseOrderNotFoundException
     * @throws BudgetExceededException
     */
    public function createBlanketRelease(string $tenantId, string $blanketPoId, string $creatorId, array $data): PurchaseOrderInterface
    {
        $this->validateReleaseData($data);

        $blanketPo = $this->query->findById($tenantId, $blanketPoId);

        if ($blanketPo === null) {
            throw PurchaseOrderNotFoundException::forId($blanketPoId);
        }

        $releaseTotal = $this->calculateTotal($data['lines']);
        $totalCommitted = $blanketPo->getTotalCommittedValue() ?? 0.0;
        $totalReleased = $blanketPo->getTotalReleasedValue() ?? 0.0;
        $remainingValue = $totalCommitted - $totalReleased;

        if ($releaseTotal > $remainingValue) {
            throw BudgetExceededException::blanketPoReleaseExceedsTotal(
                $blanketPoId,
                $releaseTotal,
                $remainingValue
            );
        }

        $this->logger->info('Creating blanket PO release', [
            'tenant_id' => $tenantId,
            'blanket_po_id' => $blanketPoId,
            'blanket_po_number' => $blanketPo->getPoNumber(),
            'release_number' => $data['release_number'],
            'release_total' => $releaseTotal,
        ]);

        $release = $this->persist->createRelease($tenantId, $blanketPoId, $creatorId, $data);

        return $release;
    }

    /**
     * Release purchase order to vendor.
     *
     * @param string $tenantId
     * @param string $poId
     * @param string $releasedBy
     * @return PurchaseOrderInterface
     * @throws PurchaseOrderNotFoundException
     */
    public function releasePo(string $tenantId, string $poId, string $releasedBy): PurchaseOrderInterface
    {
        $po = $this->query->findById($tenantId, $poId);

        if ($po === null) {
            throw PurchaseOrderNotFoundException::forId($poId);
        }

        $this->logger->info('Releasing purchase order', [
            'tenant_id' => $tenantId,
            'po_id' => $poId,
            'po_number' => $po->getPoNumber(),
            'released_by' => $releasedBy,
        ]);

        $releasedPo = $this->persist->updateStatus($poId, 'released', $tenantId);

        return $releasedPo;
    }

    /**
     * Approve purchase order.
     *
     * @param string $poId
     * @param string $approverId
     * @return PurchaseOrderInterface
     * @throws PurchaseOrderNotFoundException
     */
    public function approvePo(string $tenantId, string $poId, string $approverId): PurchaseOrderInterface
    {
        $po = $this->query->findById($tenantId, $poId);

        if ($po === null) {
            throw PurchaseOrderNotFoundException::forId($poId);
        }

        $this->logger->info('Approving purchase order', [
            'tenant_id' => $tenantId,
            'po_id' => $poId,
            'po_number' => $po->getPoNumber(),
            'approver_id' => $approverId,
        ]);

        $approvedPo = $this->persist->approve($poId, $approverId, $tenantId);

        $this->logger->info('Purchase order approved', [
            'tenant_id' => $tenantId,
            'po_id' => $poId,
            'po_number' => $approvedPo->getPoNumber(),
            'status' => $approvedPo->getStatus(),
        ]);

        return $approvedPo;
    }

    /**
     * Close purchase order.
     *
     * @param string $tenantId
     * @param string $poId
     * @return PurchaseOrderInterface
     * @throws PurchaseOrderNotFoundException
     */
    public function closePo(string $tenantId, string $poId): PurchaseOrderInterface
    {
        $po = $this->query->findById($tenantId, $poId);

        if ($po === null) {
            throw PurchaseOrderNotFoundException::forId($poId);
        }

        $this->logger->info('Closing purchase order', [
            'tenant_id' => $tenantId,
            'po_id' => $poId,
            'po_number' => $po->getPoNumber(),
        ]);

        $closedPo = $this->persist->updateStatus($poId, 'closed', $tenantId);

        return $closedPo;
    }

    /**
     * Get purchase order by ID.
     *
     * @param string $poId
     * @return PurchaseOrderInterface
     * @throws PurchaseOrderNotFoundException
     */
    public function getPurchaseOrder(string $tenantId, string $poId): PurchaseOrderInterface
    {
        $po = $this->query->findById($tenantId, $poId);

        if ($po === null) {
            throw PurchaseOrderNotFoundException::forId($poId);
        }

        return $po;
    }

    /**
     * Get all purchase orders for tenant.
     *
     * @param string $tenantId
     * @param array<string, mixed> $filters
     * @return array<PurchaseOrderInterface>
     */
    public function getPurchaseOrdersForTenant(string $tenantId, array $filters = []): array
    {
        return $this->query->findByTenantId($tenantId, $filters);
    }

    /**
     * Get purchase orders by vendor.
     *
     * @param string $tenantId
     * @param string $vendorId
     * @return array<PurchaseOrderInterface>
     */
    public function getPurchaseOrdersByVendor(string $tenantId, string $vendorId): array
    {
        return $this->query->findByVendorId($tenantId, $vendorId);
    }

    /**
     * Calculate total from lines.
     *
     * @param array<array{quantity: float, unit_price: float}> $lines
     * @return float
     */
    private function calculateTotal(array $lines): float
    {
        $total = 0.0;

        foreach ($lines as $line) {
            $total += ($line['quantity'] * $line['unit_price']);
        }

        return $total;
    }

    /**
     * Validate PO total against requisition total.
     *
     * Enforces BUS-PRO-0101: PO cannot exceed requisition by more than tolerance %.
     *
     * @param string $poNumber
     * @param float $poTotal
     * @param float $reqTotal
     * @throws BudgetExceededException
     */
    private function validatePoAgainstRequisition(string $poNumber, float $poTotal, float $reqTotal): void
    {
        if ($poTotal <= $reqTotal) {
            return; // PO is within budget
        }

        $maxAllowed = $reqTotal * (1 + ($this->poTolerancePercent / 100));

        if ($poTotal > $maxAllowed) {
            throw BudgetExceededException::poExceedsRequisition(
                $poNumber,
                $poTotal,
                $reqTotal,
                $this->poTolerancePercent
            );
        }
    }

    /**
     * Validate purchase order data.
     *
     * @param array $data
     * @throws InvalidPurchaseOrderDataException
     */
    private function validatePurchaseOrderData(array $data): void
    {
        if (!isset($data['vendor_id'])) {
            throw InvalidPurchaseOrderDataException::missingVendor();
        }

        if (!isset($data['lines']) || !is_array($data['lines']) || count($data['lines']) === 0) {
            throw InvalidPurchaseOrderDataException::noLines();
        }

        if (!isset($data['number'])) {
            throw InvalidPurchaseOrderDataException::missingRequiredField('number');
        }
    }

    /**
     * Validate blanket PO data.
     *
     * @param array $data
     * @throws InvalidPurchaseOrderDataException
     */
    private function validateBlanketPoData(array $data): void
    {
        if (!isset($data['vendor_id'])) {
            throw InvalidPurchaseOrderDataException::missingVendor();
        }

        if (!isset($data['total_committed_value'])) {
            throw InvalidPurchaseOrderDataException::missingRequiredField('total_committed_value');
        }

        if (!isset($data['number'])) {
            throw InvalidPurchaseOrderDataException::missingRequiredField('number');
        }

        if (!isset($data['valid_from']) || !isset($data['valid_until'])) {
            throw InvalidPurchaseOrderDataException::missingRequiredField('valid_from/valid_until');
        }
    }

    /**
     * Validate release data.
     *
     * @param array $data
     * @throws InvalidPurchaseOrderDataException
     */
    private function validateReleaseData(array $data): void
    {
        if (!isset($data['lines']) || !is_array($data['lines']) || count($data['lines']) === 0) {
            throw InvalidPurchaseOrderDataException::noLines();
        }

        if (!isset($data['release_number'])) {
            throw InvalidPurchaseOrderDataException::missingRequiredField('release_number');
        }
    }
}
