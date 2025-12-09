<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for creating a Release Order against a Blanket PO.
 *
 * Release orders draw down from an established blanket PO's spending limit.
 */
final readonly class ReleaseOrderRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $blanketPoId Parent blanket PO identifier
     * @param string $requesterId User creating the release order
     * @param array<array{product_id: string, quantity: float, unit_price_cents: int, description?: string}> $lineItems Line items to order
     * @param \DateTimeImmutable $deliveryDate Requested delivery date
     * @param string|null $deliveryAddress Delivery address
     * @param string|null $notes Additional notes
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $blanketPoId,
        public string $requesterId,
        public array $lineItems,
        public \DateTimeImmutable $deliveryDate,
        public ?string $deliveryAddress = null,
        public ?string $notes = null,
        public array $metadata = [],
    ) {}

    /**
     * Calculate total amount for this release order in cents.
     */
    public function calculateTotalCents(): int
    {
        $total = 0;
        foreach ($this->lineItems as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (int) ($item['unit_price_cents'] ?? 0);
            $total += (int) ($quantity * $unitPrice);
        }
        return $total;
    }

    /**
     * Validate the request data.
     *
     * @return array<string, string> Validation errors keyed by field
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->lineItems)) {
            $errors['lineItems'] = 'At least one line item is required';
        }

        foreach ($this->lineItems as $index => $item) {
            if (!isset($item['product_id']) || empty($item['product_id'])) {
                $errors["lineItems.{$index}.product_id"] = 'Product ID is required';
            }
            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                $errors["lineItems.{$index}.quantity"] = 'Quantity must be positive';
            }
            if (!isset($item['unit_price_cents']) || $item['unit_price_cents'] < 0) {
                $errors["lineItems.{$index}.unit_price_cents"] = 'Unit price cannot be negative';
            }
        }

        if ($this->deliveryDate < new \DateTimeImmutable('today')) {
            $errors['deliveryDate'] = 'Delivery date cannot be in the past';
        }

        return $errors;
    }

    /**
     * Get the number of line items.
     */
    public function getLineItemCount(): int
    {
        return count($this->lineItems);
    }
}
