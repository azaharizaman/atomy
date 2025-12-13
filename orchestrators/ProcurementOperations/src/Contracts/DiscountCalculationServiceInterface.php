<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\EarlyPaymentDiscountData;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountTierData;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountResult;

/**
 * Discount Calculation Service Interface
 * 
 * Defines the contract for discount calculations including:
 * - Early payment discounts (2/10 Net 30, etc.)
 * - Volume discounts (tiered pricing)
 * - Vendor-specific discount agreements
 * 
 * Consuming applications implement this interface to integrate
 * with their discount configuration and tracking systems.
 * 
 * @see EarlyPaymentDiscountData
 * @see VolumeDiscountTierData
 * @see VolumeDiscountResult
 */
interface DiscountCalculationServiceInterface
{
    /**
     * Get applicable early payment discount terms for an invoice
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string $invoiceId Invoice identifier
     * @return EarlyPaymentDiscountData|null Discount terms if applicable
     */
    public function getEarlyPaymentDiscount(
        string $tenantId,
        string $vendorId,
        string $invoiceId,
    ): ?EarlyPaymentDiscountData;

    /**
     * Calculate early payment discount amount
     *
     * @param EarlyPaymentDiscountData $discountTerms Discount terms
     * @param Money $invoiceAmount Invoice gross amount
     * @param \DateTimeImmutable $paymentDate Planned payment date
     * @return Money Discount amount (zero if discount not applicable)
     */
    public function calculateEarlyPaymentDiscountAmount(
        EarlyPaymentDiscountData $discountTerms,
        Money $invoiceAmount,
        \DateTimeImmutable $paymentDate,
    ): Money;

    /**
     * Check if early payment discount is still available
     *
     * @param EarlyPaymentDiscountData $discountTerms Discount terms
     * @param \DateTimeImmutable $asOfDate Date to check
     * @return bool True if discount is available
     */
    public function isEarlyPaymentDiscountAvailable(
        EarlyPaymentDiscountData $discountTerms,
        \DateTimeImmutable $asOfDate,
    ): bool;

    /**
     * Get days remaining to capture early payment discount
     *
     * @param EarlyPaymentDiscountData $discountTerms Discount terms
     * @param \DateTimeImmutable $asOfDate Date to check
     * @return int Days remaining (negative if expired)
     */
    public function getDaysToDiscountDeadline(
        EarlyPaymentDiscountData $discountTerms,
        \DateTimeImmutable $asOfDate,
    ): int;

    /**
     * Calculate annualized return rate for early payment discount
     * 
     * Formula: (Discount% / (100 - Discount%)) × (365 / (Net Days - Discount Days))
     *
     * @param EarlyPaymentDiscountData $discountTerms Discount terms
     * @return float Annualized return rate (e.g., 0.3648 for 36.48%)
     */
    public function calculateAnnualizedReturnRate(
        EarlyPaymentDiscountData $discountTerms,
    ): float;

    /**
     * Get volume discount tiers for a vendor/product
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string|null $productCategoryId Optional product category filter
     * @return array<VolumeDiscountTierData> Active discount tiers
     */
    public function getVolumeDiscountTiers(
        string $tenantId,
        string $vendorId,
        ?string $productCategoryId = null,
    ): array;

    /**
     * Calculate volume discount for given quantity/amount
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param Money $purchaseAmount Total purchase amount
     * @param float|null $quantity Total quantity (for unit-based tiers)
     * @param string|null $productCategoryId Product category
     * @param \DateTimeImmutable|null $asOfDate Date for tier effectiveness
     * @return VolumeDiscountResult Calculated discount result
     */
    public function calculateVolumeDiscount(
        string $tenantId,
        string $vendorId,
        Money $purchaseAmount,
        ?float $quantity = null,
        ?string $productCategoryId = null,
        ?\DateTimeImmutable $asOfDate = null,
    ): VolumeDiscountResult;

    /**
     * Get year-to-date purchase total for volume discount calculation
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string|null $productCategoryId Product category filter
     * @return Money YTD purchase total
     */
    public function getYtdPurchaseTotal(
        string $tenantId,
        string $vendorId,
        ?string $productCategoryId = null,
    ): Money;

    /**
     * Estimate potential savings from early payment discounts
     * 
     * Analyzes unpaid invoices and calculates total potential savings
     * if all available early payment discounts were captured.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable|null $asOfDate Analysis date
     * @return array{
     *     total_potential_savings: Money,
     *     invoice_count: int,
     *     average_annualized_return: float,
     *     invoices_with_discounts: array<array{
     *         invoice_id: string,
     *         vendor_id: string,
     *         invoice_amount: Money,
     *         discount_amount: Money,
     *         discount_deadline: \DateTimeImmutable,
     *         annualized_return: float
     *     }>
     * }
     */
    public function estimatePotentialDiscountSavings(
        string $tenantId,
        ?\DateTimeImmutable $asOfDate = null,
    ): array;

    /**
     * Record captured early payment discount
     *
     * @param string $tenantId Tenant identifier
     * @param string $invoiceId Invoice identifier
     * @param Money $discountAmount Discount captured
     * @param \DateTimeImmutable $paymentDate Actual payment date
     * @return void
     */
    public function recordCapturedDiscount(
        string $tenantId,
        string $invoiceId,
        Money $discountAmount,
        \DateTimeImmutable $paymentDate,
    ): void;

    /**
     * Record missed early payment discount
     *
     * @param string $tenantId Tenant identifier
     * @param string $invoiceId Invoice identifier
     * @param Money $missedAmount Discount amount missed
     * @param string $reason Reason for missing discount
     * @return void
     */
    public function recordMissedDiscount(
        string $tenantId,
        string $invoiceId,
        Money $missedAmount,
        string $reason,
    ): void;

    /**
     * Get discount performance metrics
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable $fromDate Start date
     * @param \DateTimeImmutable $toDate End date
     * @return array{
     *     total_discounts_captured: Money,
     *     total_discounts_missed: Money,
     *     capture_rate: float,
     *     average_annualized_return: float,
     *     total_invoices_with_discounts: int,
     *     invoices_captured: int,
     *     invoices_missed: int
     * }
     */
    public function getDiscountPerformanceMetrics(
        string $tenantId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate,
    ): array;

    /**
     * Prioritize invoices for payment based on discount opportunity
     * 
     * Returns invoices sorted by discount value and urgency
     *
     * @param string $tenantId Tenant identifier
     * @param Money $availableCash Available cash for payments
     * @return array<array{
     *     invoice_id: string,
     *     vendor_id: string,
     *     invoice_amount: Money,
     *     discount_amount: Money,
     *     net_payment_amount: Money,
     *     discount_deadline: \DateTimeImmutable,
     *     days_to_deadline: int,
     *     annualized_return: float,
     *     priority_score: float
     * }>
     */
    public function prioritizeInvoicesForDiscountCapture(
        string $tenantId,
        Money $availableCash,
    ): array;
}
