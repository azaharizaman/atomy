<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Listeners;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Events\PaymentReceivedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class ReleaseCreditHoldListener
{
    public function __construct(
        private CreditManagerInterface $creditManager,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function handle(PaymentReceivedEvent $event): void
    {
        $this->getLogger()->info('Processing payment for credit release', [
            'payment_id' => $event->paymentId,
            'order_id' => $event->orderId,
            'customer_id' => $event->customerId,
            'amount' => $event->amount,
        ]);

        try {
            $this->creditManager->releaseCredit(
                $event->tenantId,
                $event->orderId
            );

            $this->getLogger()->info('Credit released for order', [
                'payment_id' => $event->paymentId,
                'order_id' => $event->orderId,
                'amount' => $event->amount,
            ]);

            $isOnHold = $this->creditManager->isOnCreditHold(
                $event->tenantId,
                $event->customerId
            );

            if ($isOnHold) {
                $this->creditManager->releaseCreditHold(
                    $event->tenantId,
                    $event->customerId
                );

                $this->getLogger()->info('Customer credit hold released', [
                    'customer_id' => $event->customerId,
                    'payment_id' => $event->paymentId,
                ]);
            }
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to release credit', [
                'payment_id' => $event->paymentId,
                'order_id' => $event->orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function __invoke(PaymentReceivedEvent $event): void
    {
        $this->handle($event);
    }
}
