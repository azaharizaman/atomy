<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Listeners;

use Nexus\Payable\Events\InvoiceApprovedForPaymentEvent;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\SupplyChainOperations\Coordinators\LandedCostCoordinator;
use Psr\Log\LoggerInterface;

/**
 * Listens for approved invoices and triggers Landed Cost capitalization if applicable.
 *
 * Current Strategy: Parses Bill Description for "Landed Cost: {GRN_ID}" pattern.
 */
final readonly class LandedCostListener
{
    private const LANDED_COST_PATTERN = '/Landed Cost:\s*([A-Z0-9-]+)/i';

    public function __construct(
        private VendorBillRepositoryInterface $billRepository,
        private LandedCostCoordinator $coordinator,
        private LoggerInterface $logger
    ) {
    }

    public function onInvoiceApproved(InvoiceApprovedForPaymentEvent $event): void
    {
        // 1. Fetch the full bill entity
        $bill = $this->billRepository->findById($event->vendorBillId);
        
        if (!$bill) {
            $this->logger->warning("LandedCostListener: Bill {$event->vendorBillId} not found.");
            return;
        }

        // 2. Check for Landed Cost pattern in description
        $description = $bill->getDescription();
        if (empty($description)) {
            return;
        }

        if (preg_match(self::LANDED_COST_PATTERN, $description, $matches)) {
            $grnId = $matches[1];
            $amount = $bill->getTotalAmount(); // Assuming total bill amount is the cost
            
            $this->logger->info("Landed Cost detected for GRN {$grnId} from Bill {$bill->getBillNumber()}. Amount: {$amount}");

            try {
                // Distribute cost based on value by default
                $this->coordinator->distributeLandedCost($grnId, $amount, 'value');
            } catch (\Exception $e) {
                $this->logger->error("LandedCostListener: Failed to distribute cost. Error: " . $e->getMessage());
            }
        }
    }
}
