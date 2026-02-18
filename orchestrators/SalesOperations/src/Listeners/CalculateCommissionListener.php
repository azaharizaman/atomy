<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Listeners;

use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\Events\CommissionCalculatedEvent;
use Nexus\SalesOperations\Events\PaymentReceivedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class CalculateCommissionListener
{
    public function __construct(
        private SalesOrderProviderInterface $orderProvider,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null,
        private string $defaultCommissionRate = '10.00',
        private string $defaultCommissionBasis = 'gross_profit',
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function handle(PaymentReceivedEvent $event): void
    {
        if ($event->salespersonId === null) {
            $this->getLogger()->debug('No salesperson assigned, skipping commission', [
                'payment_id' => $event->paymentId,
                'order_id' => $event->orderId,
            ]);
            return;
        }

        $this->getLogger()->info('Calculating commission for payment', [
            'payment_id' => $event->paymentId,
            'order_id' => $event->orderId,
            'salesperson_id' => $event->salespersonId,
            'amount' => $event->amount,
        ]);

        try {
            $order = $this->orderProvider->findById(
                $event->tenantId,
                $event->orderId
            );

            if ($order === null) {
                $this->getLogger()->warning('Order not found for commission calculation', [
                    'order_id' => $event->orderId,
                ]);
                return;
            }

            $commissionAmount = $this->calculateCommission($event, $order);

            $commissionEvent = new CommissionCalculatedEvent(
                tenantId: $event->tenantId,
                commissionId: 'comm-' . bin2hex(random_bytes(8)),
                salespersonId: $event->salespersonId,
                orderId: $event->orderId,
                paymentId: $event->paymentId,
                commissionAmount: $commissionAmount,
                currencyCode: $event->currencyCode,
                rate: (float) $this->defaultCommissionRate,
                basis: $this->defaultCommissionBasis,
            );

            $this->eventDispatcher->dispatch($commissionEvent);

            $this->getLogger()->info('Commission calculated', [
                'payment_id' => $event->paymentId,
                'order_id' => $event->orderId,
                'salesperson_id' => $event->salespersonId,
                'commission_amount' => $commissionAmount,
                'rate' => $this->defaultCommissionRate,
                'basis' => $this->defaultCommissionBasis,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to calculate commission', [
                'payment_id' => $event->paymentId,
                'order_id' => $event->orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function calculateCommission(PaymentReceivedEvent $event, $order): float
    {
        $rate = (float) $this->defaultCommissionRate / 100;

        if ($this->defaultCommissionBasis === 'revenue') {
            return $event->amount * $rate;
        }

        if ($this->defaultCommissionBasis === 'gross_profit') {
            $estimatedCost = $order->getTotal() * 0.6;
            $grossProfit = $event->amount - ($estimatedCost * ($event->amount / $order->getTotal()));
            return max(0, $grossProfit * $rate);
        }

        return $event->amount * $rate;
    }

    public function __invoke(PaymentReceivedEvent $event): void
    {
        $this->handle($event);
    }
}
