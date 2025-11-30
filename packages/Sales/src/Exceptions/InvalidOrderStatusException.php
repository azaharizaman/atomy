<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

use Nexus\Sales\Enums\SalesOrderStatus;

/**
 * Exception thrown when order status transition is invalid.
 */
class InvalidOrderStatusException extends SalesException
{
    public static function cannotTransition(string $orderId, SalesOrderStatus $currentStatus, SalesOrderStatus $newStatus): self
    {
        return new self(
            "Cannot transition order '{$orderId}' from '{$currentStatus->value}' to '{$newStatus->value}'."
        );
    }

    public static function cannotConfirm(string $orderId, SalesOrderStatus $status): self
    {
        return new self(
            "Cannot confirm order '{$orderId}' with status '{$status->value}'. " .
            "Order must be in 'draft' status."
        );
    }

    public static function cannotModify(string $orderId, SalesOrderStatus $status): self
    {
        return new self(
            "Cannot modify order '{$orderId}' with status '{$status->value}'. " .
            "Only draft orders can be modified."
        );
    }
}
