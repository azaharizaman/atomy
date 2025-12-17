<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\TaxValidationServiceInterface;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxCalculation;
use Nexus\ProcurementOperations\Events\Withholding\WithholdingTaxCalculatedEvent;
use Nexus\ProcurementOperations\Events\Withholding\WithholdingTaxExemptedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates withholding tax calculations for payments.
 *
 * This coordinator intercepts payment processing to ensure
 * correct withholding tax is applied before remittance.
 *
 * Responsibilities:
 * - Determine if withholding tax applies
 * - Calculate withholding amounts (standard, treaty, exempt)
 * - Coordinate tax remittance scheduling
 * - Generate withholding certificates/statements
 */
final readonly class WithholdingTaxCoordinator
{
    public function __construct(
        private TaxValidationServiceInterface $taxValidationService,
        private WithholdingTaxRemittanceInterface $remittanceService,
        private WithholdingCertificateGeneratorInterface $certificateGenerator,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Process withholding tax for a payment.
     *
     * @param string $paymentId Payment being processed
     * @param string $vendorId Vendor receiving payment
     * @param string $incomeType Type of income (royalty, service_fee, interest, etc.)
     * @param Money $grossAmount Gross payment amount before withholding
     * @param string $buyerCountryCode Buyer's tax jurisdiction
     * @return WithholdingTaxResult
     */
    public function processWithholding(
        string $paymentId,
        string $vendorId,
        string $incomeType,
        Money $grossAmount,
        string $buyerCountryCode,
    ): WithholdingTaxResult {
        $this->logger->info('Processing withholding tax', [
            'payment_id' => $paymentId,
            'vendor_id' => $vendorId,
            'income_type' => $incomeType,
            'gross_amount' => $grossAmount->getAmount(),
            'currency' => $grossAmount->getCurrency(),
        ]);

        // Calculate withholding tax
        $calculation = $this->taxValidationService->calculateWithholdingTax(
            $vendorId,
            $incomeType,
            $grossAmount,
            $buyerCountryCode,
        );

        // Handle different scenarios
        if (!$calculation->hasWithholding) {
            $this->handleExemption($paymentId, $vendorId, $calculation);

            return WithholdingTaxResult::noWithholding(
                paymentId: $paymentId,
                grossAmount: $grossAmount,
                netAmount: $grossAmount,
                reason: $calculation->exemptionReason ?? 'No withholding applicable',
            );
        }

        // Schedule tax remittance
        $remittanceId = $this->scheduleRemittance(
            paymentId: $paymentId,
            withholdingAmount: $calculation->withholdingAmount,
            buyerCountryCode: $buyerCountryCode,
            incomeType: $incomeType,
        );

        // Generate withholding certificate
        $certificateId = $this->generateCertificate(
            paymentId: $paymentId,
            vendorId: $vendorId,
            calculation: $calculation,
        );

        // Dispatch event
        $this->eventDispatcher->dispatch(new WithholdingTaxCalculatedEvent(
            paymentId: $paymentId,
            vendorId: $vendorId,
            grossAmount: $grossAmount,
            withholdingAmount: $calculation->withholdingAmount,
            netAmount: $calculation->netAmount,
            withholdingRate: $calculation->withholdingRate,
            incomeType: $incomeType,
            isTreatyRate: $calculation->treatyCountry !== null,
            remittanceId: $remittanceId,
            certificateId: $certificateId,
        ));

        $this->logger->info('Withholding tax calculated', [
            'payment_id' => $paymentId,
            'withholding_amount' => $calculation->withholdingAmount->getAmount(),
            'withholding_rate' => $calculation->withholdingRate,
            'is_treaty_rate' => $calculation->treatyCountry !== null,
            'remittance_id' => $remittanceId,
            'certificate_id' => $certificateId,
        ]);

        return WithholdingTaxResult::withWithholding(
            paymentId: $paymentId,
            grossAmount: $grossAmount,
            withholdingAmount: $calculation->withholdingAmount,
            netAmount: $calculation->netAmount,
            withholdingRate: $calculation->withholdingRate,
            incomeType: $incomeType,
            isTreatyRate: $calculation->treatyCountry !== null,
            treatyCountry: $calculation->treatyCountry,
            remittanceId: $remittanceId,
            certificateId: $certificateId,
        );
    }

    /**
     * Process a batch of payments for withholding tax.
     *
     * @param array<array{payment_id: string, vendor_id: string, income_type: string, gross_amount: Money}> $payments
     * @param string $buyerCountryCode
     * @return array<WithholdingTaxResult>
     */
    public function processBatchWithholding(array $payments, string $buyerCountryCode): array
    {
        $results = [];

        foreach ($payments as $payment) {
            $results[] = $this->processWithholding(
                paymentId: $payment['payment_id'],
                vendorId: $payment['vendor_id'],
                incomeType: $payment['income_type'],
                grossAmount: $payment['gross_amount'],
                buyerCountryCode: $buyerCountryCode,
            );
        }

        return $results;
    }

    /**
     * Get withholding summary for a period.
     *
     * @param string $tenantId
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable $to
     * @return WithholdingPeriodSummary
     */
    public function getPeriodSummary(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): WithholdingPeriodSummary {
        // Delegate to remittance service for summary
        return $this->remittanceService->getPeriodSummary($tenantId, $from, $to);
    }

    /**
     * Get pending remittances for a tenant.
     *
     * @param string $tenantId
     * @return array<PendingRemittance>
     */
    public function getPendingRemittances(string $tenantId): array
    {
        return $this->remittanceService->getPendingRemittances($tenantId);
    }

    /**
     * Mark a remittance as paid.
     *
     * @param string $remittanceId
     * @param \DateTimeImmutable $paymentDate
     * @param string|null $referenceNumber
     */
    public function markRemittancePaid(
        string $remittanceId,
        \DateTimeImmutable $paymentDate,
        ?string $referenceNumber = null,
    ): void {
        $this->remittanceService->markPaid($remittanceId, $paymentDate, $referenceNumber);

        $this->logger->info('Withholding tax remittance marked paid', [
            'remittance_id' => $remittanceId,
            'payment_date' => $paymentDate->format('Y-m-d'),
            'reference' => $referenceNumber,
        ]);
    }

    /**
     * Handle exemption scenario.
     */
    private function handleExemption(
        string $paymentId,
        string $vendorId,
        WithholdingTaxCalculation $calculation,
    ): void {
        $this->eventDispatcher->dispatch(new WithholdingTaxExemptedEvent(
            paymentId: $paymentId,
            vendorId: $vendorId,
            exemptionReason: $calculation->exemptionReason ?? 'Unknown',
            exemptionCertificateId: $calculation->exemptionCertificateId,
        ));

        $this->logger->info('Payment exempt from withholding tax', [
            'payment_id' => $paymentId,
            'vendor_id' => $vendorId,
            'reason' => $calculation->exemptionReason,
        ]);
    }

    /**
     * Schedule withholding tax remittance to tax authority.
     */
    private function scheduleRemittance(
        string $paymentId,
        Money $withholdingAmount,
        string $buyerCountryCode,
        string $incomeType,
    ): string {
        return $this->remittanceService->scheduleRemittance(
            paymentId: $paymentId,
            amount: $withholdingAmount,
            countryCode: $buyerCountryCode,
            incomeType: $incomeType,
        );
    }

    /**
     * Generate withholding certificate for vendor.
     */
    private function generateCertificate(
        string $paymentId,
        string $vendorId,
        WithholdingTaxCalculation $calculation,
    ): string {
        return $this->certificateGenerator->generate(
            paymentId: $paymentId,
            vendorId: $vendorId,
            grossAmount: $calculation->grossAmount,
            withholdingAmount: $calculation->withholdingAmount,
            netAmount: $calculation->netAmount,
            withholdingRate: $calculation->withholdingRate,
            incomeType: $calculation->incomeType,
            isTreatyRate: $calculation->treatyCountry !== null,
            treatyCountry: $calculation->treatyCountry,
        );
    }
}

/**
 * Result of withholding tax processing.
 */
final readonly class WithholdingTaxResult
{
    private function __construct(
        public string $paymentId,
        public Money $grossAmount,
        public Money $netAmount,
        public bool $hasWithholding,
        public ?Money $withholdingAmount = null,
        public ?float $withholdingRate = null,
        public ?string $incomeType = null,
        public ?string $reason = null,
        public bool $isTreatyRate = false,
        public ?string $treatyCountry = null,
        public ?string $remittanceId = null,
        public ?string $certificateId = null,
    ) {}

    public static function noWithholding(
        string $paymentId,
        Money $grossAmount,
        Money $netAmount,
        string $reason,
    ): self {
        return new self(
            paymentId: $paymentId,
            grossAmount: $grossAmount,
            netAmount: $netAmount,
            hasWithholding: false,
            reason: $reason,
        );
    }

    public static function withWithholding(
        string $paymentId,
        Money $grossAmount,
        Money $withholdingAmount,
        Money $netAmount,
        float $withholdingRate,
        string $incomeType,
        bool $isTreatyRate,
        ?string $treatyCountry,
        string $remittanceId,
        string $certificateId,
    ): self {
        return new self(
            paymentId: $paymentId,
            grossAmount: $grossAmount,
            netAmount: $netAmount,
            hasWithholding: true,
            withholdingAmount: $withholdingAmount,
            withholdingRate: $withholdingRate,
            incomeType: $incomeType,
            isTreatyRate: $isTreatyRate,
            treatyCountry: $treatyCountry,
            remittanceId: $remittanceId,
            certificateId: $certificateId,
        );
    }
}

/**
 * Summary of withholding for a period.
 */
final readonly class WithholdingPeriodSummary
{
    /**
     * @param array<array{income_type: string, total: Money, count: int}> $byIncomeType
     * @param array<array{vendor_id: string, vendor_name: string, total: Money}> $byVendor
     */
    public function __construct(
        public string $tenantId,
        public \DateTimeImmutable $periodStart,
        public \DateTimeImmutable $periodEnd,
        public Money $totalWithholding,
        public Money $totalRemitted,
        public Money $pendingRemittance,
        public int $paymentCount,
        public int $certificateCount,
        public array $byIncomeType,
        public array $byVendor,
    ) {}
}

/**
 * Pending remittance record.
 */
final readonly class PendingRemittance
{
    public function __construct(
        public string $remittanceId,
        public string $paymentId,
        public Money $amount,
        public string $incomeType,
        public \DateTimeImmutable $dueDate,
        public string $status,
    ) {}
}

/**
 * Interface for withholding tax remittance (adapter layer).
 */
interface WithholdingTaxRemittanceInterface
{
    public function scheduleRemittance(
        string $paymentId,
        Money $amount,
        string $countryCode,
        string $incomeType,
    ): string;

    public function getPeriodSummary(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): WithholdingPeriodSummary;

    /**
     * @return array<PendingRemittance>
     */
    public function getPendingRemittances(string $tenantId): array;

    public function markPaid(
        string $remittanceId,
        \DateTimeImmutable $paymentDate,
        ?string $referenceNumber,
    ): void;
}

/**
 * Interface for withholding certificate generation (adapter layer).
 */
interface WithholdingCertificateGeneratorInterface
{
    public function generate(
        string $paymentId,
        string $vendorId,
        Money $grossAmount,
        Money $withholdingAmount,
        Money $netAmount,
        float $withholdingRate,
        string $incomeType,
        bool $isTreatyRate,
        ?string $treatyCountry,
    ): string;
}
