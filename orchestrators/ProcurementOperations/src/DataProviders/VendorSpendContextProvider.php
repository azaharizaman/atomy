<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Party\Contracts\VendorQueryInterface;
use Nexus\Payable\Contracts\VendorBillQueryInterface;

/**
 * Aggregates vendor spend data from multiple packages.
 *
 * Provides spend analytics and insights for vendor management:
 * - Total spend by vendor
 * - Payment history
 * - Average payment terms performance
 * - Spend by category
 */
final readonly class VendorSpendContextProvider
{
    public function __construct(
        private VendorBillQueryInterface $vendorBillQuery,
        private ?VendorQueryInterface $vendorQuery = null,
    ) {}

    /**
     * Get vendor spend summary for a tenant.
     *
     * @return array<string, array{
     *     vendorId: string,
     *     vendorName: string,
     *     totalSpendCents: int,
     *     invoiceCount: int,
     *     paidCount: int,
     *     outstandingCount: int,
     *     outstandingAmountCents: int,
     *     averagePaymentDays: float,
     *     currency: string
     * }>
     */
    public function getVendorSpendSummary(
        string $tenantId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null,
    ): array {
        $bills = $this->vendorBillQuery->findByTenant($tenantId);
        $summary = [];

        foreach ($bills as $bill) {
            $invoiceDate = $bill->getInvoiceDate();

            // Apply date filters
            if ($fromDate !== null && $invoiceDate < $fromDate) {
                continue;
            }
            if ($toDate !== null && $invoiceDate > $toDate) {
                continue;
            }

            $vendorId = $bill->getVendorId();
            
            if (!isset($summary[$vendorId])) {
                $vendorName = $bill->getVendorName() ?? 'Unknown';
                if ($vendorName === 'Unknown' && $this->vendorQuery !== null) {
                    $vendor = $this->vendorQuery->findById($vendorId);
                    $vendorName = $vendor?->getName() ?? 'Unknown';
                }

                $summary[$vendorId] = [
                    'vendorId' => $vendorId,
                    'vendorName' => $vendorName,
                    'totalSpendCents' => 0,
                    'invoiceCount' => 0,
                    'paidCount' => 0,
                    'outstandingCount' => 0,
                    'outstandingAmountCents' => 0,
                    'averagePaymentDays' => 0.0,
                    'currency' => $bill->getCurrency(),
                    'paymentDaysSum' => 0,
                    'paidCountForAvg' => 0,
                ];
            }

            $summary[$vendorId]['totalSpendCents'] += $bill->getTotalAmountCents();
            $summary[$vendorId]['invoiceCount']++;

            if ($bill->isPaid()) {
                $summary[$vendorId]['paidCount']++;
                
                // Calculate payment days
                $paymentDate = $bill->getPaymentDate();
                if ($paymentDate !== null) {
                    $diff = $invoiceDate->diff($paymentDate);
                    if ($diff->days !== false) {
                        $summary[$vendorId]['paymentDaysSum'] += $diff->days;
                        $summary[$vendorId]['paidCountForAvg']++;
                    }
                }
            } else {
                $summary[$vendorId]['outstandingCount']++;
                $summary[$vendorId]['outstandingAmountCents'] += $bill->getOutstandingAmountCents();
            }
        }

        // Calculate averages and clean up
        foreach ($summary as $vendorId => $data) {
            if ($data['paidCountForAvg'] > 0) {
                $summary[$vendorId]['averagePaymentDays'] = 
                    round($data['paymentDaysSum'] / $data['paidCountForAvg'], 1);
            }
            unset($summary[$vendorId]['paymentDaysSum']);
            unset($summary[$vendorId]['paidCountForAvg']);
        }

        return $summary;
    }

    /**
     * Get spend by category for a vendor.
     *
     * @return array<string, array{
     *     category: string,
     *     totalSpendCents: int,
     *     invoiceCount: int
     * }>
     */
    public function getVendorSpendByCategory(
        string $tenantId,
        string $vendorId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null,
    ): array {
        $bills = $this->vendorBillQuery->findByVendor($tenantId, $vendorId);
        $categories = [];

        foreach ($bills as $bill) {
            $invoiceDate = $bill->getInvoiceDate();

            if ($fromDate !== null && $invoiceDate < $fromDate) {
                continue;
            }
            if ($toDate !== null && $invoiceDate > $toDate) {
                continue;
            }

            foreach ($bill->getLineItems() as $lineItem) {
                $category = $lineItem['category'] ?? 'Uncategorized';
                
                if (!isset($categories[$category])) {
                    $categories[$category] = [
                        'category' => $category,
                        'totalSpendCents' => 0,
                        'invoiceCount' => 0,
                    ];
                }

                $categories[$category]['totalSpendCents'] += $lineItem['lineTotalCents'] ?? 0;
            }

            // Count invoices per category (simplified - count invoice once per category)
            $invoiceCategories = array_unique(array_column($bill->getLineItems(), 'category'));
            foreach ($invoiceCategories as $cat) {
                $cat = $cat ?? 'Uncategorized';
                if (isset($categories[$cat])) {
                    $categories[$cat]['invoiceCount']++;
                }
            }
        }

        return $categories;
    }

    /**
     * Get payment performance metrics for a vendor.
     *
     * @return array{
     *     vendorId: string,
     *     vendorName: string,
     *     totalInvoices: int,
     *     paidOnTime: int,
     *     paidLate: int,
     *     onTimePercentage: float,
     *     averageDaysLate: float,
     *     discountsTaken: int,
     *     discountsMissed: int,
     *     potentialSavingsCents: int
     * }
     */
    public function getPaymentPerformance(
        string $tenantId,
        string $vendorId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null,
    ): array {
        $bills = $this->vendorBillQuery->findByVendor($tenantId, $vendorId);
        
        $vendorName = 'Unknown';
        if ($this->vendorQuery !== null) {
            $vendor = $this->vendorQuery->findById($vendorId);
            $vendorName = $vendor?->getName() ?? 'Unknown';
        }

        $metrics = [
            'vendorId' => $vendorId,
            'vendorName' => $vendorName,
            'totalInvoices' => 0,
            'paidOnTime' => 0,
            'paidLate' => 0,
            'onTimePercentage' => 0.0,
            'averageDaysLate' => 0.0,
            'discountsTaken' => 0,
            'discountsMissed' => 0,
            'potentialSavingsCents' => 0,
        ];

        $totalDaysLate = 0;
        $lateCount = 0;

        foreach ($bills as $bill) {
            $invoiceDate = $bill->getInvoiceDate();

            if ($fromDate !== null && $invoiceDate < $fromDate) {
                continue;
            }
            if ($toDate !== null && $invoiceDate > $toDate) {
                continue;
            }

            if (!$bill->isPaid()) {
                continue;
            }

            $metrics['totalInvoices']++;

            $dueDate = $bill->getDueDate();
            $paymentDate = $bill->getPaymentDate();

            if ($paymentDate !== null && $dueDate !== null) {
                if ($paymentDate <= $dueDate) {
                    $metrics['paidOnTime']++;
                } else {
                    $metrics['paidLate']++;
                    $diff = $dueDate->diff($paymentDate);
                    if ($diff->days !== false) {
                        $totalDaysLate += $diff->days;
                        $lateCount++;
                    }
                }
            }

            // Track discount performance
            $discountDate = $bill->getDiscountDate();
            $discountAmount = $bill->getDiscountAmountCents();
            
            if ($discountDate !== null && $discountAmount > 0) {
                if ($paymentDate !== null && $paymentDate <= $discountDate) {
                    $metrics['discountsTaken']++;
                } else {
                    $metrics['discountsMissed']++;
                    $metrics['potentialSavingsCents'] += $discountAmount;
                }
            }
        }

        // Calculate percentages and averages
        if ($metrics['totalInvoices'] > 0) {
            $metrics['onTimePercentage'] = round(
                ($metrics['paidOnTime'] / $metrics['totalInvoices']) * 100,
                1
            );
        }

        if ($lateCount > 0) {
            $metrics['averageDaysLate'] = round($totalDaysLate / $lateCount, 1);
        }

        return $metrics;
    }
}
