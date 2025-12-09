<?php

declare(strict_types=1);

namespace Nexus\Tax\Strategies\Withholding;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Contracts\WithholdingTaxStrategyInterface;
use Nexus\Tax\Data\WithholdingRates\MYWithholdingRates;
use Nexus\Tax\Enums\PaymentType;
use Nexus\Tax\Enums\WithholdingTaxType;
use Nexus\Tax\ValueObjects\WithholdingTaxContext;
use Nexus\Tax\ValueObjects\WithholdingTaxLine;
use Nexus\Tax\ValueObjects\WithholdingTaxResult;

/**
 * Malaysian Withholding Tax Strategy
 * 
 * Implements Malaysian WHT rules per Income Tax Act 1967:
 * - Section 109: Interest, royalties (10-15%)
 * - Section 109B: Technical/management fees (10%)
 * - Section 107A: Contract payments to non-residents (10%/3%)
 * 
 * Rates based on LHDN (Inland Revenue Board) guidelines.
 * Pattern follows PayrollMysStatutory/Data/PcbTaxTable.php
 */
final readonly class MYWithholdingStrategy implements WithholdingTaxStrategyInterface
{
    private const JURISDICTION_CODE = 'MY';

    /**
     * {@inheritdoc}
     */
    public function getJurisdictionCode(): string
    {
        return self::JURISDICTION_CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function calculate(WithholdingTaxContext $context, Money $grossAmount): WithholdingTaxResult
    {
        if (!$this->isApplicable($context)) {
            return WithholdingTaxResult::noWithholding($grossAmount, self::JURISDICTION_CODE);
        }

        $rate = $this->getRate($context);
        $withholdingAmount = $grossAmount->multiply($rate);
        $netAmount = $grossAmount->subtract($withholdingAmount);

        $itaSection = MYWithholdingRates::getITASection($context->paymentType);

        $lines = [
            new WithholdingTaxLine(
                taxType: WithholdingTaxType::FEDERAL,
                authorityCode: 'LHDN',
                authorityName: 'Lembaga Hasil Dalam Negeri (IRBM)',
                rate: $rate,
                baseAmount: $grossAmount,
                taxAmount: $withholdingAmount,
                reference: $itaSection,
            ),
        ];

        return new WithholdingTaxResult(
            grossAmount: $grossAmount,
            netAmount: $netAmount,
            withholdingAmount: $withholdingAmount,
            effectiveRate: $rate,
            jurisdictionCode: self::JURISDICTION_CODE,
            formType: 'CP37',
            formCategory: $this->determineFormCategory($context->paymentType),
            lines: $lines,
            metadata: [
                'ita_section' => $itaSection,
                'payment_type' => $context->paymentType->value,
                'vendor_country' => $context->vendorJurisdiction,
            ],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(WithholdingTaxContext $context): bool
    {
        // Malaysian WHT only applies to payments to non-residents
        if ($context->vendorIsResident) {
            return false;
        }

        // Check if payment type is subject to WHT
        return MYWithholdingRates::isPaymentTypeSubject($context->paymentType);
    }

    /**
     * {@inheritdoc}
     */
    public function getRate(WithholdingTaxContext $context): float
    {
        // Check for treaty-reduced rate first
        if ($context->isTreatyEligible && $context->treatyCountry !== null) {
            $treatyRate = MYWithholdingRates::getTreatyRate(
                $context->treatyCountry,
                $context->paymentType
            );
            if ($treatyRate !== null) {
                return $treatyRate;
            }
        }

        // Standard rate based on payment type
        return MYWithholdingRates::getRate($context->paymentType);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedPaymentTypes(): array
    {
        return [
            PaymentType::ROYALTIES->value,
            PaymentType::LICENSE_FEES->value,
            PaymentType::PATENT_FEES->value,
            PaymentType::COPYRIGHT_FEES->value,
            PaymentType::TECHNICAL_SERVICES->value,
            PaymentType::MANAGEMENT_FEES->value,
            PaymentType::INTEREST->value,
            PaymentType::CONTRACT_PAYMENTS->value,
            PaymentType::CONSULTING->value,
            PaymentType::PROFESSIONAL_SERVICES->value,
        ];
    }

    /**
     * Determine CP37 form category
     */
    private function determineFormCategory(PaymentType $paymentType): string
    {
        return match ($paymentType) {
            PaymentType::INTEREST => 'CP37(A)',
            PaymentType::ROYALTIES,
            PaymentType::LICENSE_FEES,
            PaymentType::PATENT_FEES,
            PaymentType::COPYRIGHT_FEES => 'CP37(B)',
            PaymentType::TECHNICAL_SERVICES,
            PaymentType::MANAGEMENT_FEES,
            PaymentType::CONSULTING => 'CP37(D)',
            PaymentType::CONTRACT_PAYMENTS => 'CP37(E)',
            default => 'CP37',
        };
    }
}
