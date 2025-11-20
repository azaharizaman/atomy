<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use DateTimeImmutable;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Currency\Contracts\ExchangeRateServiceInterface;
use Nexus\Sales\Contracts\CreditLimitCheckerInterface;
use Nexus\Sales\Contracts\InvoiceManagerInterface;
use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Contracts\StockReservationInterface;
use Nexus\Sales\Enums\SalesOrderStatus;
use Nexus\Sales\Exceptions\ExchangeRateLockedException;
use Nexus\Sales\Exceptions\InvalidOrderStatusException;
use Nexus\Sales\Exceptions\SalesOrderNotFoundException;
use Nexus\Sequencing\Contracts\SequenceGeneratorInterface;
use Psr\Log\LoggerInterface;

/**
 * Sales order lifecycle management service.
 */
final readonly class SalesOrderManager
{
    public function __construct(
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private SequenceGeneratorInterface $sequenceGenerator,
        private ExchangeRateServiceInterface $exchangeRateService,
        private CreditLimitCheckerInterface $creditLimitChecker,
        private StockReservationInterface $stockReservation,
        private InvoiceManagerInterface $invoiceManager,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    /**
     * Create a new sales order (draft status).
     *
     * @param string $tenantId
     * @param string $customerId
     * @param array $lines Array of line data
     * @param array $data Additional order data
     * @return SalesOrderInterface
     */
    public function createOrder(
        string $tenantId,
        string $customerId,
        array $lines,
        array $data
    ): SalesOrderInterface {
        // Generate order number
        $orderNumber = $this->sequenceGenerator->generate(
            $tenantId,
            'sales_order',
            ['prefix' => 'SO']
        );

        // Create order entity (implementation-specific, will be in Atomy)
        // For now, this signature serves as the contract definition

        $this->logger->info('Sales order created', [
            'tenant_id' => $tenantId,
            'order_number' => $orderNumber,
            'customer_id' => $customerId,
        ]);

        return $order ?? throw new \RuntimeException('Sales order creation not implemented in package layer');
    }

    /**
     * Confirm sales order (lock exchange rate, check credit, reserve stock).
     *
     * @param string $orderId
     * @param string $confirmedBy User ID who confirmed the order
     * @return void
     * @throws SalesOrderNotFoundException
     * @throws InvalidOrderStatusException
     * @throws \Nexus\Sales\Exceptions\CreditLimitExceededException
     * @throws \Nexus\Sales\Exceptions\InsufficientStockException
     */
    public function confirmOrder(string $orderId, string $confirmedBy): void
    {
        $order = $this->salesOrderRepository->findById($orderId);

        if (!$order->getStatus()->canBeConfirmed()) {
            throw InvalidOrderStatusException::cannotConfirm($orderId, $order->getStatus());
        }

        // 1. Check credit limit
        $this->creditLimitChecker->checkCreditLimit(
            $order->getTenantId(),
            $order->getCustomerId(),
            $order->getTotal(),
            $order->getCurrencyCode()
        );

        // 2. Lock exchange rate (if foreign currency)
        if ($order->getExchangeRate() === null) {
            $baseCurrency = 'MYR'; // TODO: Get from tenant settings
            if ($order->getCurrencyCode() !== $baseCurrency) {
                $exchangeRate = $this->exchangeRateService->getRate(
                    $order->getCurrencyCode(),
                    $baseCurrency,
                    new DateTimeImmutable()
                );
                // Set exchange rate on order (implementation-specific mutation)
            }
        }

        // 3. Reserve stock
        $this->stockReservation->reserveStockForOrder($orderId);

        // 4. Update status to CONFIRMED
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $orderId,
            'order_confirmed',
            "Sales order {$order->getOrderNumber()} confirmed by {$confirmedBy}"
        );

        $this->logger->info('Sales order confirmed', [
            'order_id' => $orderId,
            'order_number' => $order->getOrderNumber(),
            'confirmed_by' => $confirmedBy,
        ]);
    }

    /**
     * Cancel sales order (release stock reservation).
     *
     * @param string $orderId
     * @param string|null $reason
     * @return void
     * @throws SalesOrderNotFoundException
     * @throws InvalidOrderStatusException
     */
    public function cancelOrder(string $orderId, ?string $reason = null): void
    {
        $order = $this->salesOrderRepository->findById($orderId);

        if ($order->getStatus()->isFinal()) {
            throw InvalidOrderStatusException::cannotTransition(
                $orderId,
                $order->getStatus(),
                SalesOrderStatus::CANCELLED
            );
        }

        // Release stock reservation
        $this->stockReservation->releaseStockReservation($orderId);

        // Update status to CANCELLED
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $orderId,
            'order_cancelled',
            "Sales order {$order->getOrderNumber()} cancelled" . ($reason ? ": {$reason}" : '')
        );

        $this->logger->info('Sales order cancelled', [
            'order_id' => $orderId,
            'order_number' => $order->getOrderNumber(),
            'reason' => $reason,
        ]);
    }

    /**
     * Mark order as shipped.
     *
     * @param string $orderId
     * @param bool $isPartialShipment
     * @return void
     * @throws SalesOrderNotFoundException
     * @throws InvalidOrderStatusException
     */
    public function markAsShipped(string $orderId, bool $isPartialShipment = false): void
    {
        $order = $this->salesOrderRepository->findById($orderId);

        if (!$order->getStatus()->canBeShipped()) {
            throw InvalidOrderStatusException::cannotTransition(
                $orderId,
                $order->getStatus(),
                $isPartialShipment ? SalesOrderStatus::PARTIALLY_SHIPPED : SalesOrderStatus::FULLY_SHIPPED
            );
        }

        $newStatus = $isPartialShipment ? SalesOrderStatus::PARTIALLY_SHIPPED : SalesOrderStatus::FULLY_SHIPPED;
        
        // Update status
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $orderId,
            'order_shipped',
            "Sales order {$order->getOrderNumber()} " . ($isPartialShipment ? 'partially' : 'fully') . ' shipped'
        );

        $this->logger->info('Sales order shipped', [
            'order_id' => $orderId,
            'order_number' => $order->getOrderNumber(),
            'is_partial' => $isPartialShipment,
        ]);
    }

    /**
     * Generate invoice from sales order.
     *
     * @param string $orderId
     * @return string Invoice ID
     * @throws SalesOrderNotFoundException
     * @throws InvalidOrderStatusException
     * @throws \BadMethodCallException If Receivable package not installed
     */
    public function generateInvoice(string $orderId): string
    {
        $order = $this->salesOrderRepository->findById($orderId);

        if (!$order->getStatus()->canBeInvoiced()) {
            throw InvalidOrderStatusException::cannotTransition(
                $orderId,
                $order->getStatus(),
                SalesOrderStatus::INVOICED
            );
        }

        // Generate invoice via stub interface (will throw NotImplementedException in V1)
        $invoiceId = $this->invoiceManager->generateInvoiceFromOrder($orderId);

        // Update status to INVOICED
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $orderId,
            'order_invoiced',
            "Invoice generated from sales order {$order->getOrderNumber()}"
        );

        $this->logger->info('Invoice generated from order', [
            'order_id' => $orderId,
            'order_number' => $order->getOrderNumber(),
            'invoice_id' => $invoiceId,
        ]);

        return $invoiceId;
    }

    /**
     * Find sales order by ID.
     *
     * @param string $orderId
     * @return SalesOrderInterface
     * @throws SalesOrderNotFoundException
     */
    public function findOrder(string $orderId): SalesOrderInterface
    {
        return $this->salesOrderRepository->findById($orderId);
    }

    /**
     * Find orders by customer.
     *
     * @param string $tenantId
     * @param string $customerId
     * @return SalesOrderInterface[]
     */
    public function findOrdersByCustomer(string $tenantId, string $customerId): array
    {
        return $this->salesOrderRepository->findByCustomer($tenantId, $customerId);
    }
}
