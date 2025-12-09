<?php

declare(strict_types=1);

namespace Nexus\Tax\Strategies\Withholding;

use Nexus\Common\ValueObjects\Money;
use Nexus\Tax\Contracts\WithholdingTaxStrategyInterface;
use Nexus\Tax\Data\WithholdingRates\USWithholdingRates;
use Nexus\Tax\Enums\PaymentType;
use Nexus\Tax\Enums\WithholdingTaxType;
use Nexus\Tax\ValueObjects\WithholdingTaxContext;
use Nexus\Tax\ValueObjects\WithholdingTaxLine;
use Nexus\Tax\ValueObjects\WithholdingTaxResult;

/**
 * US Withholding Tax Strategy
 * 
 * Implements US federal withholding tax rules:
 * - Backup withholding (24%) for missing W-9
 * - Non-resident alien withholding (30%) for cross-border
 * - FATCA withholding (28%)
 * - Treaty-reduced rates where applicable
 * 
 * Note: Full 1099 year-end reporting deferred to Phase C
 */
final readonly class USWithholdingStrategy implements WithholdingTaxStrategyInterface
{
    private const JURISDICTION_CODE = 'US';

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

        $lines = [
            new WithholdingTaxLine(
                taxType: $this->determineWithholdingType($context),
                authorityCode: 'IRS',
                authorityName: 'Internal Revenue Service',
                rate: $rate,
                baseAmount: $grossAmount,
                taxAmount: $withholdingAmount,
            ),
        ];

        $formInfo = $this->determineFormRequirements($context);

        return new WithholdingTaxResult(
            grossAmount: $grossAmount,
            netAmount: $netAmount,
            withholdingAmount: $withholdingAmount,
            effectiveRate: $rate,
            jurisdictionCode: self::JURISDICTION_CODE,
            formType: $formInfo['form'],
            formCategory: $formInfo['category'],
            lines: $lines,
            metadata: [
                'withholding_reason' => $this->getWithholdingReason($context),
                'treaty_applied' => $context->isTreatyEligible,
            ],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(WithholdingTaxContext $context): bool
    {
        // Backup withholding: Missing W-9 for US resident vendors
        if ($context->requiresBackupWithholding()) {
            return true;
        }

        // Non-resident withholding: Non-US vendors
        if ($context->isNonResident()) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getRate(WithholdingTaxContext $context): float
    {
        // Backup withholding for missing W-9
        if ($context->requiresBackupWithholding()) {
            return USWithholdingRates::BACKUP_WITHHOLDING_RATE;
        }

        // Non-resident withholding
        if ($context->isNonResident()) {
            // Check for treaty-reduced rate
            if ($context->isTreatyEligible && $context->treatyCountry !== null) {
                $treatyRate = USWithholdingRates::getTreatyRate(
                    $context->treatyCountry,
                    $context->paymentType
                );
                if ($treatyRate !== null) {
                    return $treatyRate;
                }
            }

            // Standard NRA rate based on payment type
            return USWithholdingRates::getNraRate($context->paymentType);
        }

        return 0.0;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedPaymentTypes(): array
    {
        return [
            PaymentType::SERVICES->value,
            PaymentType::PROFESSIONAL_SERVICES->value,
            PaymentType::CONSULTING->value,
            PaymentType::ROYALTIES->value,
            PaymentType::LICENSE_FEES->value,
            PaymentType::INTEREST->value,
            PaymentType::DIVIDENDS->value,
            PaymentType::RENT->value,
            PaymentType::COMMISSIONS->value,
        ];
    }

    /**
     * Determine withholding tax type based on context
     */
    private function determineWithholdingType(WithholdingTaxContext $context): WithholdingTaxType
    {
        if ($context->isTreatyEligible) {
            return WithholdingTaxType::TREATY;
        }

        return WithholdingTaxType::FEDERAL;
    }

    /**
     * Determine form requirements
     * 
     * @return array{form: string, category: string|null}
     */
    private function determineFormRequirements(WithholdingTaxContext $context): array
    {
        if ($context->isNonResident()) {
            return [
                'form' => '1042-S',
                'category' => null,
            ];
        }

        // Domestic payments with backup withholding
        return [
            'form' => '1099',
            'category' => $context->paymentType->getUS1099Category(),
        ];
    }

    /**
     * Get human-readable withholding reason
     */
    private function getWithholdingReason(WithholdingTaxContext $context): string
    {
        if ($context->requiresBackupWithholding()) {
            return 'Backup withholding - Missing or invalid W-9';
        }

        if ($context->isTreatyEligible) {
            return "Treaty-reduced withholding - {$context->treatyCountry} tax treaty";
        }

        if ($context->isNonResident()) {
            return 'Non-resident alien withholding (FDAP income)';
        }

        return 'Standard withholding';
    }
}
