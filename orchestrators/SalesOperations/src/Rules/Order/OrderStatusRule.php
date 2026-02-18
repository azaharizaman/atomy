<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Order;

use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\DTOs\FulfillmentRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class OrderStatusRule implements RuleInterface
{
    private const CONFIRMED_STATUSES = ['confirmed', 'processing', 'partial_shipped'];

    public function __construct(
        private SalesOrderProviderInterface $orderProvider
    ) {}

    public function check(object $context): RuleResult
    {
        if (!$context instanceof FulfillmentRequest) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected FulfillmentRequest'
            );
        }

        $order = $this->orderProvider->findById(
            $context->tenantId,
            $context->orderId
        );

        if ($order === null) {
            return RuleResult::fail(
                $this->getName(),
                sprintf('Order %s not found', $context->orderId),
                ['order_id' => $context->orderId]
            );
        }

        $status = $order->getStatus();

        if (!in_array($status, self::CONFIRMED_STATUSES, true)) {
            return RuleResult::fail(
                $this->getName(),
                sprintf(
                    'Order %s is not in a fulfillable status (current: %s)',
                    $context->orderId,
                    $status
                ),
                ['order_id' => $context->orderId, 'status' => $status]
            );
        }

        return RuleResult::pass(
            $this->getName(),
            sprintf('Order is in fulfillable status: %s', $status)
        );
    }

    public function getName(): string
    {
        return 'order_status';
    }
}
