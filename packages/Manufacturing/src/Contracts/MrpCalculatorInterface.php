<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\ValueObjects\MaterialRequirement;
use Nexus\Manufacturing\ValueObjects\PlannedOrder;
use Nexus\Manufacturing\ValueObjects\MrpResult;

/**
 * MRP (Material Requirements Planning) Calculator interface.
 *
 * Implements MRP algorithm to calculate material requirements and
 * generate planned production and purchase orders.
 */
interface MrpCalculatorInterface
{
    /**
     * Run MRP calculation for a product.
     *
     * @param string $productId Product to plan for
     * @param float $demandQuantity Quantity demanded
     * @param \DateTimeImmutable $demandDate Date demand is required
     * @param string $lotSizingStrategy Lot sizing strategy to use
     * @return MrpResult MRP calculation result
     */
    public function calculate(
        string $productId,
        float $demandQuantity,
        \DateTimeImmutable $demandDate,
        string $lotSizingStrategy = 'lot_for_lot'
    ): MrpResult;

    /**
     * Run full MRP explosion for all demand.
     *
     * Processes all gross requirements and generates planned orders.
     *
     * @param array<array{productId: string, quantity: float, date: \DateTimeImmutable}> $demands
     * @param string $lotSizingStrategy Default lot sizing strategy
     * @return array<PlannedOrder>
     */
    public function explode(array $demands, string $lotSizingStrategy = 'lot_for_lot'): array;

    /**
     * Calculate gross to net requirements.
     *
     * Gross Requirements - (On Hand + Scheduled Receipts - Safety Stock) = Net Requirements
     *
     * @param string $productId Product to calculate for
     * @param float $grossRequirement Gross quantity required
     * @return float Net requirement quantity
     */
    public function calculateNetRequirement(string $productId, float $grossRequirement): float;

    /**
     * Apply lot sizing strategy to net requirements.
     *
     * @param float $netRequirement Net requirement quantity
     * @param string $strategy Lot sizing strategy
     * @param array<string, mixed> $parameters Strategy-specific parameters
     * @return float Lot-sized order quantity
     */
    public function applyLotSizing(float $netRequirement, string $strategy, array $parameters = []): float;

    /**
     * Calculate lead time offset for order release.
     *
     * @param string $productId Product to check
     * @param \DateTimeImmutable $requiredDate Date requirement is needed
     * @return \DateTimeImmutable Date order must be released
     */
    public function offsetForLeadTime(string $productId, \DateTimeImmutable $requiredDate): \DateTimeImmutable;

    /**
     * Get available lot sizing strategies.
     *
     * @return array<string, string> Strategy code => description map
     */
    public function getAvailableStrategies(): array;

    /**
     * Validate MRP setup for a product.
     *
     * Checks for valid BOM, lead times, etc.
     *
     * @return array<string> List of validation errors (empty if valid)
     */
    public function validateSetup(string $productId): array;

    /**
     * Set planning parameters.
     *
     * @param array<string, mixed> $parameters Planning parameters
     */
    public function setParameters(array $parameters): void;

    /**
     * Get current planning parameters.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array;
}
