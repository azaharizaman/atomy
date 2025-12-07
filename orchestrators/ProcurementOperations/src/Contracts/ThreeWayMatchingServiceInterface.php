<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\MatchingResult;
use Nexus\ProcurementOperations\DTOs\ThreeWayMatchContext;

/**
 * Contract for three-way matching calculation service.
 *
 * Pure calculation logic for matching PO ↔ GR ↔ Invoice.
 * No side effects - just computes match results.
 */
interface ThreeWayMatchingServiceInterface
{
    /**
     * Perform three-way match calculation.
     *
     * Compares:
     * - Invoice quantities vs PO quantities vs GR quantities
     * - Invoice prices vs PO prices
     * - Invoice amounts vs PO amounts vs GR values
     *
     * @param ThreeWayMatchContext $context Aggregated data from PO, GR, Invoice
     * @param float $priceTolerancePercent Maximum allowed price variance (%)
     * @param float $quantityTolerancePercent Maximum allowed quantity variance (%)
     */
    public function calculateMatch(
        ThreeWayMatchContext $context,
        float $priceTolerancePercent,
        float $quantityTolerancePercent
    ): MatchingResult;

    /**
     * Perform two-way match calculation (PO vs Invoice only).
     *
     * @param ThreeWayMatchContext $context Context with PO and Invoice (GR may be empty)
     * @param float $priceTolerancePercent Maximum allowed price variance (%)
     */
    public function calculateTwoWayMatch(
        ThreeWayMatchContext $context,
        float $priceTolerancePercent
    ): MatchingResult;

    /**
     * Calculate variance percentages.
     *
     * @return array{
     *     priceVariancePercent: float,
     *     quantityVariancePercent: float,
     *     amountVariancePercent: float,
     *     lineVariances: array<string, array{
     *         priceVariance: float,
     *         quantityVariance: float,
     *         withinTolerance: bool
     *     }>
     * }
     */
    public function calculateVariances(ThreeWayMatchContext $context): array;
}
