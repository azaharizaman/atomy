<?php

declare(strict_types=1);

namespace Nexus\Payment\ValueObjects;

use Nexus\Payment\Exceptions\InvalidPaymentReferenceException;

/**
 * Unique payment reference value object.
 *
 * Represents a unique, human-readable reference for a payment transaction.
 * Can include a type (e.g., INV for invoice), a prefix, and the actual value.
 */
final readonly class PaymentReference
{
    /**
     * @param string $value The reference value (e.g., "INV-2024-00001")
     * @param string|null $type Reference type classification (e.g., "invoice", "order")
     * @param string|null $prefix Optional prefix for display
     */
    public function __construct(
        public string $value,
        public ?string $type = null,
        public ?string $prefix = null,
    ) {
        if (trim($value) === '') {
            throw new InvalidPaymentReferenceException('Payment reference value cannot be empty');
        }

        if (strlen($value) > 100) {
            throw new InvalidPaymentReferenceException('Payment reference value cannot exceed 100 characters');
        }
    }

    /**
     * Create a payment reference from a string.
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create a payment reference with type.
     */
    public static function withType(string $value, string $type): self
    {
        return new self($value, $type);
    }

    /**
     * Create an invoice reference.
     */
    public static function invoice(string $invoiceNumber): self
    {
        return new self($invoiceNumber, 'invoice', 'INV');
    }

    /**
     * Create an order reference.
     */
    public static function order(string $orderNumber): self
    {
        return new self($orderNumber, 'order', 'ORD');
    }

    /**
     * Get the display value with prefix if available.
     */
    public function display(): string
    {
        if ($this->prefix !== null) {
            return sprintf('%s-%s', $this->prefix, $this->value);
        }

        return $this->value;
    }

    /**
     * Check equality with another reference.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value
            && $this->type === $other->type;
    }

    /**
     * Get the reference value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Convert to string.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
