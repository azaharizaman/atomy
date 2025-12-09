<?php

declare(strict_types=1);

namespace Nexus\Tax\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Tax\Contracts\WithholdingTaxCalculatorInterface;
use Nexus\Tax\Contracts\WithholdingTaxStrategyInterface;
use Nexus\Tax\Exceptions\JurisdictionNotSupportedException;
use Nexus\Tax\ValueObjects\WithholdingTaxContext;
use Nexus\Tax\ValueObjects\WithholdingTaxResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Withholding Tax Calculator
 * 
 * Orchestrates WHT calculation by delegating to jurisdiction-specific strategies.
 * Supports multiple jurisdictions via strategy pattern.
 * 
 * Framework-agnostic - strategies are injected at construction.
 */
final class WithholdingTaxCalculator implements WithholdingTaxCalculatorInterface
{
    /** @var array<string, WithholdingTaxStrategyInterface> */
    private readonly array $strategies;

    /**
     * @param array<WithholdingTaxStrategyInterface> $strategies Jurisdiction strategies
     * @param LoggerInterface $logger PSR-3 logger
     */
    public function __construct(
        array $strategies,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->strategies = $this->indexStrategies($strategies);
    }

    /**
     * {@inheritdoc}
     */
    public function calculate(WithholdingTaxContext $context, Money $grossAmount): WithholdingTaxResult
    {
        $strategy = $this->getStrategy($context->payerJurisdiction);

        $this->logger->debug('Calculating withholding tax', [
            'tenant_id' => $context->tenantId,
            'vendor_id' => $context->vendorId,
            'jurisdiction' => $context->payerJurisdiction,
            'gross_amount' => $grossAmount->getAmount(),
            'currency' => $grossAmount->getCurrency(),
        ]);

        $result = $strategy->calculate($context, $grossAmount);

        $this->logger->info('Withholding tax calculated', [
            'tenant_id' => $context->tenantId,
            'vendor_id' => $context->vendorId,
            'gross_amount' => $grossAmount->getAmount(),
            'withholding_amount' => $result->withholdingAmount->getAmount(),
            'effective_rate' => $result->effectiveRate,
            'form_type' => $result->formType,
        ]);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(WithholdingTaxContext $context): bool
    {
        try {
            $strategy = $this->getStrategy($context->payerJurisdiction);
            return $strategy->isApplicable($context);
        } catch (JurisdictionNotSupportedException) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableRate(WithholdingTaxContext $context): float
    {
        $strategy = $this->getStrategy($context->payerJurisdiction);
        return $strategy->getRate($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedJurisdictions(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * Get strategy for jurisdiction
     * 
     * @throws JurisdictionNotSupportedException
     */
    private function getStrategy(string $jurisdictionCode): WithholdingTaxStrategyInterface
    {
        $code = strtoupper($jurisdictionCode);

        if (!isset($this->strategies[$code])) {
            throw new JurisdictionNotSupportedException(
                "Withholding tax strategy not configured for jurisdiction: {$code}"
            );
        }

        return $this->strategies[$code];
    }

    /**
     * Index strategies by jurisdiction code
     * 
     * @param array<WithholdingTaxStrategyInterface> $strategies
     * @return array<string, WithholdingTaxStrategyInterface>
     */
    private function indexStrategies(array $strategies): array
    {
        $indexed = [];

        foreach ($strategies as $strategy) {
            $code = strtoupper($strategy->getJurisdictionCode());
            $indexed[$code] = $strategy;
        }

        return $indexed;
    }
}
