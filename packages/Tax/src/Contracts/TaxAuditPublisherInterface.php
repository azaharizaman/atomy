<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

use Nexus\Tax\ValueObjects\TaxBreakdown;
use Nexus\Tax\ValueObjects\TaxContext;

/**
 * Tax Audit Publisher Interface
 * 
 * Optional interface for publishing tax events to EventStream.
 * Enables event sourcing for tax calculations (if required).
 * 
 * Application layer implements this using Nexus\EventStream package.
 * Only bind if event sourcing is needed for compliance.
 */
interface TaxAuditPublisherInterface
{
    /**
     * Publish tax calculation event
     * 
     * Publishes immutable event to EventStream for audit trail.
     * 
     * @param TaxContext $context Transaction context
     * @param TaxBreakdown $breakdown Calculated tax breakdown
     * @param array<string, mixed> $metadata Optional event metadata
     * 
     * @return void
     */
    public function publishCalculationEvent(
        TaxContext $context,
        TaxBreakdown $breakdown,
        array $metadata = []
    ): void;

    /**
     * Publish tax adjustment event
     * 
     * Publishes adjustment/reversal event.
     * 
     * @param TaxContext $context Adjustment context
     * @param TaxBreakdown $adjustment Adjustment breakdown
     * @param string $originalTransactionId Original transaction being adjusted
     * @param array<string, mixed> $metadata Optional event metadata
     * 
     * @return void
     */
    public function publishAdjustmentEvent(
        TaxContext $context,
        TaxBreakdown $adjustment,
        string $originalTransactionId,
        array $metadata = []
    ): void;

    /**
     * Publish exemption application event
     * 
     * Records when exemption certificate was applied.
     * 
     * @param TaxContext $context Transaction context
     * @param string $certificateId Exemption certificate ID
     * @param string $exemptionPercentage Exemption percentage applied
     * @param array<string, mixed> $metadata Optional event metadata
     * 
     * @return void
     */
    public function publishExemptionEvent(
        TaxContext $context,
        string $certificateId,
        string $exemptionPercentage,
        array $metadata = []
    ): void;
}
