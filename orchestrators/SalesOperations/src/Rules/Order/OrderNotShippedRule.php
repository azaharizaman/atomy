<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Order;

use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\DTOs\FulfillmentRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class OrderNotShippedRule implements RuleInterface
{
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
                sprintf('Order %s not found', $context->orderId)
            );
        }

        $status = $order->getStatus();

        if ($status === 'shipped' || $status === 'completed') {
            return RuleResult::fail(
                $this->getName(),
                sprintf('Order %s has already been fully shipped', $context->orderId),
                ['order_id' => $context->orderId, 'status' => $status]
            );
        }

        return RuleResult::pass(
            $this->getName(),
            'Order has not been fully shipped'
        );
    }

    public function getName(): string
    {
        return 'order_not_shipped';
    }
}
