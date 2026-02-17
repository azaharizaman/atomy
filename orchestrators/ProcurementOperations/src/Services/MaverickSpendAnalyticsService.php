<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service to detect and analyze non-compliant procurement spend.
 */
final readonly class MaverickSpendAnalyticsService
{
    public function __construct(
        private VendorBillRepositoryInterface $billRepository,
        private PurchaseOrderQueryInterface $poQuery,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Identify spend occurring outside of formal procurement processes.
     */
    public function getOffContractSpendReport(
        string $tenantId, 
        \DateTimeImmutable $start, 
        \DateTimeImmutable $end
    ): array {
        $this->logger->info('Running maverick spend analysis', [
            'tenant_id' => $tenantId,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ]);

        // 1. Get all bills for the period
        // For simplicity, we assume getByVendor or a similar filter exists or we fetch by status
        $bills = $this->billRepository->getByStatus($tenantId, 'posted');
        
        $totalSpendCents = 0;
        $maverickSpendCents = 0;
        $maverickItems = [];

        foreach ($bills as $bill) {
            $billDate = $bill->getBillDate();
            if ($billDate < $start || $billDate > $end) {
                continue;
            }

            $totalSpendCents += $bill->getTotalAmountCents();

            // Maverick Spend Criteria: No associated Purchase Order
            if ($bill->getPurchaseOrderId() === null) {
                $maverickSpendCents += $bill->getTotalAmountCents();
                $maverickItems[] = [
                    'billId' => $bill->getId(),
                    'billNumber' => $bill->getBillNumber(),
                    'vendorId' => $bill->getVendorId(),
                    'amountCents' => $bill->getTotalAmountCents(),
                    'reason' => 'Direct Invoice (No PO)'
                ];
            }
        }

        return [
            'tenantId' => $tenantId,
            'period' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d')
            ],
            'summary' => [
                'totalSpendCents' => $totalSpendCents,
                'maverickSpendCents' => $maverickSpendCents,
                'maverickRatePercent' => $totalSpendCents > 0 ? round(($maverickSpendCents / $totalSpendCents) * 100, 2) : 0
            ],
            'maverickItems' => $maverickItems
        ];
    }
}
