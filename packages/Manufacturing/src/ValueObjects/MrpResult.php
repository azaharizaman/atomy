<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

/**
 * MRP Result value object.
 *
 * Represents the complete result of an MRP calculation.
 */
final readonly class MrpResult
{
    /**
     * @param string $productId Product calculated for
     * @param array<PlannedOrder> $plannedOrders Generated planned orders
     * @param array<MaterialRequirement> $materialRequirements All material requirements
     * @param array<string> $warnings Calculation warnings
     * @param array<string> $errors Calculation errors
     * @param \DateTimeImmutable $calculatedAt Calculation timestamp
     * @param array<string, mixed> $parameters Parameters used for calculation
     */
    public function __construct(
        public string $productId,
        public array $plannedOrders = [],
        public array $materialRequirements = [],
        public array $warnings = [],
        public array $errors = [],
        public ?\DateTimeImmutable $calculatedAt = null,
        public array $parameters = [],
    ) {
    }

    /**
     * Check if calculation was successful (no errors).
     */
    public function isSuccessful(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * Check if calculation has warnings.
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Check if calculation has errors.
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Get manufacturing orders.
     *
     * @return array<PlannedOrder>
     */
    public function getManufacturingOrders(): array
    {
        return array_filter(
            $this->plannedOrders,
            fn (PlannedOrder $order) => $order->isManufacturing()
        );
    }

    /**
     * Get purchase orders.
     *
     * @return array<PlannedOrder>
     */
    public function getPurchaseOrders(): array
    {
        return array_filter(
            $this->plannedOrders,
            fn (PlannedOrder $order) => $order->isPurchase()
        );
    }

    /**
     * Get total planned order quantity.
     */
    public function getTotalPlannedQuantity(): float
    {
        return array_sum(array_map(
            fn (PlannedOrder $order) => $order->quantity,
            $this->plannedOrders
        ));
    }

    /**
     * Get total net requirement.
     */
    public function getTotalNetRequirement(): float
    {
        return array_sum(array_map(
            fn (MaterialRequirement $req) => $req->netRequirement,
            $this->materialRequirements
        ));
    }

    /**
     * Get requirements grouped by product.
     *
     * @return array<string, array<MaterialRequirement>>
     */
    public function getRequirementsByProduct(): array
    {
        $grouped = [];
        foreach ($this->materialRequirements as $requirement) {
            $grouped[$requirement->productId][] = $requirement;
        }
        return $grouped;
    }

    /**
     * Get orders grouped by date.
     *
     * @return array<string, array<PlannedOrder>>
     */
    public function getOrdersByDate(): array
    {
        $grouped = [];
        foreach ($this->plannedOrders as $order) {
            $date = $order->startDate->format('Y-m-d');
            $grouped[$date][] = $order;
        }
        ksort($grouped);
        return $grouped;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'plannedOrders' => array_map(
                fn (PlannedOrder $order) => $order->toArray(),
                $this->plannedOrders
            ),
            'materialRequirements' => array_map(
                fn (MaterialRequirement $req) => $req->toArray(),
                $this->materialRequirements
            ),
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'calculatedAt' => $this->calculatedAt?->format('Y-m-d H:i:s'),
            'parameters' => $this->parameters,
            'summary' => [
                'isSuccessful' => $this->isSuccessful(),
                'plannedOrderCount' => count($this->plannedOrders),
                'manufacturingOrderCount' => count($this->getManufacturingOrders()),
                'purchaseOrderCount' => count($this->getPurchaseOrders()),
                'totalPlannedQuantity' => $this->getTotalPlannedQuantity(),
                'totalNetRequirement' => $this->getTotalNetRequirement(),
            ],
        ];
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['productId'],
            plannedOrders: array_map(
                fn (array $order) => PlannedOrder::fromArray($order),
                $data['plannedOrders'] ?? []
            ),
            materialRequirements: array_map(
                fn (array $req) => MaterialRequirement::fromArray($req),
                $data['materialRequirements'] ?? []
            ),
            warnings: $data['warnings'] ?? [],
            errors: $data['errors'] ?? [],
            calculatedAt: isset($data['calculatedAt'])
                ? new \DateTimeImmutable($data['calculatedAt'])
                : null,
            parameters: $data['parameters'] ?? [],
        );
    }
}
