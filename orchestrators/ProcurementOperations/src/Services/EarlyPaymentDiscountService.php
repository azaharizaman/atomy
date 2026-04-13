<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\DiscountCalculationServiceInterface;
use Nexus\ProcurementOperations\DTOs\Financial\EarlyPaymentDiscountData;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountResult;
use Nexus\ProcurementOperations\Events\Financial\EarlyPaymentDiscountCapturedEvent;
use Nexus\ProcurementOperations\Events\Financial\EarlyPaymentDiscountMissedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Early Payment Discount Service
 * 
 * Orchestrates the identification and capture of early payment discounts.
 * 
 * Following Advanced Orchestrator Pattern v1.1:
 * - Domain-driven calculation logic
 * - Cross-boundary event dispatching
 * - Strategy pattern for prioritization
 * 
 * @since 1.0.0
 */
final readonly class EarlyPaymentDiscountService implements DiscountCalculationServiceInterface
{
    /**
     * @param EventDispatcherInterface $eventDispatcher Dispatcher for discount events
     * @param LoggerInterface $logger Logger for calculation audits
     */
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getEarlyPaymentDiscount(
        string $tenantId,
        string $vendorId,
        string $invoiceId,
    ): ?EarlyPaymentDiscountData {
        // This would typically query a repository or vendor profile
        // Returning null as default; expected to be implemented by adapters or specialized services
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateEarlyPaymentDiscountAmount(
        EarlyPaymentDiscountData $discountTerms,
        Money $invoiceAmount,
        \DateTimeImmutable $paymentDate,
    ): Money {
        if (!$discountTerms->isDiscountAvailable($paymentDate)) {
            return Money::of(0, $invoiceAmount->getCurrency());
        }

        return $discountTerms->discountAmount ?? Money::of(0, $invoiceAmount->getCurrency());
    }

    /**
     * {@inheritDoc}
     */
    public function isEarlyPaymentDiscountAvailable(
        EarlyPaymentDiscountData $discountTerms,
        \DateTimeImmutable $asOfDate,
    ): bool {
        return $discountTerms->isDiscountAvailable($asOfDate);
    }

    /**
     * {@inheritDoc}
     */
    public function getDaysToDiscountDeadline(
        EarlyPaymentDiscountData $discountTerms,
        \DateTimeImmutable $asOfDate,
    ): int {
        return $discountTerms->getDaysRemainingForDiscount($asOfDate);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateAnnualizedReturnRate(
        EarlyPaymentDiscountData $discountTerms,
    ): float {
        return $discountTerms->getAnnualizedReturnRate();
    }

    /**
     * {@inheritDoc}
     */
    public function getVolumeDiscountTiers(
        string $tenantId,
        string $vendorId,
        ?string $productCategoryId = null,
    ): array {
        // This service focuses on early payment discounts.
        // For volume discounts, use VolumeDiscountService.
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function calculateVolumeDiscount(
        string $tenantId,
        string $vendorId,
        Money $purchaseAmount,
        ?float $quantity = null,
        ?string $productCategoryId = null,
        ?\DateTimeImmutable $asOfDate = null,
    ): VolumeDiscountResult {
        // This service focuses on early payment discounts.
        // For volume discounts, use VolumeDiscountService.
        return VolumeDiscountResult::noDiscount(
            vendorId: $vendorId,
            productCategoryId: $productCategoryId ?? 'ALL',
            quantity: $quantity ?? 1.0,
            amount: $purchaseAmount
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getYtdPurchaseTotal(
        string $tenantId,
        string $vendorId,
        ?string $productCategoryId = null,
    ): Money {
        return Money::of(0, 'USD');
    }

    /**
     * {@inheritDoc}
     */
    public function estimatePotentialDiscountSavings(
        string $tenantId,
        ?\DateTimeImmutable $asOfDate = null,
    ): array {
        return [
            'total_potential_savings' => Money::of(0, 'USD'),
            'invoice_count' => 0,
            'average_annualized_return' => 0.0,
            'invoices_with_discounts' => []
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function recordCapturedDiscount(
        string $tenantId,
        string $invoiceId,
        Money $discountAmount,
        \DateTimeImmutable $paymentDate,
    ): void {
        $this->logger->info('Early payment discount captured', [
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'discount_amount' => $discountAmount->getAmount(),
        ]);
        
        // In a real implementation, we would fetch the full discount data 
        // to populate the event correctly.
    }

    /**
     * {@inheritDoc}
     */
    public function recordMissedDiscount(
        string $tenantId,
        string $invoiceId,
        Money $missedAmount,
        string $reason,
    ): void {
        $this->logger->warning('Early payment discount missed', [
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'missed_amount' => $missedAmount->getAmount(),
            'reason' => $reason,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountPerformanceMetrics(
        string $tenantId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate,
    ): array {
        return [
            'total_discounts_captured' => Money::of(0, 'USD'),
            'total_discounts_missed' => Money::of(0, 'USD'),
            'capture_rate' => 0.0,
            'average_annualized_return' => 0.0,
            'total_invoices_with_discounts' => 0,
            'invoices_captured' => 0,
            'invoices_missed' => 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function prioritizeInvoicesForDiscountCapture(
        string $tenantId,
        Money $availableCash,
    ): array {
        return [];
    }
}
