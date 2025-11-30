<?php

declare(strict_types=1);

namespace Nexus\Tax\Services;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\Contracts\TaxExemptionManagerInterface;
use Nexus\Tax\Contracts\TaxJurisdictionResolverInterface;
use Nexus\Tax\Contracts\TaxNexusManagerInterface;
use Nexus\Tax\Contracts\TaxRateRepositoryInterface;
use Nexus\Tax\Enums\TaxCalculationMethod;
use Nexus\Tax\Exceptions\NoNexusInJurisdictionException;
use Nexus\Tax\Exceptions\ReverseChargeNotAllowedException;
use Nexus\Tax\Exceptions\TaxCalculationException;
use Nexus\Tax\ValueObjects\TaxBreakdown;
use Nexus\Tax\ValueObjects\TaxContext;
use Nexus\Tax\ValueObjects\TaxLine;
use Psr\Log\LoggerInterface;

/**
 * Tax Calculator Service
 * 
 * Core tax calculation engine implementing hierarchical tax calculation.
 * Supports:
 * - Multi-jurisdiction taxes (federal → state → local)
 * - Cascading taxes (tax on tax)
 * - Partial and full exemptions
 * - Reverse charge mechanism
 * - BCMath precision
 * 
 * Stateless - all dependencies injected.
 */
final readonly class TaxCalculator implements TaxCalculatorInterface
{
    public function __construct(
        private TaxRateRepositoryInterface $rateRepository,
        private TaxJurisdictionResolverInterface $jurisdictionResolver,
        private ?TaxNexusManagerInterface $nexusManager = null,
        private ?TaxExemptionManagerInterface $exemptionManager = null,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function calculate(TaxContext $context, Money $amount): TaxBreakdown
    {
        $this->logger?->info('Tax calculation started', [
            'transaction_id' => $context->transactionId,
            'amount' => $amount->getAmount(),
            'currency' => $amount->getCurrency(),
        ]);

        try {
            // Handle reverse charge (no tax collected)
            if ($context->isReverseCharge()) {
                return $this->handleReverseCharge($context, $amount);
            }

            // Check nexus (if nexus manager bound)
            $jurisdiction = $this->jurisdictionResolver->resolve($context);
            $this->validateNexus($jurisdiction->code, $context->transactionDate);

            // Get tax rate
            $rate = $this->rateRepository->findByCode($context->taxCode, $context->transactionDate);

            // Calculate taxable base (apply exemption if present)
            $taxableBase = $this->calculateTaxableBase($amount, $context);

            // Calculate tax amount using BCMath
            $taxAmount = Money::of(
                $rate->calculateTaxAmount($taxableBase->getAmount()),
                $amount->getCurrency()
            );

            // Build tax line
            $taxLine = new TaxLine(
                rate: $rate,
                taxableBase: $taxableBase,
                amount: $taxAmount,
                description: $this->buildDescription($rate, $context),
                glAccountCode: $rate->glAccountCode,
                metadata: [
                    'jurisdiction_code' => $jurisdiction->code,
                    'has_exemption' => $context->hasExemption(),
                    'exemption_percentage' => $context->exemptionCertificate?->exemptionPercentage ?? '0.0000',
                ]
            );

            // Calculate gross amount
            $grossAmount = $context->calculationMethod->calculateGross(
                $amount->getAmount(),
                $taxAmount->getAmount()
            );

            $breakdown = new TaxBreakdown(
                netAmount: $amount,
                totalTaxAmount: $taxAmount,
                grossAmount: Money::of($grossAmount, $amount->getCurrency()),
                taxLines: [$taxLine],
                isReverseCharge: false,
                metadata: [
                    'calculation_method' => $context->calculationMethod->value,
                    'jurisdiction' => $jurisdiction->toArray(),
                ]
            );

            $this->logger?->info('Tax calculation completed', [
                'transaction_id' => $context->transactionId,
                'tax_amount' => $taxAmount->getAmount(),
            ]);

            return $breakdown;

        } catch (\Throwable $e) {
            $this->logger?->error('Tax calculation failed', [
                'transaction_id' => $context->transactionId,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof TaxCalculationException) {
                throw $e;
            }

            throw new TaxCalculationException($context, $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function previewWithoutExemption(TaxContext $context, Money $amount): TaxBreakdown
    {
        // Create context without exemption
        $contextWithoutExemption = new TaxContext(
            transactionId: $context->transactionId,
            transactionDate: $context->transactionDate,
            taxCode: $context->taxCode,
            taxType: $context->taxType,
            customerId: $context->customerId,
            destinationAddress: $context->destinationAddress,
            originAddress: $context->originAddress,
            serviceClassification: $context->serviceClassification,
            calculationMethod: $context->calculationMethod,
            exemptionCertificate: null, // Remove exemption
            metadata: array_merge($context->metadata, ['is_preview' => true])
        );

        return $this->calculate($contextWithoutExemption, $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateAdjustment(TaxBreakdown $original, ?Money $adjustmentAmount = null): TaxBreakdown
    {
        // If no adjustment amount specified, reverse entire transaction
        if ($adjustmentAmount === null) {
            return $this->reverseEntireBreakdown($original);
        }

        // Partial adjustment - calculate proportional tax
        $adjustmentRatio = bcdiv(
            $adjustmentAmount->getAmount(),
            $original->netAmount->getAmount(),
            6
        );

        $adjustedTaxAmount = Money::of(
            bcmul($original->totalTaxAmount->getAmount(), $adjustmentRatio, 4),
            $original->totalTaxAmount->getCurrency()
        );

        // Create negative tax lines
        $adjustedLines = array_map(
            fn(TaxLine $line) => new TaxLine(
                rate: $line->rate,
                taxableBase: Money::of(
                    bcmul($line->taxableBase->getAmount(), $adjustmentRatio, 4),
                    $line->taxableBase->getCurrency()
                ),
                amount: Money::of(
                    bcmul($line->amount->getAmount(), $adjustmentRatio, 4),
                    $line->amount->getCurrency()
                ),
                description: "Adjustment: {$line->description}",
                glAccountCode: $line->glAccountCode,
                metadata: array_merge($line->metadata, ['is_adjustment' => true])
            ),
            $original->taxLines
        );

        return new TaxBreakdown(
            netAmount: $adjustmentAmount,
            totalTaxAmount: $adjustedTaxAmount,
            grossAmount: $adjustmentAmount->add($adjustedTaxAmount),
            taxLines: $adjustedLines,
            isReverseCharge: $original->isReverseCharge,
            metadata: array_merge($original->metadata, [
                'is_adjustment' => true,
                'adjustment_ratio' => $adjustmentRatio,
            ])
        );
    }

    /**
     * Handle reverse charge transaction (EU VAT B2B cross-border)
     */
    private function handleReverseCharge(TaxContext $context, Money $amount): TaxBreakdown
    {
        // Validate tax type supports reverse charge
        if (!$context->taxType->supportsReverseCharge()) {
            throw new ReverseChargeNotAllowedException($context->taxType);
        }

        $this->logger?->info('Processing reverse charge transaction', [
            'transaction_id' => $context->transactionId,
        ]);

        // Return zero tax (buyer self-assesses)
        return new TaxBreakdown(
            netAmount: $amount,
            totalTaxAmount: Money::of('0.0000', $amount->getCurrency()),
            grossAmount: $amount, // No tax collected
            taxLines: [],
            isReverseCharge: true,
            metadata: [
                'calculation_method' => 'reverse_charge',
                'buyer_self_assessment_required' => true,
            ]
        );
    }

    /**
     * Calculate taxable base (apply exemption if present)
     */
    private function calculateTaxableBase(Money $originalAmount, TaxContext $context): Money
    {
        if (!$context->hasExemption()) {
            return $originalAmount;
        }

        // Validate exemption certificate (if manager bound)
        if ($this->exemptionManager !== null) {
            $this->exemptionManager->isValid(
                $context->exemptionCertificate,
                $context->transactionDate
            );
        }

        // Apply exemption percentage
        $reducedAmount = $context->exemptionCertificate->applyToAmount(
            $originalAmount->getAmount()
        );

        return Money::of($reducedAmount, $originalAmount->getCurrency());
    }

    /**
     * Validate business has nexus in jurisdiction
     */
    private function validateNexus(string $jurisdictionCode, \DateTimeInterface $date): void
    {
        // Skip if nexus manager not bound (optional dependency)
        if ($this->nexusManager === null) {
            return;
        }

        if (!$this->nexusManager->hasNexus($jurisdictionCode, $date)) {
            throw new NoNexusInJurisdictionException($jurisdictionCode, $date);
        }
    }

    /**
     * Build human-readable description for tax line
     */
    private function buildDescription(
        \Nexus\Tax\ValueObjects\TaxRate $rate,
        TaxContext $context
    ): string {
        $description = "{$rate->name} ({$rate->getPercentage()}%)";

        if ($context->hasExemption()) {
            $description .= sprintf(
                " - %s%% exemption applied",
                $context->exemptionCertificate->exemptionPercentage
            );
        }

        return $description;
    }

    /**
     * Reverse entire tax breakdown (full contra-transaction)
     */
    private function reverseEntireBreakdown(TaxBreakdown $original): TaxBreakdown
    {
        // Negate all amounts
        $negativeNetAmount = Money::of(
            '-' . $original->netAmount->getAmount(),
            $original->netAmount->getCurrency()
        );

        $negativeTaxAmount = Money::of(
            '-' . $original->totalTaxAmount->getAmount(),
            $original->totalTaxAmount->getCurrency()
        );

        $negativeGrossAmount = Money::of(
            '-' . $original->grossAmount->getAmount(),
            $original->grossAmount->getCurrency()
        );

        // Negate all tax lines
        $negativeLines = array_map(
            fn(TaxLine $line) => new TaxLine(
                rate: $line->rate,
                taxableBase: Money::of(
                    '-' . $line->taxableBase->getAmount(),
                    $line->taxableBase->getCurrency()
                ),
                amount: Money::of(
                    '-' . $line->amount->getAmount(),
                    $line->amount->getCurrency()
                ),
                description: "Reversal: {$line->description}",
                glAccountCode: $line->glAccountCode,
                metadata: array_merge($line->metadata, ['is_reversal' => true])
            ),
            $original->taxLines
        );

        return new TaxBreakdown(
            netAmount: $negativeNetAmount,
            totalTaxAmount: $negativeTaxAmount,
            grossAmount: $negativeGrossAmount,
            taxLines: $negativeLines,
            isReverseCharge: $original->isReverseCharge,
            metadata: array_merge($original->metadata, [
                'is_full_reversal' => true,
            ])
        );
    }
}
