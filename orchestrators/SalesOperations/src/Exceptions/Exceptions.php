<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Exceptions;

use Exception;

final class CreditLimitExceededException extends Exception
{
    public function __construct(
        public readonly string $customerId,
        public readonly float $creditLimit,
        public readonly float $requestedAmount,
        public readonly float $availableCredit
    ) {
        parent::__construct(
            "Credit limit exceeded for customer {$customerId}. " .
            "Limit: {$creditLimit}, Requested: {$requestedAmount}, Available: {$availableCredit}"
        );
    }
}

final class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly string $productId,
        public readonly float $requested,
        public readonly float $available
    ) {
        parent::__construct(
            "Insufficient stock for product {$productId}. Requested: {$requested}, Available: {$available}"
        );
    }
}

final class OrderNotFoundException extends Exception
{
    public function __construct(public readonly string $orderId)
    {
        parent::__construct("Order not found: {$orderId}");
    }
}

final class QuotationNotConvertibleException extends Exception
{
    public function __construct(public readonly string $quotationId, public readonly string $reason)
    {
        parent::__construct("Quotation {$quotationId} cannot be converted: {$reason}");
    }
}

final class FulfillmentException extends Exception
{
    public function __construct(
        public readonly string $orderId,
        public readonly array $issues = []
    ) {
        parent::__construct("Fulfillment failed for order {$orderId}");
    }
}

final class CustomerNotFoundException extends Exception
{
    public function __construct(public readonly string $customerId)
    {
        parent::__construct("Customer not found: {$customerId}");
    }
}

final class PaymentException extends Exception
{
    public static function failed(string $paymentId, string $reason): self
    {
        return new self("Payment {$paymentId} failed: {$reason}");
    }

    public static function invalidAmount(float $amount): self
    {
        return new self("Invalid payment amount: {$amount}");
    }

    public static function alreadyProcessed(string $paymentId): self
    {
        return new self("Payment {$paymentId} has already been processed");
    }
}

final class ShipmentException extends Exception
{
    public static function cannotShip(string $orderId, string $reason): self
    {
        return new self("Cannot ship order {$orderId}: {$reason}");
    }

    public static function insufficientStock(string $productId, float $required, float $available): self
    {
        return new self(
            "Insufficient stock for {$productId}. Required: {$required}, Available: {$available}"
        );
    }

    public static function invalidWarehouse(string $warehouseId): self
    {
        return new self("Invalid warehouse: {$warehouseId}");
    }
}

final class CommissionException extends Exception
{
    public static function invalidRate(float $rate): self
    {
        return new self("Invalid commission rate: {$rate}");
    }

    public static function noSalespersonAssigned(string $orderId): self
    {
        return new self("No salesperson assigned to order {$orderId}");
    }

    public static function alreadyPaid(string $commissionId): self
    {
        return new self("Commission {$commissionId} has already been paid");
    }
}

final class PricingException extends Exception
{
    public static function invalidPrice(float $price): self
    {
        return new self("Invalid price: {$price}");
    }

    public static function discountExceeded(float $requested, float $maxAllowed): self
    {
        return new self(
            "Discount {$requested}% exceeds maximum allowed {$maxAllowed}%"
        );
    }

    public static function productNotPriced(string $productId): self
    {
        return new self("No pricing found for product {$productId}");
    }

    public static function invalidPriceList(string $priceListId): self
    {
        return new self("Invalid price list: {$priceListId}");
    }
}
