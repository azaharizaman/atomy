<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

use Nexus\Manufacturing\Enums\WorkOrderStatus;

/**
 * Exception thrown when an invalid work order status transition is attempted.
 */
class InvalidWorkOrderStatusException extends \RuntimeException
{
    public function __construct(
        string $message = 'Invalid work order status operation',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function invalidTransition(
        string $workOrderId,
        WorkOrderStatus $currentStatus,
        WorkOrderStatus $targetStatus
    ): self {
        $validTransitions = implode(', ', array_map(
            fn (WorkOrderStatus $s) => $s->value,
            $currentStatus->getValidTransitions()
        ));

        return new self(
            "Cannot transition work order '{$workOrderId}' from '{$currentStatus->value}' to '{$targetStatus->value}'. " .
            "Valid transitions: [{$validTransitions}]"
        );
    }

    public static function cannotPerformAction(string $workOrderId, string $action, WorkOrderStatus $status): self
    {
        return new self(
            "Cannot perform '{$action}' on work order '{$workOrderId}' in '{$status->value}' status"
        );
    }

    public static function alreadyInStatus(string $workOrderId, WorkOrderStatus $status): self
    {
        return new self("Work order '{$workOrderId}' is already in '{$status->value}' status");
    }
}
