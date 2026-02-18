<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Listeners;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\Events\OrderCreatedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class CheckCreditListener
{
    public function __construct(
        private CreditManagerInterface $creditManager,
        private CustomerProviderInterface $customerProvider,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function handle(OrderCreatedEvent $event): void
    {
        $this->getLogger()->info('Checking credit for new order', [
            'order_id' => $event->orderId,
            'customer_id' => $event->customerId,
            'total_amount' => $event->totalAmount,
        ]);

        try {
            $customer = $this->customerProvider->findById(
                $event->tenantId,
                $event->customerId
            );

            if ($customer === null) {
                $this->getLogger()->warning('Customer not found for credit check', [
                    'order_id' => $event->orderId,
                    'customer_id' => $event->customerId,
                ]);
                return;
            }

            $isOnHold = $this->creditManager->isOnCreditHold(
                $event->tenantId,
                $event->customerId
            );

            if ($isOnHold) {
                $holdReason = $this->creditManager->getCreditHoldReason(
                    $event->tenantId,
                    $event->customerId
                );

                $this->getLogger()->warning('Customer is on credit hold', [
                    'order_id' => $event->orderId,
                    'customer_id' => $event->customerId,
                    'hold_reason' => $holdReason,
                ]);

                $this->creditManager->placeOnCreditHold(
                    $event->tenantId,
                    $event->customerId,
                    "Order {$event->orderNumber} created while on credit hold"
                );
                return;
            }

            $creditLimit = $this->creditManager->getCreditLimit(
                $event->tenantId,
                $event->customerId
            );
            $creditUsed = $this->creditManager->getCreditUsed(
                $event->tenantId,
                $event->customerId
            );
            $availableCredit = $creditLimit - $creditUsed;

            if ($event->totalAmount > $availableCredit) {
                $this->getLogger()->warning('Credit limit exceeded', [
                    'order_id' => $event->orderId,
                    'customer_id' => $event->customerId,
                    'credit_limit' => $creditLimit,
                    'credit_used' => $creditUsed,
                    'available_credit' => $availableCredit,
                    'order_amount' => $event->totalAmount,
                    'shortage' => $event->totalAmount - $availableCredit,
                ]);
            } else {
                $this->getLogger()->info('Credit check passed', [
                    'order_id' => $event->orderId,
                    'customer_id' => $event->customerId,
                    'available_credit' => $availableCredit,
                    'order_amount' => $event->totalAmount,
                ]);
            }
        } catch (\Throwable $e) {
            $this->getLogger()->error('Credit check failed', [
                'order_id' => $event->orderId,
                'customer_id' => $event->customerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function __invoke(OrderCreatedEvent $event): void
    {
        $this->handle($event);
    }
}
